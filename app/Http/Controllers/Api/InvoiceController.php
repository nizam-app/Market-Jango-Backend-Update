<?php

namespace App\Http\Controllers\Api;

use App\Helpers\CalculateDistance;
use App\Helpers\NotificationHelper;
use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Cart;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceStatusLog;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
    // Get success order
    public function index(Request $request): JsonResponse
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
                ->where('delivery_status', 'successful')
                ->with(['items', 'items.product'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // invoice delivery status update
    public function updateStatus(Request $request, $invoiceId)
    {
        try {
        $validator = $request->validate([
            'status' => 'required|string',
            'note' => 'nullable|string',
            'current_latitude' => 'nullable|string',
            'current_longitude' => 'nullable|string',
            'current_address' => 'nullable|string',
        ]);
        $user_id = $request->header('id');
        $driver = Driver::where('user_id', '=', $user_id)->first();
        if (!$driver) {
            return ResponseHelper::Out('failed', 'Driver not found', null, 404);
        }
        $status = $request->input('status');

        $invoice = InvoiceItem::where('driver_id', $driver->id)
            ->where('id', $invoiceId)
            ->with(['invoice', 'product', 'driver', 'driver.user'])
            ->first();
        if (!$invoice) {
            return ResponseHelper::Out('failed', 'Order not found', null, 404);
        }
        if($status=='Not Deliver'){
            $invoice->update([
                'status' => $status,
                'note' => $request->input('note') ?? $invoice->note,
                'current_latitude' => $request->input('current_latitude') ?? $invoice->current_latitude,
                'current_longitude' => $request->input('current_longitude') ?? $invoice->current_longitude,
                'current_address' => $request->input('current_address') ?? $invoice->current_address,
            ]);
            return ResponseHelper::Out('success', 'Status updated successfully', $invoice, 200);
        }
        if($status=='Complete'){
            // Update status
            $invoice->update([
                'status' =>$status,
                'note' =>$request->input('note')
            ]);
            // SEND NOTIFICATION
            $senderId=$user_id;
            $receiverId=$user_id;
            $message = 'You order is delivered';
            $name=$invoice->cus_name;
            NotificationHelper::sendNotification($senderId,$receiverId,$message, $name );
            return ResponseHelper::Out('success', 'Status updated successfully', $invoice, 200);
        }
        if($status=='Ready for delivery' && $invoice->payment_method == 'OPU'){
            $invoice->update([
                'status' => $status,
                'note' => $request->input('note') ?? $invoice->note,
                'payment_proof_id' => $request->input('payment_proof_id') ?? $invoice->payment_proof_id,
            ]);
            return ResponseHelper::Out('success', 'Status updated successfully', $invoice, 200);
        }
        if($status=='Ready for delivery'){
            $invoice->update([
                'status' => $status,
                'note' => $request->input('note') ?? $invoice->note,
            ]);
            return ResponseHelper::Out('success', 'Status updated successfully', $invoice, 200);
        }
        if($status == 'On The Way'){
            $invoice->update([
                'status' => $status,
                'note' => $request->input('note') ?? $invoice->note,
            ]);
            return ResponseHelper::Out('success', 'Status updated successfully', $invoice, 200);
        }
        return ResponseHelper::Out('success', 'Status updated successfully', $invoice, 200);
        }catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Show  all staus by delivery
    public function showTrackingBuyerDetails(Request $request, $invoiceId)
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $user = User::where('id', '=', $user_id)->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
                $invoice = Invoice::where('user_id', $user_id)
                    ->where('id', $invoiceId)
                    ->with('statusLogs')
                ->first();
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // all orders
    public function allOrders(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = InvoiceItem::where('user_id', $user_id)
                ->with(['invoice','product'])
                ->get();
//                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', ['data'=>$invoices], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //create order
//    public function InvoiceCreate(Request $request)
//    {
//        DB::beginTransaction();
//        try {
//            $user_id = $request->header('id');
//            $user_email = $request->header('email');
//
//            $Profile = User::where('id', $user_id)->with('buyer')->first();
//            if (!$Profile) {
//                return ResponseHelper::Out('failed', 'User not found', null, 404);
//            }
//
//            $buyer = $Profile->buyer;
//            $buyerId = $buyer->id;
//
//            $tran_id = uniqid();
//            $paymentMethod = $request->input('payment_method');
//
//            $total = 0;
//            $cartList = Cart::where('buyer_id', $buyerId)->where('status', 'active')->get();
//
//            if ($cartList->isEmpty()) {
//                return ResponseHelper::Out('failed', 'Cart not found', null, 404);
//            }
//
//            foreach ($cartList as $cartItem) {
//                $total += $cartItem->price + $cartItem->delivery_charge;
//            }
//
//            $vat = 0;
//            $payable = $total;
//
//            $invoice = Invoice::create([
//                'cus_name' => $Profile->name,
//                'cus_email' => $user_email,
//                'cus_phone' => $Profile->phone,
//                'total' => $total,
//                'vat' => $vat,
//                'payable' => $payable,
//                'tax_ref' => $tran_id,
//                'currency' => 'USD',
//                'payment_method' => $paymentMethod,
//                'status' => 'Pending',
//                'user_id' => $user_id
//            ]);
//
//            foreach ($cartList as $item) {
//
//                // ⭐ PRODUCT STOCK CHECK
//                $product = Product::find($item->product_id);
//                if (!$product) {
//                    DB::rollBack();
//                    return ResponseHelper::Out('failed', 'Product not found', null, 404);
//                }
//
//                if ($product->stock < $item->quantity) {
//                    DB::rollBack();
//                    return ResponseHelper::Out(
//                        'failed',
//                        "Not enough stock for product ID {$item->product_id}",
//                        null,
//                        400
//                    );
//                }
//
//                //REDUCE STOCK
//                $product->stock -= $item->quantity;
//                $product->save();
//
//                // CREATE INVOICE ITEM
//                InvoiceItem::create([
//                    'cus_name' => $Profile->name,
//                    'cus_email' => $user_email,
//                    'cus_phone' => $Profile->phone,
//                    'quantity' => $item->quantity,
//                    'status' => 'Pending',
//                    'delivery_charge' => $item->delivery_charge,
//                    'sale_price' => $item->price,
//                    'total_pay' => $item->price + $item->delivery_charge,
//                    'tran_id' => $tran_id,
//                    'user_id' => $user_id,
//                    'invoice_id' => $invoice->id,
//                    'product_id' => $item->product_id,
//                    'vendor_id' => $item->vendor_id,
//                ]);
//
//                // DELETE CART
//                $item->delete();
//            }
//
//            DB::commit();
//
//            return ResponseHelper::Out('success', 'Order placed successfully', [
//                'invoice' => $invoice,
//                'total' => $total,
//                'vat' => $vat,
//                'payable' => $payable
//            ], 200);
//
//        } catch (Exception $e) {
//            DB::rollBack();
//            return ResponseHelper::Out('fail', 'Something went wrong', $e->getMessage(), 200);
//        }
//    }
    function InvoiceCreate(Request $request)
    {
        DB::beginTransaction();
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');
            $Profile = User::where('id', '=', $user_id)->with('buyer')->first();
            if (!$Profile) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $tran_id = uniqid();
            $currency = "USD";
            $delivery_status = 'Pending';
            $payment_status = 'Pending';
            $buyer = $Profile->buyer;
            $buyerId = $buyer->id;
            $drop_lat = $buyer->ship_latitude;
            $drop_long = $buyer->ship_longitude;
            $cus_name = $Profile->name;
            $cus_phone = $Profile->phone;
            $ship_address = $buyer->ship_location;
            $paymentMethod =$request->input('payment_method');
            // Payable Calculation
            $total = 0;
            $cartList = Cart::where('buyer_id', $buyerId)
                ->where('status', 'active')->get();
            if ($cartList->isEmpty()) {
                return ResponseHelper::Out('failed', 'Cart not found', null, 404);
            }
            foreach ($cartList as $cartItem) {
                $total = $total + $cartItem->price + $cartItem->delivery_charge;
            }
            $vat = ($total * 0) / 100;
            $payable = $total + $vat;
            $invoice = Invoice::create([
                'cus_name' => $cus_name,
                'cus_email' => $user_email,
                'cus_phone' => $cus_phone,
                'total' => $total,
                'vat' => $vat,
                'payable' => $payable,
                'tax_ref' => $tran_id,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'status' => $payment_status,
                'user_id' => $user_id
            ]);
            $invoiceID = $invoice->id;
            foreach ($cartList as $EachProduct) {
                $vendorId = $EachProduct['vendor_id'];
                $vendor = Vendor::where('id', $vendorId)->select('id', 'longitude','latitude','address')->first();
                $pickup_lat = $vendor->latitude;
                $pickup_long = $vendor->longitude;
                $vendorLocation = $vendor->address;
                $distance = CalculateDistance::Distance($pickup_lat, $pickup_long, $drop_lat, $drop_long);
                // ⭐ PRODUCT STOCK CHECK
                $product = Product::find($EachProduct->product_id);
                if (!$product) {
                    DB::rollBack();
                    return ResponseHelper::Out('failed', 'Product not found', null, 404);
                }

                if ($product->stock < $EachProduct->quantity) {
                    DB::rollBack();
                    return ResponseHelper::Out(
                        'failed',
                        "Not enough stock for product ID {$EachProduct->product_id}",
                        null,
                        400
                    );
                }

                //REDUCE STOCK
                $product->stock -= $EachProduct->quantity;
                $product->save();

                InvoiceItem::create([
                    'cus_name' => $cus_name,
                    'cus_email' => $user_email,
                    'cus_phone' => $cus_phone,
                    'pickup_address' => $vendorLocation,
                    'pickup_latitude' => $pickup_lat,
                    'pickup_longitude' => $pickup_long,
                    'ship_address' => $ship_address,
                    'ship_latitude' => $drop_lat,
                    'ship_longitude' => $drop_long,
                    'distance' => $distance,
                    'quantity' => $EachProduct['quantity'],
                    'status' => $delivery_status,
                    'delivery_charge' => $EachProduct['delivery_charge'],
                    'sale_price' => $EachProduct['price'],
                    'total_pay' => $EachProduct['price'] + $EachProduct['delivery_charge'],
                    'tran_id' => $tran_id,
                    'user_id' => $user_id,
                    'invoice_id' => $invoiceID,
                    'payment_proof_id' => null,
                    'payment_method' => $paymentMethod,
                    'product_id' => $EachProduct['product_id'],
                    'vendor_id' => $vendorId,
                    'driver_id' => null,
                ]);
//                $EachProduct->delete();
            }
            if ($paymentMethod == 'OPU') {
                DB::commit();
                // SEND NOTIFICATION
                $senderId=$user_id;
                $message = 'You have add to order';
                $name=$buyer->name;
                NotificationHelper::sendNotification($senderId,$senderId,$message, $name );
                return ResponseHelper::Out('success', 'Order placed with Cash On Delivery', [
                    'paymentMethod' => $invoice,
                    'payable' => $payable,
                    'vat' => $vat,
                    'total' => $total
                ], 200);

            } else {
                // FlutterWave Payment
                $paymentMethod = PaymentSystem::InitiatePayment($invoice);
                DB::commit();
                return ResponseHelper::Out('success', 'Order placed with Online payment', [
                    'paymentMethod' => $paymentMethod,
                    'payable' => $payable,
                    'vat' => $vat,
                    'total' => $total
                ], 200);
            }
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('fail', 'Something went wrong', $e->getMessage(), 200);
        }
    }
    //flutter wave redirect url method
    public function handleFlutterWaveResponse(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $tax = $request->input('tx_ref');
            $status = $request->input('status'); // Flutter wave response status
            $payment = Invoice::where('tax_ref', $tax)->with(['items','user'])->first();
            if (!$payment) {
                return ResponseHelper::Out('failed', 'Transaction not found', $payment->getMessage(), 404);
            }
            switch ($status) {
                case 'successful':
                    $payment->status = 'successful';
                    break;
                case 'failed':
                    $payment->status = 'failed';
                    break;
                case 'pending':
                    $payment->status = 'pending';
                    break;

                case 'cancelled':
                    $payment->status = 'cancelled';
                    break;

                case 'reversed':
                    $payment->status = 'reversed';
                    break;

                case 'expired':
                    $payment->status = 'expired';
                    break;

                case 'pending_verification':
                    $payment->status = 'pending_verification';
                    break;

                case 'processing':
                    $payment->status = 'processing';
                    break;

                default:
                    $payment->status = 'unknown';
                    break;
            }
            // Set transaction_id explicitly
            $payment->transaction_id = $transactionId;
            $payment->save();
            if ($status === 'successful') {
                $invoiceStatusLog = InvoiceStatusLog::where('invoice_id',$payment->id )->first();
                if (!$invoiceStatusLog) {
                    // SEND NOTIFICATION
                    $senderId=$payment->user_id;
                    $message = 'Payment successful';
                    $name=$payment->cus_name;
                    NotificationHelper::sendNotification($senderId,$senderId,$message, $name );
                    foreach ($payment->items as $item) {
                        $item->status = 'Pending';
                        $item->save();
                    }
                    return ResponseHelper::Out('success', 'Payment status updated', $payment, 200);
                }
                $invoiceItem = InvoiceItem::where('id',$invoiceStatusLog->invoice_item_id)->first();
                if($invoiceItem){
                $invoiceItem->update([
                    'status'=>'AssignedOrder',
                    'driver_id'=>$invoiceStatusLog->driver_id,
                ]);
                    // SEND NOTIFICATION
                    $senderId=$invoiceItem->user_id;
                    $message = 'Start Delivery';
                    $name=$invoiceItem->cus_name;
                    NotificationHelper::sendNotification($senderId,$senderId,$message, $name );
                }
                foreach ($payment->items as $item) {
                    $item->status = 'Pending';
                    $item->save();
                }
            }
//            $frontBase = config('app.frontend_url', 'http://103.208.183.253:8000');
//            $redirectUrl = $frontBase.'track-order';
//            return redirect()->away($redirectUrl);
            return ResponseHelper::Out('success', 'Payment status updated', $payment, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
