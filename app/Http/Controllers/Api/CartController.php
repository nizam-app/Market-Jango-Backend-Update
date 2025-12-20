<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CalculateDistance;
use App\Helpers\NotificationHelper;
use App\Helpers\ResponseHelper;
use App\Models\Buyer;
use App\Models\Cart;
use App\Models\DeliveryCharge;
use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\LocationRoute;
use App\Models\Vendor;
use Exception;

class CartController extends Controller
{
    // Get All Carts
    public function index(Request $request): JsonResponse
    {
        try {

            // get login buyer
            $buyerId = Buyer::where('user_id', $request->header('id'))->select('id')->first();
            if (!$buyerId) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $total = 0;
            // get cart data by login buyer
            $carts = Cart::where('buyer_id', $buyerId->id)
                ->where('status', 'active')
                ->with(['product', 'vendor', 'buyer'])
                ->get();
            if ($carts->isEmpty()) {
                return ResponseHelper::Out('success', 'Cart not found', null, 200);
            }
            foreach ($carts as $cartItem) {
                $total = $total + $cartItem->price + $cartItem->delivery_charge;
            }
            return ResponseHelper::Out('success', 'All carts successfully fetched', [$carts, "total" => $total], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {

            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'attributes' => 'nullable|json',
                'quantity'   => 'nullable|integer|min:1',
                'action'     => 'nullable|in:increase,decrease'
            ]);
            if ($validator->fails()) {
                return ResponseHelper::Out('failed', 'Validation error', $validator->errors()->first(), 422);
            }
            $userId = $request->header('id');
            $buyer = Buyer::where('user_id', $userId)
                ->select('id', 'ship_latitude', 'ship_longitude')
                ->first();


            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }

            $product = Product::find($request->product_id);
            if (!$product) {
                return ResponseHelper::Out('failed', 'Product not found', null, 404);
            }
            $vendor = Vendor::select('id', 'latitude', 'longitude')
                ->find($product->vendor_id);

            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }

            $requestedQty = $request->input('quantity', 1);
            $action = $request->input('action');
            $currentStock = $product->stock;


            $cart = Cart::where('product_id', $product->id)
                ->where('buyer_id', $buyer->id)
                ->first();


            // ===============================
            // DELIVERY CHARGE CALCULATION
            // ===============================

            $deliveryChargeAmount = 0;

            $routes = LocationRoute::with(['startPoint', 'endPoint'])->get();


            foreach ($routes as $route) {

                if (
                    !$route->radius_km ||
                    !$route->startPoint ||
                    !$route->endPoint
                ) {
                    continue;
                }

                // // Exact location match (small tolerance)
                // $vendorMatch =
                //     abs($vendor->latitude - $route->startPoint->latitude) < 0.001 &&
                //     abs($vendor->longitude - $route->endPoint->longitude) < 0.001;

                // $buyerMatch =
                //     abs($buyer->ship_latitude - $route->startPoint->latitude) < 0.001 &&
                //     abs($buyer->ship_longitude - $route->endPoint->longitude) < 0.001;

                // if ($vendorMatch && $buyerMatch) {
                //     $deliveryChargeAmount = (float) $route->price;
                //     break;
                // }

                // Radius based check
                $vendorDistance = CalculateDistance::distanceInKm(
                    $vendor->latitude,
                    $vendor->longitude,
                    $route->startPoint->latitude,
                    $route->startPoint->longitude
                );
                $buyerDistance = CalculateDistance::distanceInKm(
                    $buyer->ship_latitude,
                    $buyer->ship_longitude,
                    $route->endPoint->latitude,
                    $route->endPoint->longitude
                );
                if (
                    $vendorDistance <= $route->radius_km &&
                    $buyerDistance <= $route->radius_km
                ) {
                    $deliveryChargeAmount = (float) $route->price;
                    break;
                }
            }
            // ===============================
            // EXISTING CART
            // ===============================

            if ($cart) {

                if ($action === 'increase') {
                    $newQty = $cart->quantity + 1;
                } elseif ($action === 'decrease') {
                    $newQty = max($cart->quantity - 1, 1);
                } else {
                    $newQty = $cart->quantity + $requestedQty;
                }

                if ($newQty > $currentStock) {
                    return ResponseHelper::Out('failed', 'Stock not available', null, 400);
                }

                $cart->update([
                    'quantity'        => $newQty,
                    'price'           => (float)$product->sell_price * $newQty,
                    'delivery_charge' => $deliveryChargeAmount,
                ]);

                return ResponseHelper::Out('success', 'Cart updated successfully', $cart, 200);
            }

            // ===============================
            // NEW CART ITEM
            // ===============================

            if ($requestedQty > $currentStock) {
                return ResponseHelper::Out('failed', 'Not enough stock available', null, 400);
            }

            $cart = Cart::create([
                'product_id'      => $product->id,
                'vendor_id'       => $vendor->id,
                'buyer_id'        => $buyer->id,
                'quantity'        => $requestedQty,
                'attributes'      => $request->input('attributes'),
                'price'           => (float)$product->sell_price * $requestedQty,
                'delivery_charge' => $deliveryChargeAmount,
                'status'          => 'active',
            ]);

            NotificationHelper::sendNotification(
                $userId,
                $userId,
                'You have added an item to cart',
                $buyer->name ?? ''
            );

            return ResponseHelper::Out('success', 'Cart item added successfully', $cart, 201);
        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    public function addOfferToCart(Request $request, $offerId): JsonResponse
    {
        try {

            $userId = $request->header('id');
            if (!$userId) {
                return ResponseHelper::Out('failed', 'User ID header missing', null, 400);
            }

            $buyer = Buyer::where('user_id', $userId)
                ->select('id', 'ship_latitude', 'ship_longitude')
                ->first();

            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }

            $offer = Offer::find($offerId);
            if (!$offer) {
                return ResponseHelper::Out('failed', 'Offer not found', null, 404);
            }

            if ((int)$offer->receiver_id !== (int)$userId) {
                return ResponseHelper::Out('failed', 'This offer is not for your account.', null, 403);
            }

            if ($offer->status !== 'pending') {
                return ResponseHelper::Out('failed', 'This offer is already ' . $offer->status, null, 400);
            }

            $product = Product::find($offer->product_id);
            if (!$product) {
                return ResponseHelper::Out('failed', 'Product not found', null, 404);
            }

            $quantity = $offer->quantity ?? 1;

            if ($product->stock < $quantity) {
                return ResponseHelper::Out('failed', 'Not enough stock available', null, 400);
            }

            $vendor = Vendor::select('id', 'latitude', 'longitude')
                ->find($product->vendor_id);

            if (
                !$vendor ||
                !$vendor->latitude || !$vendor->longitude ||
                !$buyer->ship_latitude || !$buyer->ship_longitude
            ) {
                return ResponseHelper::Out('failed', 'Location data missing', null, 422);
            }

            // ===============================
            // DELIVERY CHARGE (ROUTE BASED)
            // ===============================

            $deliveryChargeAmount = 100;

            $routes = LocationRoute::with(['startPoint', 'endPoint'])->get();

            foreach ($routes as $route) {

                if (
                    !$route->radius_km ||
                    !$route->startPoint ||
                    !$route->endPoint
                ) {
                    continue;
                }

                $vendorDistance = CalculateDistance::distanceInKm(
                    $vendor->latitude,
                    $vendor->longitude,
                    $route->startPoint->latitude,
                    $route->startPoint->longitude
                );

                $buyerDistance = CalculateDistance::distanceInKm(
                    $buyer->ship_latitude,
                    $buyer->ship_longitude,
                    $route->endPoint->latitude,
                    $route->endPoint->longitude
                );

                if (
                    $vendorDistance <= $route->radius_km &&
                    $buyerDistance <= $route->radius_km
                ) {
                    $deliveryChargeAmount = (float) $route->price;
                    break;
                }
            }

            // ===============================
            // CART CREATE
            // ===============================

            Cart::where('product_id', $product->id)
                ->where('buyer_id', $buyer->id)
                ->delete();

            $lineSubtotal = $offer->sale_price * $quantity;

            $cart = Cart::create([
                'product_id'      => $product->id,
                'vendor_id'       => $vendor->id,
                'buyer_id'        => $buyer->id,
                'quantity'        => $quantity,
                'attributes'      => $offer->attributes,
                'price'           => $lineSubtotal,
                'delivery_charge' => $deliveryChargeAmount,
                'status'          => 'active',
            ]);

            // UPDATE OFFER STATUS
            $offer->status = 'accepted';
            $offer->save();

            return ResponseHelper::Out(
                'success',
                'Offer added to cart successfully',
                $cart,
                200
            );
        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }



    //============================old cart start=========================
    // public function store(Request $request): JsonResponse
    // {
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'product_id' => 'required|exists:products,id',
    //             'attributes' => 'nullable|json',
    //             'quantity' => 'nullable|integer|min:1',
    //             'action' => 'nullable|in:increase,decrease'
    //         ]);

    //         if ($validator->fails()) {
    //             return ResponseHelper::Out('failed', 'Validation exception', $validator->errors()->first(), 422);
    //         }
    //         $userId = $request->header('id');
    //         $buyer = Buyer::where('user_id', $userId)->select('id')->first();
    //         if (!$buyer) {
    //             return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
    //         }
    //         $productId = $request->input('product_id');
    //         $product = Product::find($productId);
    //         if (!$product) {
    //             return ResponseHelper::Out('failed', 'Product not found', null, 422);
    //         }
    //         $vendorId = $product->vendor_id;
    //         $requestedQty = $request->input('quantity', 1);
    //         $action = $request->input('action');
    //         $cart = Cart::where('product_id', $productId)
    //             ->where('buyer_id', $buyer->id)
    //             ->first();

    //         $currentStock = $product->stock;

    //         // EXISTING CART
    //         if ($cart) {

    //             if ($action === 'increase') {
    //                 $newQty = $cart->quantity + 1;

    //                 //STOCK CHECK
    //                 if ($newQty > $currentStock) {
    //                     return ResponseHelper::Out('failed', 'Stock not available', null, 400);
    //                 }
    //             } elseif ($action === 'decrease') {
    //                 $newQty = max($cart->quantity - 1, 1);
    //             } else {
    //                 $newQty = $cart->quantity + $requestedQty;
    //                 if ($newQty > $currentStock) {
    //                     return ResponseHelper::Out('failed', 'Stock not available', null, 400);
    //                 }
    //             }

    //             // DELIVERY CHARGE CALC
    //             $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
    //                 ->where('product_id', $productId)
    //                 ->where('quantity', '<=', $newQty)
    //                 ->orderBy('quantity', 'desc')
    //                 ->first();

    //             $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;

    //             // UPDATE CART
    //             $cart->update([
    //                 'quantity' => $newQty,
    //                 'price' => (float)$product->sell_price * $newQty,
    //                 'delivery_charge' => $deliveryChargeAmount,
    //             ]);
    //             return ResponseHelper::Out('success', 'Cart updated successfully', $cart, 200);
    //         }

    //         // NEW CART ITEM
    //         if ($requestedQty > $currentStock) {
    //             return ResponseHelper::Out('failed', 'Not enough stock available', null, 400);
    //         }

    //         $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
    //             ->where('product_id', $productId)
    //             ->where('quantity', '<=', $requestedQty)
    //             ->orderBy('quantity', 'desc')
    //             ->first();

    //         $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;

    //         $cart = Cart::create([
    //             'product_id' => $productId,
    //             'vendor_id' => $vendorId,
    //             'buyer_id' => $buyer->id,
    //             'quantity' => $requestedQty,
    //             'attributes'    => $request->input('attributes'),
    //             'price' => (float)$product->sell_price * $requestedQty,
    //             'delivery_charge' => $deliveryChargeAmount,
    //             'status' => 'active',
    //         ]);
    //         // SEND NOTIFICATION
    //         $senderId = $userId;
    //         $message = 'You have add to cart';
    //         $name = $buyer->name;
    //         NotificationHelper::sendNotification($senderId, $senderId, $message, $name);
    //         return ResponseHelper::Out('success', 'Cart item added successfully', $cart, 201);
    //     } catch (Exception $e) {
    //         return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
    //     }
    // }
    //============================old cart end=======================

    //============================old offer to cart start=========================
    // public function addOfferToCart(Request $request, $offerId): JsonResponse
    // {
    //     try {
    //         $userId = $request->header('id');
    //         if (!$userId) {
    //             return ResponseHelper::Out('failed', 'User ID header missing', null, 400);
    //         }

    //         $buyer = Buyer::where('user_id', $userId)->select('id')->first();
    //         if (!$buyer) {
    //             return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
    //         }

    //         $offer = Offer::find($offerId);
    //         if (!$offer) {
    //             return ResponseHelper::Out('failed', 'Offer not found', null, 404);
    //         }

    //         if ((int)$offer->receiver_id !== (int)$userId) {
    //             return ResponseHelper::Out('failed', 'This offer is not for your account.', null, 403);
    //         }

    //         if ($offer->status !== 'pending') {
    //             return ResponseHelper::Out('failed', 'This offer is already ' . $offer->status . '.', null, 400);
    //         }

    //         $product = Product::find($offer->product_id);
    //         if (!$product) {
    //             return ResponseHelper::Out('failed', 'Product not found', null, 422);
    //         }

    //         $quantity = $offer->quantity ?? 1;

    //         // ✅ STOCK CHECK
    //         if ($product->stock < $quantity) {
    //             return ResponseHelper::Out('failed', 'Not enough stock available', null, 400);
    //         }

    //         $vendorId = $product->vendor_id;
    //         $offerPrice     =  $offer->sale_price;
    //         $offerDelivery  =  $offer->delivery_charge;
    //         $lineSubtotal   = $offerPrice * $quantity;

    //         // DELETE OLD CART IF EXIST
    //         $cart = Cart::where('product_id', $offer->product_id)
    //             ->where('buyer_id', $buyer->id)
    //             ->first();
    //         if ($cart) {
    //             $cart->delete();
    //         }

    //         // CREATE NEW CART
    //         $cart = Cart::create([
    //             'product_id'      => $offer->product_id,
    //             'vendor_id'       => $vendorId,
    //             'buyer_id'        => $buyer->id,
    //             'quantity'        => $quantity,
    //             'attributes'      => $offer->attributes,
    //             'price'           => $lineSubtotal,
    //             'delivery_charge' => $offerDelivery,
    //             'status'          => 'active',
    //         ]);

    //         // UPDATE OFFER STATUS
    //         $offer->status = 'accepted';
    //         $offer->save();

    //         return ResponseHelper::Out('success', 'Offer added to cart successfully', $cart, 200);
    //     } catch (Exception $e) {
    //         return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
    //     }
    // }
    //============================old offer to cart end=========================
    // Delete Cart
    public function checkout(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $buyer = Buyer::where('user_id', $request->header('id'))->first();
            // get cart data by login buyer
            $cartItems = Cart::where('buyer_id', $buyer->id)->where('status', 'active')->get();
            $totalAmount = 0;

            foreach ($cartItems as $item) {
                // You can calculate total price here (quantity * price)
                $totalAmount +=  floatval($item->price);
                // Mark cart item as checked_out
                $item->update(['status' => 'checked_out']);
            }
            return ResponseHelper::Out(
                'success',
                'Cart Checkout successfully',
                [
                    'total_amount' => $totalAmount,
                    'items_count' => $cartItems->count()
                ],
                200
            );
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
