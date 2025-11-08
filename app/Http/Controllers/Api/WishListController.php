<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wishlist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WishListController extends Controller
{
    // Get All Wishlists for Logged-in Buyer
    public function index(Request $request): JsonResponse
    {
        try {
            $buyer = User::where('id',$request->header('id'))
                ->with('buyer')
                ->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }
            $wishlists = WishList::where('buyer_id', $buyer->buyer->id)
                ->get();
            if (!$wishlists) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }
            return ResponseHelper::Out('success', 'All wishlists successfully fetched', $wishlists, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // Store Wishlist
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);

            $user = User::where('id',$request->header('id'))
                ->with('buyer')
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }
            $productId =  $request->input('product_id');
            $buyerId = $user->buyer->id;
            $wishlist = WishList::updateOrCreate(
                [
                    'product_id' => $productId,
                    'buyer_id'   => $buyerId
                ]
            );
            return ResponseHelper::Out('success', 'Product added to wishlist', $wishlist, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Delete Wishlist
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $buyer = User::where('id',$request->header('id'))
                ->with('buyer')
                ->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }
            $wishlist = WishList::where('id', $id)
                ->where('buyer_id', $buyer->id)
                ->first();
            if (!$wishlist) {
                return ResponseHelper::Out('failed', 'Wishlist not found', null, 404);
            }
            $wishlist->delete();
            return ResponseHelper::Out('success', 'Wishlist successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
