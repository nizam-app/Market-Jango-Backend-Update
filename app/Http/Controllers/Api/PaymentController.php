<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function InvoiceCreate(Request $request)
    {
        DB::beginTransaction();
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');

            $tran_id = uniqid();
            $delivery_status = 'Pending';
            $payment_status = 'Pending';

            $Profile = Buyer::where('user_id', '=', $user_id)->first();

            $cus_details = "Name:$Profile->cus_name,Address:$Profile->cus_add,City:$Profile->cus_city,Phone:$Profile->cus_phone";
            $ship_details = "Name:$Profile->ship_name,Address:$Profile->ship_add,City:$Profile->ship_city,Phone:$Profile->cus_phone";

            $total = 0;
            $cartList = Cart::where('user_id', '=', $user_id)->get();
            foreach ($cartList as $cartItem) {
                $total += $cartItem->price;
            }

            $vat = ($total * 3) / 100;
            $payable = $total + $vat;

            $invoice = Invoice::create([
                'total' => $total,
                'vat' => $vat,
                'payable' => $payable,
                'cus_details' => $cus_details,
                'ship_details' => $ship_details,
                'tran_id' => $tran_id,
                'delivery_status' => $delivery_status,
                'payment_status' => $payment_status,
                'user_id' => $user_id,
            ]);

            $invoiceID = $invoice->id;

            foreach ($cartList as $EachProduct) {
                InvoiceItem::create([
                    'invoice_id' => $invoiceID,
                    'product_id' => $EachProduct['product_id'],
                    'user_id' => $user_id,
                    'qty' => $EachProduct['qty'],
                    'sale_price' => $EachProduct['price'],
                ]);
            }

            $paymentMethod = PaymentSystem::InitiatePayment($Profile, $payable, $tran_id, $user_email);

            DB::commit();

            return ResponseHelper::Out('success','payment created' ,[
                'paymentMethod' => $paymentMethod,
                'payable' => $payable,
                'vat' => $vat,
                'total' => $total,
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('fail', $e->getMessage(), '',200);
        }
    }

    public function PaymentSuccess(Request $request)
    {
        PaymentSystem::InitiateSuccess($request->query('tran_id'));
        return redirect('/profile');
    }

    public function PaymentCancel(Request $request)
    {
       PaymentSystem::InitiateCancel($request->query('tran_id'));
        return redirect('/profile');
    }

    public function PaymentFail(Request $request)
    {
        PaymentSystem::InitiateFail($request->query('tran_id'));
        return redirect('/profile');
    }

    public function PaymentIPN(Request $request)
    {
        return PaymentSystem::InitiateIPN(
            $request->input('tran_id'),
            $request->input('status'),
            $request->input('tx_ref')
        );
    }

}
