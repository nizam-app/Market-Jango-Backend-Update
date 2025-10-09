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
            $drivers = Driver::whereRelation('user', 'status', 'Approved')
                ->with([
                    'user:id,name,email,phone,status',
                    'images:id,product_id,image_path,file_type'
                ])
                ->select('id','car_name','car_model','location','price','rating','route_id','user_id')
                ->paginate(10);
            return ResponseHelper::Out('success', 'All Driver successfully fetched', $drivers, 200);
        }catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);

        }
    }
}
