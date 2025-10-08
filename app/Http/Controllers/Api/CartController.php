<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Models\Buyer;
use App\Models\Cart;
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
            $buyer = Buyer::where('user_id',$request->header('id'))->first();
            // get cart data by login buyer
            $carts = Cart::where('buyer_id', $buyer->id)->where('status', 'active')->with(['product', 'product.vendor', 'buyer'])->get();
            return ResponseHelper::Out('success', 'All carts successfully fetched', $carts, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Store Cart
    public function store(Request $request): JsonResponse
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'buyer_id' => 'nullable|exists:users,id',
                'color' => 'required|string|max:20',
                'size' => 'required|string|max:20',
                'price' => 'nullable|string|max:20',
            ]);
            if ($validator->fails()) {
                return ResponseHelper::Out('failed', 'Validation exception', $validator->errors()->first(), 422);
            }
            $userId = $request->header('id');
            $buyer = Buyer::where('user_id', $userId)->first();
            $product = Product::find($request->product_id);
            if (!$product) {
                return ResponseHelper::Out('failed', 'Product Not found', $validator->errors()->first(), 422);
            }
            $unitPrice = $product->current_price;
            $totalPrice = $unitPrice * $request->quantity;
            // Check if product already in cart
            $existing = Cart::where('product_id', $request->product_id)
                ->where('buyer_id', $buyer->id)
                ->where('status', 'active')
                ->first();
            if ($existing) {
                $existing->quantity += $request->quantity;
                $existing->price = $unitPrice * $existing->quantity;
                $existing->save();
                return ResponseHelper::Out('success', 'Cart updated successfully', $existing, 200);
            }

            // Create new cart item
            $cart = Cart::create([
                'product_id' => $request->input('product_id'),
                'buyer_id' => $buyer->id,
                'quantity' => $request->input('quantity'),
                'color' => $request->input('color'),
                'size' => $request->input('size'),
                'price' => $totalPrice,
                'vendor_id' => 1,
                'status' => 'active',
            ]);
            return ResponseHelper::Out('success', 'Cart successfully created', $cart, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
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
