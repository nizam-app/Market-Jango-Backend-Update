<?php

namespace App\Helpers;

function canChat($senderRole, $receiverRole)
{
    $rules = [
        'buyer' => ['vendor', 'transport'],
        'vendor' => ['buyer', 'driver'],
        'driver' => ['buyer', 'vendor', 'transport'],
        'transport' => ['buyer', 'driver'],
    ];

    return in_array($receiverRole, $rules[$senderRole] ?? []);
}
