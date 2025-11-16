<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Dotenv\Validator;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorHomePageController extends Controller
{
//screen 1
    //vendor details
    public function show(Request $request): JsonResponse
    {
        try {
            $vendor = User::where('id', $request->header('id'))->select(['id', 'name', 'image', 'public_id'])->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            return ResponseHelper::Out('success', 'All user successfully fetched', $vendor, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //search vendor product
    public function productSearchByVendor(Request $request): JsonResponse
    {
        try {
            $vendor = Vendor::where('user_id', $request->header('id'))
                ->select('id', 'user_id')
                ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $request->validate([
                'query' => 'required|string|min:1'
            ]);
            $query = trim($request->input('query'));
            $products = Product::where('vendor_id', $vendor->id)
                ->where(function ($q) use ($query) {
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
                ->limit(20) // limit results for faster response
                ->paginate(10);

            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'No products found', null, 200);
            }
            return ResponseHelper::Out('success', 'Products found', $products, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //product by vendor
    public function vendorProduct(Request $request): JsonResponse
    {
        try {
            // Vendor fetch
            $vendor = Vendor::where('user_id', $request->header('id'))
                ->select(['id', 'user_id'])
                ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // vendor product
            $products = Product::where('vendor_id', $vendor->id)
                ->with(['category:id,name,description', 'images:id,image_path,product_id'])
                ->select(['id', 'name', 'description', 'regular_price', 'sell_price', 'image', 'vendor_id', 'category_id', 'color', 'size'])
                ->latest()
                ->paginate(10);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no products', [], 200);
            }
            return ResponseHelper::Out('success', 'Products found', ['all' => $products->count(), 'products' => $products], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //driver search
    public function driverSearch(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'required|string|max:100'
            ]);
            $query = $request->input('query');

            $drivers = User::where('user_type', 'driver')->where('name', 'like', "%{$query}%")
                ->with(['driver'])
            ->get();

            if (!$drivers) {
                return ResponseHelper::Out('success', 'No driver found', [], 200);
            }
            return ResponseHelper::Out('success', 'Driver data fetched successfully', $drivers, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Vendor Pending Order
    public function vendorPendingOrder(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $vendor = Vendor::where('user_id', '=', $user_id)->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get order item  data by login vendor
            $invoices = InvoiceItem::where('vendor_id', $vendor->id)
                ->where('status', 'Pending')
                ->with(['invoice', 'product','driver'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function vendorAssignedOrder(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $vendor = Vendor::where('user_id', '=', $user_id)->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get order item  data by login vendor
            $invoices = InvoiceItem::where('vendor_id', $vendor->id)
                ->where('status', 'AssignedOrder')
                ->with(['invoice', 'product', 'driver', 'driver.user'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'assign order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function vendorAllOrder(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $vendor = Vendor::where('user_id', '=', $user_id)->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get order item  data by login vendor
            $invoices = InvoiceItem::where('vendor_id', $vendor->id)
                ->with(['invoice', 'product', 'driver', 'driver.user'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'Complete order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All complete order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function vendorCanceledOrder(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $vendor = Vendor::where('user_id', '=', $user_id)->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get order item  data by login vendor
            $invoices = InvoiceItem::where('vendor_id', $vendor->id)
                ->where('status', 'Cancel')
                ->with(['invoice', 'product','driver','driver.user'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

}
