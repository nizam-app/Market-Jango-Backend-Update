<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CalculateDistance;
use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    // Get All approved vendor
    public function index(): JsonResponse
    {
        try {
            $vendors = User::where('user_type', 'admin')
            ->with(['admin','admin.role'])
            ->paginate(10);
            if($vendors->isEmpty()){
                return ResponseHelper::Out('success', 'No approved vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    public function createAdmin(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validation
            $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6',
                'role' => 'nullable',
                'date_of_birth' => 'nullable|date',
                'present_address' => 'nullable|string',
                'permanent_address' => 'nullable|string',
                'city' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'country' => 'nullable|string',
            ]);

            $role = $request->role;

            // Create the user
            $user = User::create([
                'user_type' => 'admin',
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            // Create admin in separate table
            $admin = Admin::create([
                'user_id'           => $user->id,
                'role'              => $role,
                'date_of_birth'     => $request->input('date_of_birth'),
                'present_address'   => $request->input('present_address'),
                'permanent_address' => $request->input('permanent_address'),
                'city'              => $request->input('city'),
                'postal_code'       => $request->input('postal_code'),
                'country'           => $request->input('country'),
            ]);
            $user->roles()->sync([$role]);
            DB::commit();
            return response()->json([
                'message' => 'New admin created successfully',
                'user' => $user,
                'admin' => $admin
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // Get All approved vendor
    public function activeVendor(): JsonResponse
    {
        try {
            $vendors = Vendor::with([
                'user:id,name,image,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Approved');
                })
                ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
                ->paginate(10);
            if($vendors->isEmpty()){
                return ResponseHelper::Out('success', 'No approved vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // pending vendor
    public function pendingVendor(): JsonResponse
    {
        try {
            $vendors = Vendor::with([
                'user:id,name,email,phone,language,status,phone_verified_at',
                'images:id,user_id,user_type,image_path,file_type',
            ])
            ->whereHas('user', function ($query) {
                $query->where('status', 'Pending');
            })
            ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
            ->paginate(10);
            if($vendors->isEmpty()){
                return ResponseHelper::Out('success', 'No pending vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All pending vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended vendor
    public function suspendedVendor(): JsonResponse
    {
        try {
            $vendors = Vendor::with([
                'user',
                'images',
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->paginate(10);
            if($vendors->isEmpty()){
                return ResponseHelper::Out('success', 'No suspended vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All suspended vendor successfully fetched', $vendors, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //vendor status update
    public function vendorStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $vendor = Vendor::with([
                'user',
                'images:id,user_id,user_type,image_path,file_type',
            ])
            ->where('id', $id)
//            ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
            ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found for this user ID', null, 404);
            }
            //get user status
            $user = $vendor->user;
            //update status
            $user->update(['status' => $request->input('status')]);
            return ResponseHelper::Out('success', 'Vendor status update successfully', $vendor, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Driver status update
    public function driverStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user',
                'images:id,user_id,user_type,image_path,file_type',
            ])
            ->where('id', $id)
//            ->select('id', 'user_id', 'country', 'address', 'business_name', 'business_type')
            ->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found for this user ID', null, 404);
            }
            //get user status
            $user = $driver->user;
            //update status
            $user->update(['status' => $request->input('status')]);
            return ResponseHelper::Out('success', 'Driver status update successfully', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //total vendor count
    public function vendorCount(): JsonResponse
    {
        try {
            $vendors = Vendor::whereHas('user', function ($query) {
                    $query->where('status', 'Approved');
                })
                ->count();

            if($vendors===0){
                return ResponseHelper::Out('success', 'No approved vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //request vendor count
    public function vendorRequestCount(): JsonResponse
    {
        try {
            $vendors = Vendor::whereHas('user', function ($query) {
                $query->where('status', 'Pending');
            })
                ->count();

            if($vendors===0){
                return ResponseHelper::Out('success', 'No pending vendor found', $vendors, 200);
            }
            return ResponseHelper::Out('success', 'All approved vendor successfully fetched', $vendors, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //total vendor count
    public function driverCount(): JsonResponse
    {
        try {
            $drivers = Driver::whereHas('user', function ($query) {
                $query->where('status', 'Approved');
            })
                ->count();

            if($drivers===0){
                return ResponseHelper::Out('success', 'No approved driver found', $drivers, 200);
            }
            return ResponseHelper::Out('success', 'All approved driver successfully fetched', $drivers, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //request vendor count
    public function driverRequestCount(): JsonResponse
    {
        try {
            $drivers = Driver::whereHas('user', function ($query) {
                $query->where('status', 'Pending');
            })
                ->count();

            if($drivers===0){
                return ResponseHelper::Out('success', 'No pending driver found', $drivers, 200);
            }
            return ResponseHelper::Out('success', 'All approved driver successfully fetched', $drivers, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request product
    public function requestProduct(): JsonResponse
    {
        try {
            $products = Product::where('is_active', 0)
                ->with([
                'category:id,name',
                'vendor:id,user_id,address',
                'vendor.user:id,name',
            ])
            ->select(['id','name','vendor_id', 'created_at', 'is_active'])
            ->paginate(10);
            if($products->isEmpty()){
                return ResponseHelper::Out('success', 'No pending product found', $products, 200);
            }
            return ResponseHelper::Out('success', 'All pending product successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Product Status Update
    public function productStatusUpdate(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'is_active' => 'required|in:0,1,2',
            ]);
            $product = Product::select(['id', 'is_active'])->find($id);
            if(!$product){
                return ResponseHelper::Out('success', 'No pending product found', $product, 200);
            }
            $product->update([
                'is_active' => $request->input('is_active')
            ]);
            return ResponseHelper::Out('success', 'All pending product successfully fetched', $product, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request product details
    public function requestProductDetails(Request $request, $id): JsonResponse
    {
        try {
            $products = Product::where('id', $id)
                ->with([
                'category:id,name',
                'vendor:id,user_id,created_at',
                'vendor.user:id,name,image,public_id',
            ])
            ->select(['id','name','description','regular_price','sell_price','image', 'public_id','vendor_id', 'color', 'size', 'created_at', 'category_id', 'is_active'])
            ->first();
            if(!$products){
                return ResponseHelper::Out('success', 'No pending product found', $products, 200);
            }
            return ResponseHelper::Out('success', 'All pending product successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request driver
    public function requestDriver(): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user','images','route'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Pending');
                })
                ->select('id', 'user_id','created_at','location', 'car_name','car_model','price','rating','route_id')
                ->paginate(10);
            if($driver->isEmpty()){
                return ResponseHelper::Out('success', 'No pending driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All pending driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // request driver details
    public function requestDriverDetails(Request $request): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user:id,name,image',
                'images',
                'route'
            ])
                ->where('id', $request->id)
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Pending');
                })
                ->select('id', 'user_id','created_at','location', 'car_name','car_model','price','rating','route_id')
                ->paginate(10);
            if($driver->isEmpty()){
                return ResponseHelper::Out('success', 'No pending driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All pending driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // approved driver
    public function approvedDriver(): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user',
                'images'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Approved');
                })
                ->paginate(10);
            if($driver->isEmpty()){
                return ResponseHelper::Out('success', 'No active driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All active driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended driver
    public function suspendedDriver(): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user'
            ])
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->select('id', 'user_id','created_at','location', 'car_name','car_model')
                ->paginate(10);
            if($driver->isEmpty()){
                return ResponseHelper::Out('success', 'No suspended driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All suspended driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // suspended driver
    public function suspendedDriverDetails(Request $request): JsonResponse
    {
        try {
            $driver = Driver::with([
                'user',
                'images:id,image_path,public_id,user_type,file_type,user_id',
                'route'
            ])
                ->where('id', $request->id)
                ->whereHas('user', function ($query) {
                    $query->where('status', 'Rejected');
                })
                ->paginate(10);
            if($driver->isEmpty()){
                return ResponseHelper::Out('success', 'No suspended driver found', $driver, 200);
            }
            return ResponseHelper::Out('success', 'All suspended driver successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Get Single User
    public function driverDetails(Request $request): JsonResponse
    {
        try {
            $user = Driver::where('id', $request->input('id'))
                ->with(['user','images'])
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            return ResponseHelper::Out('success', 'Driver data fetched successfully', $user, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Driver Cancel Order
    public function notDeliveredOrder(Request $request): JsonResponse
    {
        try {
            $order = InvoiceItem::where('status', 'Not Deliver')
                ->with('driver')
                ->paginate(10);
            if (!$order) {
                return ResponseHelper::Out('success', 'not delivered order  not found', null, 404);
            }
            return ResponseHelper::Out('success', 'not delivered order data fetched successfully', $order, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //delete user
    public function destroy($id)
    {
        try {
            $route = User::where('id', $id)->with(['vendor','buyer','driver','transport'])->first();
            if(!$route){
                return ResponseHelper::Out('failed','User not found',null, 404);
            }
            $route->delete();
            return ResponseHelper::Out('success','User Delete successfully',null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    // Get All order
    public function allOrder(Request $request): JsonResponse
    {
        try {

            // get cart data by login buyer
            $invoices = InvoiceItem::with(['invoice'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    function adminInvoice(Request $request, $driver_id, $order_item_id ): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');
            $user = User::where('id', '=', $user_id)->with('admin')->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $driver = Driver::where('id', $driver_id)->with('user')->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }

            $orderItem = InvoiceItem::where('id', $order_item_id)->with('invoice')->first();
            if (!$orderItem) {
                return ResponseHelper::Out('failed', 'Order item not found', null, 404);
            }
            $tax_ref = uniqid();
            $payment_status = 'Pending';
            $currency = "USD";
            $cus_name = $user->name;
            $cus_phone = $user->phone;
            $total = $driver->price;
            $vat=0;
            $drop_lat = $orderItem->ship_latitude;
            $drop_long = $orderItem->ship_longitude;
            $pickup_lat = $orderItem->current_latitude;
            $pickup_long = $orderItem->current_longitude;
            $distance = CalculateDistance::Distance($pickup_lat, $pickup_long, $drop_lat, $drop_long);
            $subtotal = $total*$distance;
            $payable = $subtotal + $vat;
            $invoice = Invoice::create([
                'cus_name' => $cus_name,
                'cus_email' => $user_email,
                'cus_phone' => $cus_phone,
                'total' => $total,
                'vat' => $vat,
                'payable' => $payable,
                'tax_ref' => $tax_ref,
                'currency' => $currency,
                'payment_method' => "FW",
                'status' => $payment_status,
                'user_id' => $user_id
            ]);
//            $orderItem->update([
//                'driver_id'=> $driver_id,
//                'status'=> "AssignedOrder"
//            ]);
//            $invoiceID = $invoice->id;
//            InvoiceItem::create([
//                'cus_name' => $cus_name,
//                'cus_email' => $user_email,
//                'cus_phone' => $cus_phone,
//                'pickup_address' => $pickup_address,
//                'ship_address' => $drop_of_address,
//                'ship_latitude' => $drop_lat,
//                'ship_longitude' => $drop_long,
//                'distance' => $distance,
//                'quantity' => $EachProduct['quantity'],
//                'status' => $delivery_status,
//                'delivery_charge' => $EachProduct['delivery_charge'],
//                'sale_price' => $EachProduct['price'],
//                'tran_id' => $tran_id,
//                'user_id' => $user_id,
//                'invoice_id' => $invoiceID,
//                'product_id' => $EachProduct['product_id'],
//                'vendor_id' => $vendorId,
//                'driver_id' => null,
//            ]);
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



//    public function driverFilter(Request $request)
//    {
//        try {
//            $request->validate([
//                'q'               => 'nullable|string',
//                'location'        => 'nullable|string',
//                'status'          => 'nullable|in:online,offline,busy',
//                'min_price'       => 'nullable|numeric',
//                'max_price'       => 'nullable|numeric',
//                'pickup_lat'      => 'nullable|numeric|between:-90,90',
//                'pickup_lng'      => 'nullable|numeric|between:-180,180',
//                'destination_lat' => 'nullable|numeric|between:-90,90',
//                'destination_lng' => 'nullable|numeric|between:-180,180',
//                'radius_km'       => 'nullable|numeric|min:0',
//                'sort'            => 'nullable|in:distance,price,rating',
//                'per_page'        => 'nullable|integer|min:1|max:100',
//            ]);
//
//            $drivers = Driver::all();
//
//            // Search (name/phone/city)
//            if ($request->filled('q')) {
//                $term = $request->q;
//                $drivers->where(function($x) use ($term) {
//                    $x->where('name','LIKE',"%{$term}%")
//                        ->orWhere('phone','LIKE',"%{$term}%")
//                        ->orWhere('city','LIKE',"%{$term}%");
//                });
//            }
//
//            // Location LIKE
//            if ($request->filled('location')) {
//                $loc = $request->location;
//                $drivers->where(function($x) use ($loc) {
//                    $x->where('address','LIKE',"%{$loc}%")
//                        ->orWhere('city','LIKE',"%{$loc}%");
//                });
//            }
//
//            // Status + price range
//            if ($request->filled('status'))    $drivers->where('status', $request->status);
//            if ($request->filled('min_price')) $drivers->where('price_per_km','>=',$request->min_price);
//            if ($request->filled('max_price')) $drivers->where('price_per_km','<=',$request->max_price);
//
//            // Nearby from pickup (adds distance_km)
//            if ($request->filled(['pickup_lat','pickup_lng'])) {
//                $lat = (float)$request->pickup_lat;
//                $lng = (float)$request->pickup_lng;
//
//                $drivers->addSelect(DB::raw("
//                    6371 * acos(
//                       cos(radians($lat)) * cos(radians(lat)) *
//                       cos(radians(lng) - radians($lng)) +
//                       sin(radians($lat)) * sin(radians(lat))
//                    ) as distance_km
//                "));
//
//                if ($request->filled('radius_km') && $request->radius_km > 0) {
//                    $drivers->having('distance_km','<=',(float)$request->radius_km);
//                }
//            }
//
//            // Sorting
//            switch ($request->input('sort')) {
//                case 'distance':
//                    if ($request->filled(['pickup_lat','pickup_lng'])) {
//                        $drivers->orderBy('distance_km');
//                    }
//                    break;
//                case 'price':  $drivers->orderBy('price_per_km'); break;
//                case 'rating': $drivers->orderByDesc('rating');   break;
//                default:       $drivers->latest('id');            break;
//            }
//
//            $paginated = $drivers->paginate(10);
//
//            // Estimated fare (pickup -> destination distance)
//            if ($request->filled(['pickup_lat','pickup_lng','destination_lat','destination_lng'])) {
//                $tripKm = $this->haversine(
//                    (float)$request->pickup_lat, (float)$request->pickup_lng,
//                    (float)$request->destination_lat, (float)$request->destination_lng
//                );
//
//                $paginated->getCollection()->transform(function ($d) use ($tripKm) {
//                    if (isset($d->distance_km)) $d->distance_km = round($d->distance_km, 2);
//                    $d->estimated_fare = round($d->base_fare + ($d->price_per_km * $tripKm), 2);
//                    unset($d->lat, $d->lng); // চাইলে লুকানো
//                    return $d;
//                });
//            } else {
//                $paginated->getCollection()->transform(function ($d) {
//                    if (isset($d->distance_km)) $d->distance_km = round($d->distance_km, 2);
//                    unset($d->lat, $d->lng);
//                    return $d;
//                });
//            }
//
//            // NOTE: total আইটেম জানতে চাইলে count() নয়, total() ইউজ করো।
//            return ResponseHelper::Out('success', 'Drivers fetched successfully', [
//                'total'      => $paginated->count(),     // current page count
//                'pagination' => [
//                    'current_page' => $paginated->currentPage(),
//                    'per_page'     => $paginated->perPage(),
//                    'last_page'    => $paginated->lastPage(),
//                    'total'        => $paginated->total(), // all pages total
//                ],
//                'data'       => $paginated->items(),
//            ], 200);
//
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Error fetching drivers', $e->getMessage(), 500);
//        }
//    }
//
//    // GET /api/drivers/{id}
//    public function driverDetails($id)
//    {
//        try {
//            $driver = Driver::select([
//                'id','name','phone','avatar_url as avatar',
//                'status','is_available','price_per_km','base_fare',
//                'rating','total_trips','city','address','lat','lng','created_at'
//            ])->find($id);
//
//            if (!$driver) {
//                return ResponseHelper::Out('success', 'Driver not found', null, 404);
//            }
//
//            unset($driver->lat, $driver->lng); // চাইলে hide
//
//            return ResponseHelper::Out('success', 'Driver details', $driver, 200);
//
//        } catch (Exception $e) {
//            return ResponseHelper::Out('failed', 'Error fetching driver details', $e->getMessage(), 500);
//        }
//    }

}
