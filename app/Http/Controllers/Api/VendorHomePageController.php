<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceStatusLog;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductClickLog;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            // Step 1: Search users by name
            $users = User::where('name', 'like', "%{$query}%")
                ->where('user_type', 'vendor') // only vendors
                ->get();
            if ($users->isEmpty()) {
                return ResponseHelper::Out('success', 'No user found', [], 200);
            }
            // Step 2: Collect vendor addresses
            $vendorIds = $users->pluck('id')->toArray();
            $vendors = Vendor::whereIn('user_id', $vendorIds)->get();

            if ($vendors->isEmpty()) {
                return ResponseHelper::Out('success', 'No vendor found', [], 200);
            }
            $vendorAddresses = $vendors->pluck('address')->toArray();
            // Step 3: Match vendor addresses with locations table
            $locations = Location::whereIn('name', $vendorAddresses)->get();
            if ($locations->isEmpty()) {
                return ResponseHelper::Out('success', 'No locations found', [], 200);
            }
            // Step 4: Collect route_ids from locations
            $routeIds = $locations->pluck('route_id')->unique()->toArray();
            // Step 5: Fetch drivers under these routes
            $drivers = Driver::whereIn('route_id', $routeIds)->with('user')->get()->map(function($driver) {
                return [
                    // Driver table er shob data
                    'driver_id' => $driver->id,
                    'car_name' => $driver->car_name,
                    'car_model' => $driver->car_model,
                    'location' => $driver->location,
                    'price' => $driver->price,
                    'description' => $driver->description,
                    'rating' => $driver->rating,
                    'user_id' => $driver->user_id,
                    'route_id' => $driver->route_id,
                    'created_at' => $driver->created_at,
                    'updated_at' => $driver->updated_at,
                    // User table er shob relevant data
                    'user' => $driver->user ? [
                        'id' => $driver->user->id,
                        'user_type' => $driver->user->user_type,
                        'last_active_at' => $driver->user->last_active_at,
                        'name' => $driver->user->name,
                        'email' => $driver->user->email,
                        'phone' => $driver->user->phone,
                        'otp' => $driver->user->otp,
                        'phone_verified_at' => $driver->user->phone_verified_at,
                        'password' => $driver->user->password,
                        'language' => $driver->user->language,
                        'image' => $driver->user->image,
                        'public_id' => $driver->user->public_id,
                        'is_read' => $driver->user->is_read,
                        'is_active' => $driver->user->is_active,
                        'status' => $driver->user->status,
                        'fcm_token' => $driver->user->fcm_token,
                        'expires_at' => $driver->user->expires_at,
                        'invite_token' => $driver->user->invite_token,
                        'must_change_password' => $driver->user->must_change_password,
                        'remember_token' => $driver->user->remember_token,
                        'created_at' => $driver->user->created_at,
                        'updated_at' => $driver->user->updated_at,
                    ] : null
                ];
            });
            return ResponseHelper::Out('success', 'Driver data fetched successfully', $drivers, 200);

        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation error', $e->errors(), 422);
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
//                ->paginate(10);
            ->get();
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'Complete order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All complete order successfully fetched', ['data'=> $invoices], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Create Order
    function vendorInvoice(Request $request, $driver_id, $order_item_id ): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');
            $user = User::where('id', '=', $user_id)->with('vendor')->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $driver = Driver::where('id', $driver_id)->with('user')->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            $orderItem = InvoiceItem::where('id', $order_item_id)->where('vendor_id',$user->vendor->id)->with('invoice')->first();
            if (!$orderItem) {
                return ResponseHelper::Out('failed', 'Order item not found', null, 404);
            }
            $tran_id = uniqid();
            $payment_status = 'Pending';
            $currency = "USD";
            $cus_name = $user->cus_name;
            $cus_phone = $user->cus_phone;
            $total = $driver->price;
            $vat=0;
            $distance=  $orderItem->distance;
            $subtotal = $total*$distance;
            $payable = $subtotal + $vat;
            $invoice = Invoice::create([
                'cus_name' => $cus_name,
                'cus_email' => $user_email,
                'cus_phone' => $cus_phone,
                'total' => $total,
                'vat' => $vat,
                'payable' => $payable,
                'tax_ref' => $tran_id,
                'currency' => $currency,
                'payment_method' => "FW",
                'status' => $payment_status,
                'user_id' => $user_id
            ]);
            $invoiceStatusLogs = InvoiceStatusLog::create([
                'driver_id'=> $driver_id,
                'invoice_id'=> $invoice->id,
                'invoice_item_id'=> $orderItem->id
            ]);
            $paymentMethod = PaymentSystem::InitiatePayment($invoice);
            DB::commit();
            return ResponseHelper::Out('success', '', array(['paymentMethod' => $paymentMethod, 'payable' => $payable, 'vat' => $vat, 'total' => $payable]), 200);
        }catch (ValidationException $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        }
        catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('fail', 'Something went wrong', $e->getMessage(), 200);
        }
    }

    public function vendorIncome(Request $request): JsonResponse
    {
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');
            $vendor = Vendor::where('id', '=', $user_id)->with('user')->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $totalIncome = InvoiceItem::where('vendor_id',$user_id )->where('status',  'Complete')
                ->sum('sale_price');

            return ResponseHelper::Out('success', 'Total income calculated', [
                'total_income' => (float) $totalIncome
            ], 200);
        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    //total income history
    public function vendorIncomeUpdate(Request $request): JsonResponse
    {
        try {
            $vendorId = $request->header('id');
            $vendor = Vendor::where('user_id', $vendorId)->with('user')->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $days = $request->input('days', 30);
            $startDate = now()->subDays($days)->startOfDay();
            $endDate = now()->endOfDay();
            // ---------- Orders ----------
            $orders = InvoiceItem::where('vendor_id', $vendor->id)
                ->where('status', 'Complete')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            $totalRevenue = $orders->sum('sale_price');
            $totalOrders = $orders->count();

            // ---------- Clicks ----------
            $totalClicks = ProductClickLog::where('vendor_id', $vendorId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            // ---------- Conversion Rate ----------
            $conversionRate = $totalClicks > 0
                ? round(($totalOrders / $totalClicks) * 100, 2)
                : 0;
            return response()->json([
                'status' => 'success',
                'data' => [
                    'total_days'      => $days,
                    'total_revenue'   => $totalRevenue,
                    'total_orders'    => $totalOrders,
                    'total_clicks'    => $totalClicks,
                    'conversion_rate' => $conversionRate, // %
                ]
            ]);
        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //get top 10 products
    public function vendorTopProduct(Request $request): JsonResponse
    {
        try {
            // Vendor User ID from header
            $vendorUserId = $request->header('id');

            // Vendor বের করা
            $vendor = Vendor::where('user_id', $vendorUserId)->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }

            $vendorId = $vendor->id;

            // ---------- Top 10 Selling Products (Lifetime) ----------
            $topProducts = InvoiceItem::where('vendor_id', $vendorId)
                ->select(
                    'product_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('SUM(sale_price) as total_revenue')
                )
                ->groupBy('product_id')
                ->orderByDesc('total_quantity')
                ->limit(10)
                ->get();

            if ($topProducts->isEmpty()) {
                return ResponseHelper::Out('success', 'No sales found', [
                    'products' => []
                ], 200);
            }

            // product details attach
            $result = $topProducts->map(function ($item) {
                $product = Product::find($item->product_id);

                return [
                    'product_id'     => $item->product_id,
                    'name'           => $product->name ?? 'Unknown',
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue'  => (float) $item->total_revenue,
            ];
        });

            return ResponseHelper::Out('success', 'Top 10 products fetched', [
                'products' => $result
            ],200);

        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Error occurred', $e->getMessage(), 500);
        }
    }

    //weakly sell
    public function weeklySalesChart(Request $request): JsonResponse
    {
        try {
            $vendorUserId = $request->header('id');
            $vendorId = Vendor::where('user_id', $vendorUserId)->value('id');

            if (!$vendorId) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }

            // Current Week
            $currentStart = now()->startOfWeek();
            $currentEnd = now()->endOfWeek();

            // Previous Week
            $previousStart = now()->subWeek()->startOfWeek();
            $previousEnd = now()->subWeek()->endOfWeek();

            // ---------- Current Week ----------
            $current = InvoiceItem::where('vendor_id', $vendorId)
                ->where('status', 'Complete')
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->select(
                    DB::raw("DATE(created_at) as date"),
                    DB::raw("SUM(sale_price) as total")
                )
                ->groupBy('date')
                ->pluck('total', 'date');

            // ---------- Previous Week ----------
            $previous = InvoiceItem::where('vendor_id', $vendorId)
                ->where('status', 'Complete')
                ->whereBetween('created_at', [$previousStart, $previousEnd])
                ->select(
                    DB::raw("DATE(created_at) as date"),
                    DB::raw("SUM(sale_price) as total")
                )
                ->groupBy('date')
                ->pluck('total', 'date');

            // Prepare final weekly array
            $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
            $currentData = [];
            $previousData = [];

            foreach (range(0, 6) as $i) {
                $cDate = $currentStart->copy()->addDays($i)->toDateString();
                $pDate = $previousStart->copy()->addDays($i)->toDateString();

                $currentData[] = $current[$cDate] ?? 0;
                $previousData[] = $previous[$pDate] ?? 0;
            }
            return ResponseHelper::Out('success', 'Total income calculated', ['data' => [
                'days'           => $days,
                'current_period' => $currentData,
                'previous_period'=> $previousData,
            ]], 200);
        } catch (\Exception $e) {
            return ResponseHelper::Out('failed', 'Error occurred', $e->getMessage(), 500);
        }
    }


//    public function vendorTopProduct(Request $request): JsonResponse
//    {
//        try {
//            // Vendor User ID
//            $vendorUserId = $request->header('id');
//
//            // Vendor বের করা
//            $vendor = Vendor::where('user_id', $vendorUserId)->first();
//            if (!$vendor) {
//                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
//            }
//
//            $vendorId = $vendor->id;
//
//            // ---------- Top Selling Product (Lifetime) ----------
//            $topProduct = InvoiceItem::where('vendor_id', $vendorId)
//                ->select(
//                    'product_id',
//                    DB::raw('SUM(quantity) as total_quantity'),
//                    DB::raw('SUM(sale_price) as total_revenue')
//                )
//                ->groupBy('product_id')
//                ->orderByDesc('total_quantity')
//                ->first();
//
//            if (!$topProduct) {
//                return ResponseHelper::Out('success', 'No sales found', [
//                    'product' => null
//                ], 200);
//            }
//
//            // product info
//            $product = Product::find($topProduct->product_id);
//
//            return ResponseHelper::Out('success', 'Top product fetched', [
//                'product' => [
//                    'product_id'     => $topProduct->product_id,
//                    'name'           => $product->name ?? 'Unknown',
//                    'total_quantity' => (int) $topProduct->total_quantity,
//                    'total_revenue'  => (float) $topProduct->total_revenue,
//                ]
//            ],200);
//
//        } catch (\Exception $e) {
//            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
//        }
//    }


}
