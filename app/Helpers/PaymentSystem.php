<?php

namespace App\Helpers;

use App\Models\Invoice;
use Exception;
use Illuminate\Support\Facades\Http;

class PaymentSystem
{
    static function InitiatePayment($invoice): array
    {
        try {
            $data = [
                "tx_ref" => $invoice->tax_ref,
                "amount" => $invoice->payable,
                "currency" => $flutter->currency ?? "USD",
                "redirect_url" => url('api/payment/status'),
                "customer" => [
                    "email" => $invoice->cus_email,
                    "phonenumber" => $invoice->cus_phone,
                    "name" => $invoice->cus_name,
                ],
                "customizations" => [
                    "title" => "Market Jango Invoice Payment",
                    "description" => "Payment for Invoice #$invoice",
                    "logo" => null
                ],
            ];

            $response = Http::withToken(env('secret_key'))
                ->post(env('init_url'), $data);

            if ($response->successful()) {
                $res = $response->json();
                return [
                    "payment_url" => $res['data']['link'] ?? null,
                    "status" => "success",
                ];
            } else {
                return [
                    "status" => "failed",
                    "message" => $response->body(),
                ];
            }
        } catch (Exception $e) {
            return [
                "status" => "error",
                "message" => $e->getMessage(),
                "key"=> "hello"
            ];
        }
    }

    static function InitiateSuccess($tran_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => 'Success']);
        return 1;
    }

    static function InitiateFail($tran_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => 'Fail']);
        return 1;
    }

    static function InitiateCancel($tran_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => 'Cancel']);
        return 1;
    }

    static function InitiateIPN($tran_id, $status, $val_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => $status, 'val_id' => $val_id]);
        return 1;
    }
}
