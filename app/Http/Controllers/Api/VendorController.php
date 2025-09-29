<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorController extends Controller
{
    //face all vendor
    public function index():JsonResponse
    {
        try {
            $driver = Vendor::all();
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
    //search by vendor name
    public function searchByName(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);
            $name = $request->input('name');
            $users = User::where('role', '=', 'Vendor')->get();
//            $vendors = Vendor::where('name', 'LIKE', "%{$name}%")->get();
            if ($users->isEmpty()) {
                return response()->json([
                    'message' => 'No vendors found.'
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'data' => $users
            ],200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Fail',
                'message' => 'Something went wrong',
                'errors' => $e->getMessage()
            ], 500);
        }
    }
}
