<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    //face all vendor
    public function index():JsonResponse
    {
        try {
            $driver = Driver::all();
            return response()->json([
                'status' => 'success',
                'data' => $driver
            ],200);

        }catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
