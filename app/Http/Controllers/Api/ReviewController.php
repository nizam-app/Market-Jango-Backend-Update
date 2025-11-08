<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    // Get All Wishlists for Logged-in Buyer
    public function orderItems(Request $request): JsonResponse
    {
        try {
            $user_id = $request->header('id');

            $invoices = Invoice::where('buyer_id', $user_id)
                ->with([
                    'items.product:id,name,thumbnail,price,vendor_id',
                    'items.rating:id,invoice_item_id,rating,review'
                ])
                ->select(['id', 'buyer_id', 'total_amount', 'created_at'])
                ->get();

            // map করে is_rated flag যোগ করা
            $data = $invoices->map(function ($invoice) {
                $invoice->items->transform(function ($item) {
                    $item->is_rated = $item->rating ? true : false;
                    return $item;
                });
                return $invoice;
            });

            return ResponseHelper::Out('success', 'Order items fetched', $data, 200);

        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    public function storeReview(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'invoice_item_id' => 'required|exists:invoice_items,id',
                'rating' => 'required|numeric|min:1|max:5',
                'review' => 'nullable|string',
            ]);

            $user_id = $request->header('id');

            // invoice_item থেকে product ও vendor বের করা
            $item = InvoiceItem::with('product')->find($request->invoice_item_id);

            if (!$item) {
                return ResponseHelper::Out('failed', 'Invoice item not found', null, 404);
            }

            $product = $item->product;
            $vendor_id = $product->vendor_id;

            // আগেই রিভিউ দিয়েছে কিনা চেক করা
            $existing = Review::where('user_id', $user_id)
                ->where('invoice_item_id', $item->id)
                ->first();

            if ($existing) {
                return ResponseHelper::Out('failed', 'You already rated this item', null, 409);
            }

            // রেটিং সংরক্ষণ করা
            $rating = new Review();
            $rating->user_id = $user_id;
            $rating->vendor_id = $vendor_id;
            $rating->product_id = $product->id;
            $rating->invoice_id = $item->invoice_id;
            $rating->invoice_item_id = $item->id;
            $rating->rating = $request->rating;
            $rating->review = $request->review;
            $rating->save();

            // vendor এর avg rating আপডেট করা
            $avgRating = Rating::where('vendor_id', $vendor_id)->avg('rating');
            Vendor::where('id', $vendor_id)->update(['avg_rating' => round($avgRating, 2)]);

            return ResponseHelper::Out('success', 'Review submitted successfully', $rating, 201);

        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

//    public function index(Request $request): JsonResponse
//    {
//        try {
//            $buyer = Buyer::where('user_id', $request->header('id'))->first();
//            if (!$buyer) {
//                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
//            }
//            $review = Review::where('buyer_id', $buyer->id)->get();
//            if (!$review) {
//                return ResponseHelper::Out('failed', 'review list not found', null, 20);
//            }
//            return ResponseHelper::Out('success', 'All review successfully fetched', $review, 200);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }

    // Store Review
//    public function store(Request $request): \Illuminate\Http\JsonResponse
//    {
//        try {
//            $validator = Validator::make($request->all(), [
//                'product_id' => 'required',
//            ]);
//            if ($validator->fails()) {
//                return ResponseHelper::Out('failed', 'Validation exception', $validator->errors()->first(), 422);
//            }
//            $buyer = Buyer::where('user_id', $request->header('id'))->first();
//            if (!$buyer) {
//                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
//            }
//            $productId =  $request->input('product_id');
//            $buyerId = $buyer->id;
//            $review = Review::updateOrCreate(
//                [
//                    'product_id' => $productId,
//                    'buyer_id'   => $buyerId,
//                ],
//                [
//                    'description' => $request->input('description'),
//                    'rating'      => $request->input('rating'),
//                ]
//            );
//            return ResponseHelper::Out('success', 'Product added to Review', $review, 201);
//        } catch (ValidationException $e) {
//            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }
    // Delete Wishlist
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $buyer = Buyer::where('user_id', $request->header('id'))->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Buyer not found', null, 404);
            }
            $review = Review::where('id', $id)
                ->where('buyer_id', $buyer->id)
                ->first();
            if (!$review) {
                return ResponseHelper::Out('failed', 'Review not found', null, 404);
            }
            $review->delete();
            return ResponseHelper::Out('success', 'Review successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
