<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceStatusLog;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\TransportInvoice;
use App\Models\TransportInvoiceStatusLogs;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransportHomeController extends Controller
{
    //Product Search
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
                    'vendor.reviews:id,vendor_id,description,rating',
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
    // get driver details
    public function driverDetails(Request $request, $id): JsonResponse
    {
        try {
            $driver = Driver::where('id', $id)->with('user')->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            return ResponseHelper::Out('success', 'All user successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //Create Order
    function InvoiceCreateTransport(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $request->validate(array(
                'drop_of_address' => 'required|string',
                'pickup_address' => 'required|string',
                'driver_id' => 'required',
                'distance' => 'required',
            ));
            $user_id = $request->header('id');
            $user_email = $request->header('email');
            $user = User::where('id', '=', $user_id)->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $driver_id = $request->input('driver_id');
            $driver = Driver::where('id', $driver_id)->with('user')->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            $tran_id = uniqid();
            $delivery_status = 'Pending';
            $payment_status = 'Pending';
            $currency = "USD";
            $driverId = $driver->id;
            $cus_name = $driverId->user->name;
            $cus_phone = $driverId->user->phone;
            $cus_email = $driverId->user->email;
            $total = $driver->price;
            $pickup_address = $request->input('pickup_address');
            $drop_of_address = $request->input('drop_of_address');
            $distance = $request->input('distance');
            $vat=0;
            $subtotal = $total*$distance;
            $payable = $subtotal + $vat;
            $invoice = Invoice::create([
                'total' => $subtotal,
                'vat' => $vat,
                'payable' => $payable,
                'cus_name' => $cus_name,
                'cus_email' => $cus_email,
                'cus_phone' => $cus_phone,
                'pickup_address' => $pickup_address,
                'drop_of_address' => $drop_of_address,
                'delivery_status' => $delivery_status,
                'distance' => $distance,
                'status' => $payment_status,
                'tax_ref' => $tran_id,
                'currency' => $currency,
                'user_id' => $user_id
            ]);
            $invoiceID = $invoice->id;

            InvoiceItem::create([
                'invoice_id' => $invoiceID,
                'tran_id' => $tran_id,
                'driver_id' => $driverId,
                'sale_price' => $payable,
            ]);
            InvoiceStatusLog::create([
                'status' => null,
                'invoice_id' => $invoiceID,
                'invoice_item_id' => $invoiceID,
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
    //all orders transport
    public function allOrdersTransport(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = Invoice::where('user_id', $user_id)
                ->with(['items', 'items.driver'])
                ->withCount('items')
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //tracking transport order Details
    public function showTransportTracking(Request $request,$invoiceId)
    {
        try {
            $user_id = $request->header('id');
            $user = User::where('id', '=', $user_id)->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $invoice = Invoice::where('user_id', $user_id)
                ->first();
            $invoiceItem = InvoiceItem::where('id', $invoiceId)->where('invoice_id', $invoice->id)->with(['invoice','invoice.statusLogs','statusLogs'])->findOrFail($invoiceId);
            if (!$invoiceItem) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoiceItem, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //tracking successful transport order Details
    public function showSuccessfulTransportTracking($invoiceId)
    {
        try {
            $invoice = Invoice::where('delivery_status', 'Completed')->with(['items', 'items.driver','items.driver.user','statusLogTransports'])->findOrFail($invoiceId);
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //tracking cancel transport order Details
    public function showCancelTransportTracking($invoiceId)
    {
        try {
            $invoice = Invoice::where('delivery_status', 'Cancelled')->with('statusLogTransports')->findOrFail($invoiceId);
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //all ongoing orders transport
    public function ongoingOrderTransport(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = Invoice::where('user_id', $user_id)
                ->where('delivery_status', 'Ongoing')
                ->with(['items', 'items.driver'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    //all complete orders transport
    public function completeOrderTransport(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = Invoice::where('user_id', $user_id)
                ->where('delivery_status', 'completed')
                ->with(['items', 'items.driver'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //all cancel orders transport
    public function cancelOrderTransport(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = Invoice::where('user_id', $user_id)
                ->where('delivery_status', 'cancel')
                ->with(['items', 'items.driver'])
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
