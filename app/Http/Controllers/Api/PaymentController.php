<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
// Initiate Flutterwave Payment
    public static function InitiatePayment($Profile, $payable, $tran_id, $user_email, $currency = 'USD'): array
    {
        try {
            $flutterwave = [
                'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
                'redirect_url' => env('FLUTTERWAVE_REDIRECT_URL')
            ];

            $response = Http::withToken($flutterwave['secret_key'])
                ->post('https://api.flutterwave.com/v3/payments', [
                    "tx_ref" => $tran_id,
                    "amount" => $payable,
                    "currency" => $currency,
                    "redirect_url" => $flutterwave['redirect_url'] . "?tran_id=$tran_id",
                    "customer" => [
                        "email" => $user_email,
                        "name" => $Profile->cus_name,
                        "phone_number" => $Profile->cus_phone
                    ],
                    "customizations" => [
                        "title" => "Apple Shop Product",
                        "description" => "Payment for Apple Shop Order $tran_id",
                        "logo" => env('APP_URL') . "/logo.png"
                    ]
                ]);

            return $response->json();
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // Payment Success
    public static function InitiateSuccess($tran_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => 'Success']);
        return 1;
    }

    // Payment Fail
    public static function InitiateFail($tran_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => 'Fail']);
        return 1;
    }

    // Payment Cancel
    public static function InitiateCancel($tran_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => 'Cancel']);
        return 1;
    }

    // IPN/Webhook
    public static function InitiateIPN($tran_id, $status, $val_id): int
    {
        Invoice::where(['tran_id' => $tran_id, 'val_id' => 0])
            ->update(['payment_status' => $status, 'val_id' => $val_id]);
        return 1;
    }
}
