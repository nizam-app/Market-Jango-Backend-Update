<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuyerHomeController extends Controller
{
    public function productFilter(Request $request)
    {
        try {
            $query = Product::with('category');
            if (
                !$request->filled('category') &&
                !$request->filled('country') &&
                !$request->filled('location')
            ) {
                return ResponseHelper::Out('success', 'Please provide at least one filter parameter', [], 200);
            }
            // Filter by Category Name
            if ($request->filled('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->category . '%');
                });
            }

            //Filter by Country dropdown → location column
            if ($request->filled('country')) {
                $query->where('location', 'LIKE', '%' . $request->country . '%');
            }

            // 🔹 Filter by Google Map location → country column
            if ($request->filled('location')) {
                $query->where('country', 'LIKE', '%' . $request->location . '%');
            }
            $products = $query->get();
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'No matching data found', $products, 200);
            }
            return ResponseHelper::Out('success', 'Filtered data retrieved successfully', ["total"=>$products->count(), 'data' => $products], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Error filtering locations', $e->getMessage(), 500);
        }
    }
    // Search Product and Store Search Value to condition
    public function productSearchByBuyer(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|min:1|max:50',
            ]);
            $userId = $request->header('id');
            $user = Buyer::where('user_id', $userId)->select('id')->first();
            if(!$user){
                return ResponseHelper::Out('failed','user not found',null, 404);
            }
            $query = trim($request->input('name'));
            //  Save search to history only if > 10 chars and only once per 24h
            if (strlen($query) >= 10) {
                $lastSearch = SearchHistory::where('user_id', $userId)
                    ->where('created_at', '>=', now()->subDay())
                    ->first();

                if (!$lastSearch) {
                    SearchHistory::create([
                        'user_id' => $userId,
                        'name' => $query,
                    ]);
                }
            }
            // search product
            $products = Product::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
                ->with([
                    'category:id,name,description',
                    'images:id,image_path,product_id'
                ])
                ->select([
                    'id',
                    'name',
                    'description',
                    'regular_price',
                    'sell_price',
                    'image',
                    'vendor_id',
                    'category_id'
                ])
                ->orderByRaw("
                CASE
                    WHEN name = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    WHEN description LIKE ? THEN 4
                    ELSE 5
                END, id ASC
            ", [
                    $query,
                    "{$query}%",
                    "%{$query}%",
                    "%{$query}%"
                ])
                ->limit(20)
                ->paginate(10);

            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'No products found', null, 200);
            }
            return ResponseHelper::Out('success', 'Products found', $products, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

}
