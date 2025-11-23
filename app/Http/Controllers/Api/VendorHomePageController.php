<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
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
            $orderItem->update([
                'driver_id'=> $driver_id
            ]);
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
}
