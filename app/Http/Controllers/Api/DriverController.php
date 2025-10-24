<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
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
            $drivers = Driver::with([
                    'user:id,name,email,phone,status',
                    'images:id,user_id,user_type,image_path,file_type,public_id'
                ])
                ->select('id','car_name','car_model','location','price','rating','route_id','user_id')
                ->paginate(10);
            if($drivers->isEmpty()){
                return ResponseHelper::Out('success', 'Driver not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All Driver successfully fetched', $drivers, 200);
        }catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);

        }
    }
}
