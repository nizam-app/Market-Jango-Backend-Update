<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Models\Buyer;
use App\Models\Cart;
use App\Models\DeliveryCharge;
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
            // get cart data by login buyer
            $carts = Cart::where('buyer_id', $buyerId->id)
                ->where('status', 'active')
                ->with(['product', 'vendor', 'buyer'])
                ->select('quantity', 'delivery_charge', 'color', 'size', 'price', 'product_id', 'buyer_id', 'vendor_id','status')
                ->get();
            if($carts->isEmpty()){
                return ResponseHelper::Out('success', 'Cart not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All carts successfully fetched', $carts, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'product_id' => 'nullable|exists:products,id',
                'color' => 'nullable|string|max:20',
                'size' => 'nullable|string|max:20',
                'action' => 'nullable|in:increase,decrease'
            ]);
            if ($validator->fails()) {
                return ResponseHelper::Out('failed', 'Validation exception', $validator->errors()->first(), 422);
            }
            $userId = $request->header('id');
            $buyer = Buyer::where('user_id', $userId)->select('id')->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer Not found', null, 404);
            }
            $productId = $request->input('product_id');
            $product = Product::where('id', $productId)->first();
            if (!$product) {
                return ResponseHelper::Out('failed', 'Product Not found', null, 422);
            }
            $vendorId = $product->vendor_id;
            $productQty = $request->input('quantity');
            $updateProductQty = 1;
            $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
                ->where('quantity', '<=', $productQty)
                ->orderBy('quantity', 'desc')
                ->first();
            $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;
            $unitPrice = $product->regular_price;
            $action = $request->input('action');
            $totalPrice = $unitPrice * $productQty;
            // Create new cart item
            $cart = Cart::where('product_id', $productId)
                ->where('buyer_id', $buyer->id)
                ->first();

            if ($cart) {
                if ($action === 'increase') {
                    $newQty = $cart->quantity + $updateProductQty;
                } else {
                    $newQty = $cart->quantity - $updateProductQty;
                }

                // Recalculate delivery charge based on total quantity
                $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
                    ->where('quantity', '<=', $newQty)
                    ->orderBy('quantity', 'desc')
                    ->first();

                $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;

                // Update cart
                $cart->update([
                    'quantity' => $newQty,
                    'price' => (float)$product->regular_price * $newQty,
                    'delivery_charge' => $deliveryChargeAmount,
                ]);
                return ResponseHelper::Out('success', 'Cart updated successfully', $cart, 200);
            } else {
                if ($action === 'decrease') {
                    return ResponseHelper::Out('failed', 'Item not found in cart to decrease', null, 404);
                }

                $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
                    ->where('quantity', '<=', $productQty)
                    ->orderBy('quantity', 'desc')
                    ->first();

                $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;

                $cart = Cart::create([
                    'product_id' => $productId,
                    'vendor_id' => $vendorId,
                    'buyer_id' => $buyer->id,
                    'quantity' => $productQty,
                    'color' => $request->input('color'),
                    'size' => $request->input('size'),
                    'price' => $product->regular_price * $productQty,
                    'delivery_charge' => $deliveryChargeAmount,
                    'status' => 'active',
                ]);

                return ResponseHelper::Out('success', 'Cart item added successfully', $cart, 201);
            }
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //store cart
//    public function store(Request $request): JsonResponse
//    {
//        try {
//            // Validation
//            $validator = Validator::make($request->all(), [
//                'product_id' => 'nullable|exists:products,id',
//                'color' => 'required|string|max:20',
//                'size' => 'required|string|max:20',
//            ]);
//            if ($validator->fails()) {
//                return ResponseHelper::Out('failed', 'Validation exception', $validator->errors()->first(), 422);
//            }
//            $userId = $request->header('id');
//            $buyer = Buyer::where('user_id', $userId)->select('id')->first();
//            if (!$buyer) {
//                return ResponseHelper::Out('failed', 'Buyer Not found', null, 404);
//            }
//            $productId = $request->input('product_id');
//            $product = Product::where('id', $productId)->first();
//            if (!$product) {
//                return ResponseHelper::Out('failed', 'Product Not found', null, 422);
//            }
//            $vendorId = $product->vendor_id;
//            $productQty = $request->input('quantity');
//            $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
//                ->where('quantity', '<=', $productQty)
//                ->orderBy('quantity', 'desc')
//                ->first();
//            $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;
//            $unitPrice = $product->regular_price;
//            $totalPrice = $unitPrice * $productQty;
//            // Create new cart item
//            $cart = Cart::where('product_id', $productId)
//                ->where('buyer_id', $buyer->id)
//                ->first();
//
//            if ($cart) {
//                // Total quantity after update
//                $newTotalQty = $cart->quantity + $productQty;
//
//                // Recalculate delivery charge based on total quantity
//                $deliveryCharge = DeliveryCharge::where('vendor_id', $vendorId)
//                    ->where('quantity', '<=', $newTotalQty)
//                    ->orderBy('quantity', 'desc')
//                    ->first();
//
//                $deliveryChargeAmount = $deliveryCharge ? $deliveryCharge->delivery_charge : 0;
//
//                // Update existing
//                $cart->quantity = $newTotalQty;
//                $cart->price = (float)$product->regular_price * $cart->quantity;
//                $cart->delivery_charge = $deliveryChargeAmount;
//                $cart->save();
//            } else {
//                $cart = Cart::create([
//                    'product_id' => $productId,
//                    'vendor_id' => $vendorId,
//                    'buyer_id' => $buyer->id,
//                    'quantity' => $productQty,
//                    'color' => $request->input('color'),
//                    'size' => $request->input('size'),
//                    'price' => $totalPrice,
//                    'delivery_charge' => $deliveryChargeAmount,
//                    'status' => 'active',
//                ]);
//            }
//            return ResponseHelper::Out('success', 'Cart successfully created', $cart, 201);
//        } catch (ValidationException $e) {
//            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
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
