<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wishlist;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    // Get All reviews for Logged-in Buyer
    public function buyerReview(Request $request): JsonResponse
    {
        try {
            $user_id = $request->header('id');
            $reviews = Review::where('user_id', $user_id)
                ->with([
                  'user','product','vendor','driver'
                ])
                ->get();
            if ($reviews->isEmpty()) {
                return ResponseHelper::Out('failed', 'Reviews not found', null, 404);
            }
            return ResponseHelper::Out('success', 'Reviews fetched', $reviews, 200);

        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //crate review
    public function store(Request $request, $id): JsonResponse
    {
        try {
            $user_id = $request->header('id');
            $buyer = User::where('id', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $invoice = Invoice::where('user_id', $user_id)->first();
            $invoiceId=$invoice->id;
            $item = InvoiceItem::where('id', $id)
                ->where('invoice_id', $invoiceId)
            ->with(['product', 'vendor', 'invoice'])->first();
            if (!$item) {
                return ResponseHelper::Out('failed', 'Invoice item not found', null, 404);
            }
            $productId = $item->product->id;
            $vendorId = $item->vendor->id;
            $existing = Review::where('user_id', $user_id)
                ->where('invoice_item_id', $item->id)
                ->first();

            if ($existing) {
                return ResponseHelper::Out('success', 'You already rated this item', null, 200);
            }
            $review = Review::create([
                'review' => $request->input('review'),
                'rating' => $request->input('rating'),
                'user_id' => $user_id,
                'invoice_id' => $invoiceId,
                'invoice_item_id' => $item->id,
                'vendor_id' => $vendorId,
                'product_id' => $productId,

            ]);

            // vendor এর avg rating আপডেট করা
            $avgRating = Review::where('vendor_id', $vendorId)->avg('rating');
            Vendor::where('id', $vendorId)->update(['avg_rating' => round($avgRating, 2)]);

            return ResponseHelper::Out('success', 'Review submitted successfully', $review, 201);

        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Delete review
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
