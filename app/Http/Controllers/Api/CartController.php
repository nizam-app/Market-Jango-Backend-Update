<?php

namespace App\Http\Controllers\Api;

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
use Exception;

class CartController extends Controller
{
    // Get All Carts
    public function index(Request $request): JsonResponse
    {
        try {

            // get login buyer
            $buyerId = Buyer::where('user_id',$request->header('id'))->select('id')->first();
            if (!$buyerId) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $total = 0;
            // get cart data by login buyer
            $carts = Cart::where('buyer_id', $buyerId->id)
                ->where('status', 'active')
                ->with(['product', 'vendor', 'buyer'])
                ->select('id','quantity', 'delivery_charge', 'color', 'size', 'price', 'product_id', 'buyer_id', 'vendor_id','status')
                ->get();
            if($carts->isEmpty()){
                return ResponseHelper::Out('success', 'Cart not found', null, 200);
            }
            foreach ($carts as $cartItem) {
                $total = $total + $cartItem->price + $cartItem->delivery_charge;
            }
            return ResponseHelper::Out('success', 'All carts successfully fetched', [$carts,"total"=>$total], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'color' => 'nullable|string|max:20',
                'size' => 'nullable|string|max:20',
                'quantity' => 'nullable|integer|min:1',
                'action' => 'nullable|in:increase,decrease'
            ]);

            if ($validator->fails()) {
                return ResponseHelper::Out('failed', 'Validation exception', $validator->errors()->first(), 422);
            }

            $userId = $request->header('id');
            $buyer = Buyer::where('user_id', $userId)->select('id')->first();

            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }

            $productId = $request->input('product_id');
            $product = Product::find($productId);

            if (!$product) {
                return ResponseHelper::Out('failed', 'Product not found', null, 422);
            }

            $vendorId = $product->vendor_id;
            $requestedQty = $request->input('quantity', 1);
            $action = $request->input('action');

            $cart = Cart::where('product_id', $productId)
                ->where('buyer_id', $buyer->id)
                ->first();

            $currentStock = $product->stock;

            // EXISTING CART
            if ($cart) {

                if ($action === 'increase') {
                    $newQty = $cart->quantity + 1;

                    //STOCK CHECK
                    if ($newQty > $currentStock) {
                        return ResponseHelper::Out('failed', 'Stock not available', null, 400);
                    }
                } elseif ($action === 'decrease') {
                    $newQty = max($cart->quantity - 1, 1);
                } else {
                    $newQty = $cart->quantity + $requestedQty;
                    if ($newQty > $currentStock) {
                        return ResponseHelper::Out('failed', 'Stock not available', null, 400);
                    }
                }

                // DELIVERY CHARGE CALC
                $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
                    ->where('product_id', $productId)
                    ->where('quantity', '<=', $newQty)
                    ->orderBy('quantity', 'desc')
                    ->first();

                $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;

                // UPDATE CART
                $cart->update([
                    'quantity' => $newQty,
                    'price' => (float)$product->sell_price * $newQty,
                    'delivery_charge' => $deliveryChargeAmount,
                ]);
                return ResponseHelper::Out('success', 'Cart updated successfully', $cart, 200);
            }

            // NEW CART ITEM
            if ($requestedQty > $currentStock) {
                return ResponseHelper::Out('failed', 'Not enough stock available', null, 400);
            }

            $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
                ->where('product_id', $productId)
                ->where('quantity', '<=', $requestedQty)
                ->orderBy('quantity', 'desc')
                ->first();

            $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;

            $cart = Cart::create([
                'product_id' => $productId,
                'vendor_id' => $vendorId,
                'buyer_id' => $buyer->id,
                'quantity' => $requestedQty,
                'color' => $request->input('color'),
                'size' => $request->input('size'),
                'price' => (float)$product->sell_price * $requestedQty,
                'delivery_charge' => $deliveryChargeAmount,
                'status' => 'active',
            ]);
            // SEND NOTIFICATION
            $senderId=$userId;
            $message = 'You have add to cart';
            $name=$buyer->name;
            NotificationHelper::sendNotification($senderId,$senderId,$message, $name );
            return ResponseHelper::Out('success', 'Cart item added successfully', $cart, 201);

        } catch (Exception $e) {
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

            $buyer = Buyer::where('user_id', $userId)->select('id')->first();
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
                return ResponseHelper::Out('failed', 'This offer is already ' . $offer->status . '.', null, 400);
            }

            $product = Product::find($offer->product_id);
            if (!$product) {
                return ResponseHelper::Out('failed', 'Product not found', null, 422);
            }

            $quantity = $offer->quantity ?? 1;

            // ✅ STOCK CHECK
            if ($product->stock < $quantity) {
                return ResponseHelper::Out('failed', 'Not enough stock available', null, 400);
            }

            $vendorId = $product->vendor_id;
            $offerPrice     =  $offer->sale_price;
            $offerDelivery  =  $offer->delivery_charge;
            $lineSubtotal   = $offerPrice * $quantity;

            // DELETE OLD CART IF EXIST
            $cart = Cart::where('product_id', $offer->product_id)
                ->where('buyer_id', $buyer->id)
                ->first();
            if ($cart) {
                $cart->delete();
            }

            // CREATE NEW CART
            $cart = Cart::create([
                'product_id'      => $offer->product_id,
                'vendor_id'       => $vendorId,
                'buyer_id'        => $buyer->id,
                'quantity'        => $quantity,
                'color'           => $offer->color,
                'size'            => $offer->size,
                'price'           => $lineSubtotal,
                'delivery_charge' => $offerDelivery,
                'status'          => 'active',
            ]);

            // UPDATE OFFER STATUS
            $offer->status = 'accepted';
            $offer->save();

            return ResponseHelper::Out('success', 'Offer added to cart successfully', $cart, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

//    public function store(Request $request): JsonResponse
//    {
//        try {
//            // Validation
//            $validator = Validator::make($request->all(), [
//                'product_id' => 'required|exists:products,id',
//                'color' => 'nullable|string|max:20',
//                'size' => 'nullable|string|max:20',
//                'quantity' => 'nullable|integer|min:1',
//                'action' => 'nullable|in:increase,decrease'
//            ]);
//
//            if ($validator->fails()) {
//                return ResponseHelper::Out('failed', 'Validation exception', $validator->errors()->first(), 422);
//            }
//
//            $userId = $request->header('id');
//            $buyer = Buyer::where('user_id', $userId)->select('id')->first();
//
//            if (!$buyer) {
//                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
//            }
//
//            $productId = $request->input('product_id');
//            $product = Product::find($productId);
//
//            if (!$product) {
//                return ResponseHelper::Out('failed', 'Product not found', null, 422);
//            }
//
//            $vendorId = $product->vendor_id;
//            $requestedQty = $request->input('quantity', 1);
//            $action = $request->input('action');
//            $updateQty = 1;
//
//            // ✅ Check if cart item already exists
//            $cart = Cart::where('product_id', $productId)
//                ->where('buyer_id', $buyer->id)
//                ->first();
//
//            if ($cart) {
//                // Increase / Decrease logic
//                if ($action === 'increase') {
//                    $newQty = $cart->quantity + $updateQty;
//                } elseif ($action === 'decrease') {
//                    $newQty = max($cart->quantity - $updateQty, 1);
//                } else {
//                    $newQty = $cart->quantity;
//                }
//
//                // ✅ Find correct delivery charge (vendor + product wise)
//                $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
//                    ->where('product_id', $productId)
//                    ->where('quantity', '<=', $newQty)
//                    ->orderBy('quantity', 'desc')
//                    ->first();
//
//                $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0; // column name = charge
//
//                // 🧾 Update cart
//                $cart->update([
//                    'quantity' => $newQty,
//                    'price' => (float) $product->regular_price * $newQty,
//                    'delivery_charge' => $deliveryChargeAmount,
//                ]);
//                return ResponseHelper::Out('success', 'Cart updated successfully', $cart, 200);
//            }
//            else {
//                // ❌ If trying to decrease non-existing item
//                if ($action === 'decrease') {
//                    return ResponseHelper::Out('failed', 'Item not found in cart to decrease', null, 404);
//                }
//
//                // ✅ Calculate delivery charge for new item (vendor + product wise)
//                $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
//                    ->where('product_id', $productId)
//                    ->where('quantity', '<=', $requestedQty)
//                    ->orderBy('quantity', 'desc')
//                    ->first();
//                $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;
//
//                // 🛒 Create new cart item
//                $cart = Cart::create([
//                    'product_id' => $productId,
//                    'vendor_id' => $vendorId,
//                    'buyer_id' => $buyer->id,
//                    'quantity' => $requestedQty,
//                    'color' => $request->input('color'),
//                    'size' => $request->input('size'),
//                    'price' => (float) $product->regular_price * $requestedQty,
//                    'delivery_charge' => $deliveryChargeAmount,
//                    'status' => 'active',
//                ]);
//
//                return ResponseHelper::Out('success', 'Cart item added successfully', $cart, 201);
//            }
//        } catch (ValidationException $e) {
//            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }

//    public function addOfferToCart(Request $request, $offerId): JsonResponse
//    {
//        try {
//            $userId = $request->header('id');
//            if (!$userId) {
//                return ResponseHelper::Out('failed', 'User ID header missing', null, 400);
//            }
//            $buyer = Buyer::where('user_id', $userId)->select('id')->first();
//            if (!$buyer) {
//                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
//            }
//            $offer = Offer::find($offerId);
//            if (!$offer) {
//                return ResponseHelper::Out('failed', 'Offer not found', null, 404);
//            }
//
//            // CHECK OFFER USER
//            if ((int) $offer->receiver_id !== (int) $userId) {
//                return ResponseHelper::Out(
//                    'failed',
//                    'This offer is not for your account.',
//                    null,
//                    403
//                );
//            }
//
//            // IF ONE TIME ACCEPT OFFER THAN DON'T ACCEPT SAME OFFER
//            if ($offer->status !== 'pending') {
//                return ResponseHelper::Out(
//                    'failed',
//                    'This offer is already ' . $offer->status . '.',
//                    null,
//                    400
//                );
//            }
//            // GET OFFER PRODUCT
//            $product = Product::find($offer->product_id);
//            if (!$product) {
//                return ResponseHelper::Out('failed', 'Product not found', null, 422);
//            }
//            $vendorId = $product->vendor_id;
//            // CALCULATE PRICE
//            $quantity       = $offer->quantity ?? 1;
//            $offerPrice     =  $offer->sale_price;
//            $offerDelivery  =  $offer->delivery_charge;
//            $lineSubtotal   = $offerPrice * $quantity;
//            // GET IF EXISTING PRODUCT
//            $cart = Cart::where('product_id', $offer->product_id)
//                ->where('buyer_id', $buyer->id)
//                ->first();
//            // EXISTING CART DELETE
//            if ($cart) {
//                $cart->delete();
//            }
//            //CREATE NEW CART
//            $cart = Cart::create([
//                'product_id'      => $offer->product_id,
//                'vendor_id'       => $vendorId,
//                'buyer_id'        => $buyer->id,
//                'quantity'        => $quantity,
//                'color'           => $offer->color,
//                'size'            => $offer->size,
//                'price'           => $lineSubtotal,
//                'delivery_charge' => $offerDelivery,
//                'status'          => 'active',
//            ]);
//            // OFFER STATUS UPDATE
//            $offer->status = 'accepted';
//            $offer->save();
//            return ResponseHelper::Out(
//                'success',
//                'Offer added to cart successfully',
//                $cart,
//                200
//            );
//        } catch (Exception $e) {
//            return ResponseHelper::Out(
//                'failed',
//                'Something went wrong',
//                $e->getMessage(),
//                500
//            );
//        }
//    }
    // Delete Cart
    public function checkout(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $buyer = Buyer::where('user_id',$request->header('id'))->first();
            // get cart data by login buyer
            $cartItems = Cart::where('buyer_id', $buyer->id)->where('status', 'active')->get();
            $totalAmount = 0;

            foreach ($cartItems as $item) {
                // You can calculate total price here (quantity * price)
                $totalAmount +=  floatval($item->price);
                // Mark cart item as checked_out
                $item->update(['status' => 'checked_out']);
            }
            return ResponseHelper::Out('success', 'Cart Checkout successfully',
                [
                    'total_amount' => $totalAmount,
                    'items_count' => $cartItems->count()
                ], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
