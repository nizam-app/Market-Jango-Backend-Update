<?php

namespace App\Helpers;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;
    protected $from;

    public function __construct()
    {
        $this->client = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $this->from = env('TWILIO_PHONE_NUMBER');
    }

    public function sendSms($to, $message)
    {
        try {
            return $this->client->messages->create($to, [
                'from' => $this->from,
                'body' => $message
            ]);
        } catch (\Exception $e) {
            throw new \Exception("SMS sending failed: ".$e->getMessage());
        }
    }
}
