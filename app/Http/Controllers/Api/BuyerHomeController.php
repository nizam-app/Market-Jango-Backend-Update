<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuyerHomeController extends Controller
{
    //popular product
    public function popularProducts(Request $request, $id)
    {
        $vendorId = (int) $id;
        if (!$vendorId) {
            return response()->json(['status'=>'failed','message'=>'vendor_id is required'], 422);
        }
        $top = InvoiceItem::query()
            ->where('vendor_id', $vendorId)
            ->selectRaw('product_id, SUM(quantity) as sold_qty')
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->limit(30)
            ->get();
        if ($top->isEmpty()) {
            return response()->json(['status' => 'success', 'message' => 'No sales for this vendor'], 200);
        }
        $productIds = $top->pluck('product_id')->all();
        $products = Product::whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
        $data = $top->map(function ($row) use ($products) {
            return [
                'product'  => $products->get($row->product_id), // null হলে শুধু id দেখাতে পারো
                'sold_qty' => (int) $row->sold_qty,
            ];
        });
        return ResponseHelper::Out('success', 'Popular Product fetched successfully', $data, 200);
    }
    public function vendorFirstProduct(): JsonResponse
    {
        $vendors = Vendor::with(['user', 'categories.products'])
            ->inRandomOrder()
            ->take(10)
            ->get();
        $data = $vendors->map(function ($vendor) {
            $firstCategory = $vendor->categories->first();
            $firstProduct = $firstCategory ? $firstCategory->products->first() : null;
            return [
                'vendor_id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'vendor_name' => $vendor->user ? $vendor->user->name : null,
                'vendor_image' =>  $vendor->user->image,
                'category' => $firstCategory ? [
                    'id' => $firstCategory->id,
                    'name' => $firstCategory->name,
                ] : null,
                'product' => $firstProduct ? [
                    'id' => $firstProduct->id,
                    'discount' => $firstProduct->discount,
                    'name' => $firstProduct->name,
                    'regular_price' => $firstProduct->regular_price,
                    'sell_price' => $firstProduct->sell_price,
                    'image' => $firstProduct->image ?? null,
                ] : null,
            ];
        });
        return ResponseHelper::Out('success', 'Product fetched successfully', $data, 200);
    }
    public function vendorListId(Request $request, $id): JsonResponse
    {


        // get all selected vendor
        $vendor = Vendor::where('id', $id)
            ->with([
            'user',         // যদি vendor -> user থাকে
            'products',
            'location',
            'ratings'       // যদি review/rating model থাকে
        ]);

        if (!$vendor) {
            return ResponseHelper::Out('success', 'No vendors found for this location', null, 200);
        }
        $vendors = Vendor::orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [$id]) ->with([
            'user'
        ])
            ->get();
        return ResponseHelper::Out('success', 'Vendor fetched successfully', $vendors, 200);
    }
    public function productFilter(Request $request)
    {
        try {
            $request->validate([
                'location' => 'required|string',
            ]);
            $location   = $request->input('location');
            $categoryName = $request->input('category');
            $vendors = Vendor::where('address', 'LIKE', "%{$location}%")->pluck('id');
            if ($vendors->isEmpty()) {
                return ResponseHelper::Out('success', 'No vendors found for this location', null, 200);
            }
            $categories = Category::whereIn('vendor_id', $vendors)
                ->where('name','LIKE', "%{$categoryName}%" )
                ->pluck('id');
            if ($categories->isEmpty()) {
                return ResponseHelper::Out('success', 'No categories found for selected vendors', null, 200);
            }
            $products = Product::whereIn('category_id', $categories)->with([
                'vendor:id,user_id',
                'vendor.user:id,name',
                'vendor.reviews:id,vendor_id,review,rating',
                'category:id,name',
                'images:id,image_path,public_id,product_id'])
                ->select(['id','name','description','regular_price','sell_price','image','vendor_id','category_id', 'color', 'size', 'discount'])
                ->latest()
                ->paginate(20);
            return ResponseHelper::Out('success', 'Products fetched successfully', ["total"=>$products->count(), 'data' => $products], 200);
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
                    'vendor:id,user_id',
                    'vendor.user:id,name',
                    'vendor.reviews:id,vendor_id,review,rating',
                    'category:id,name',
                    'images:id,image_path,public_id,product_id'
                ])
                ->select([
                    'id',
                    'name',
                    'description',
                    'regular_price',
                    'image',
                    'size',
                    'color',
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
    //vendor by product
    public function vendorByProduct(Request $request, $id): JsonResponse
    {
        try {
            // vendor product
            $products = Product::where('vendor_id', $id)
                ->with(['category', 'images:id,image_path,product_id'])
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
    //vendor by category by product
    public function vendorCategoryByProduct(Request $request, $id): JsonResponse
    {
        try {
            $vendor = Vendor::where('id', $id)->with(['user', 'user.reviews'])
                ->first();
            $categories = Category::where('vendor_id', $id)
                ->where('status', 'active')
            ->with([
                'products' => function ($query) {
                    $query->where('is_active', 1)
                        ->select('id','name', 'description', 'regular_price', 'sell_price','discount','image','color', 'size', 'vendor_id','remark', 'category_id')
                        ->with([
                            'images:id,product_id,image_path,public_id',
                            'vendor:id,country,address,business_name,business_type,user_id',
                            'vendor.user:id,name,image,email,phone,language',
                        ]);
                },
                'categoryImages:id,category_id,image_path,public_id',
                'vendor.user:id,name,image',
                'vendor.reviews',
            ])
            ->paginate(10);
            if ($categories->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no products', [], 200);
            }
            return ResponseHelper::Out('success', 'Products found', ['all' => $categories->count(), 'categories' => $categories, 'vendor'=>$vendor], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Get All invoice
    public function userInvoice(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $buyerId = Buyer::where('user_id',$request->header('id'))->select('id')->first();
            if (!$buyerId) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $carts = Invoice::where('buyer_id', $buyerId->id)
                ->where('status', 'success')
                ->with(['product', 'vendor', 'buyer'])
                ->select('quantity', 'delivery_charge', 'color', 'size', 'price', 'product_id', 'buyer_id', 'vendor_id','status')
                ->get();
            if($carts->isEmpty()){
                return ResponseHelper::Out('success', 'Cart not found', null, 200);
            }
            foreach ($carts as $cartItem) {
                $total=$cartItem->price+$cartItem->delivery_charge;
            }
            return ResponseHelper::Out('success', 'All carts successfully fetched', ["cartData"=>$carts, "totalPrice"=> $total], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
