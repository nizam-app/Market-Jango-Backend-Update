<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Exception;
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
}
