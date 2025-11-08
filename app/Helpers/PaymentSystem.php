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
                "currency" => $invoice->currency ?? "USD",
                "redirect_url" => url('api/payment/response'),
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
                "message" => $e->getMessage()
            ];
        }
    }
}
