<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductClickLog;
use Illuminate\Http\Request;

class ProductClickController extends Controller
{
    public function productClick(Request $request)
    {
        ProductClickLog::create([
            'vendor_id' => $request->vendor_id,
            'product_id' => $request->product_id,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'device' => $request->header('User-Agent'),
        ]);

        return response()->json(['message' => 'Click Logged']);
    }
}
