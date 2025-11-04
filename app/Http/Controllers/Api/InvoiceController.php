<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    function InvoiceCreate(Request $request)
    {
        DB::beginTransaction();
        try {
            $user_id=$request->header('id');
            $user_email=$request->header('email');
            $user_id=1;
            $user_email='mills.howell@example.net';

            $tran_id=uniqid();
            $currency = "USD";
            $delivery_status='Pending';
            $payment_status='Pending';


            $Profile=User::where('id','=',$user_id)->with('buyer')->first();
            $buyer = $Profile->buyer;
            $cus_details="Name:$Profile->name,Address:,City:$buyer->state,Phone: $Profile->phone";
           $cus_name = $Profile->name;
           $cus_phone = $Profile->phone;
           $ship_address = $buyer->address;
           $ship_city = $buyer->ship_city;
           $ship_country = $buyer->country;
            // Payable Calculation
            $total=0;
            $cartList = Cart::where('buyer_id', $buyer->id)
                ->where('status', 'active')->get();
            foreach ($cartList as $cartItem) {
                $total=$total+$cartItem->price+$cartItem->delivery_charge;
            }
            $vat=($total*0)/100;
            $payable=$total+$vat;
            $invoice = Invoice::create([
                'total'         => $total,
                'vat'           => $vat,
                'payable'       => $payable,
                'cus_name'      => $cus_name,
                'cus_email'     => $user_email,
                'cus_phone'     => $cus_phone,
                'ship_address'  => $ship_address,
                'ship_city'     => $ship_city,
                'ship_country'  => $ship_country,
                'tax_ref'       => $tran_id,
                'currency' => $currency,
                'delivery_status'=> $delivery_status,
                'payment_status' => $payment_status,
                'user_id'      => $user_id
            ]);
            $invoiceID=$invoice->id;
            foreach ($cartList as $EachProduct) {
                InvoiceItem::create([
                    'invoice_id' => $invoiceID,
                    'tran_id' => $tran_id,
                    'product_id' => $EachProduct['product_id'],
                    'user_id'=>$user_id,
                    'quantity' =>  $EachProduct['quantity'],
                    'sale_price'=>  $EachProduct['price'],
                ]);
            }
            $paymentMethod=PaymentSystem::InitiatePayment($invoice);
            DB::commit();
            return ResponseHelper::Out('success','',array(['paymentMethod'=>$paymentMethod,'payable'=>$payable,'vat'=>$vat,'total'=>$total]),200);
        }
        catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('fail','',$e,200);
        }
    }

    function InvoiceList(Request $request){
        $user_id=$request->header('id');
        return Invoice::where('user_id',$user_id)->get();
    }

    function InvoiceProductList(Request $request){
        $user_id=$request->header('id');
        $invoice_id=$request->invoice_id;
        return InvoiceItem::where(['user_id'=>$user_id,'invoice_id'=>$invoice_id])->with('product')->get();
    }

    function PaymentStatus(Request $request){
    dd($request->all());
    }


    function PaymentCancel(Request $request){
        PaymentSystem::InitiateCancel($request->query('tran_id'));
    }

    function PaymentFail(Request $request){
        return PaymentSystem::InitiateFail($request->query('tran_id'));
    }

    function PaymentIPN(Request $request){
        return PaymentSystem::InitiateIPN($request->input('tran_id'),$request->input('status'),$request->input('val_id'));
    }
}
