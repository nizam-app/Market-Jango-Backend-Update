<?php

namespace App\Helpers;

use App\Models\Invoice;
use Exception;
use Illuminate\Support\Facades\Http;

class PaymentSystem
{
    static function InitiatePayment($Profile, $payable, $tran_id, $user_email): array
    {
        try {
            $flutter = PaymentSystem::first();

            $data = [
                "tx_ref" => $tran_id,
                "amount" => $payable,
                "currency" => $flutter->currency ?? "USD",
                "redirect_url" => "$flutter->success_url?tran_id=$tran_id",
                "customer" => [
                    "email" => $user_email,
                    "phonenumber" => $Profile->cus_phone,
                    "name" => $Profile->cus_name,
                ],
                "customizations" => [
                    "title" => "Market Jango Invoice Payment",
                    "description" => "Payment for Invoice #$tran_id",
                    "logo" => $flutter->logo ?? null
                ],
            ];

            $response = Http::withToken($flutter->secret_key)
                ->post($flutter->init_url, $data);

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
