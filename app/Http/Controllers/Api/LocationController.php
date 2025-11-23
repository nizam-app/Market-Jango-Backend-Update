<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Location;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $locations = Location::with(['route'])
//                ->select(['id','name','route_id'])
                ->get();
            if ($locations->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no location', [], 200);
            }
            return ResponseHelper::Out('success', 'All locations successfully fetched', $locations, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'route_id' => 'required'
            ]);
            $location = Location::create([
                'name' => $request->input('name'),
                'route_id' => $request->input('route_id'),
                'longitude' => $request->input('longitude'),
                'latitude' => $request->input('latitude')
            ]);
            return ResponseHelper::Out('success','Location create successfully',$location, 201);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    public function show(Request $request)
    {
        try{
            $location = Location::where('id', $request->input('id'))->with(['route:id,name'])
                ->select(['id', 'name','route_id'])
                ->first();
            if(!$location){
                return ResponseHelper::Out('failed','Location not found',null, 404);
            }
            return ResponseHelper::Out('success','Location fetched successfully',$location, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50'
            ]);
            $location = Location::where('id', $id)->first();
            if(!$location){
                return ResponseHelper::Out('failed','Location not found',null, 404);
            }
            $location->update([
                'name' => $request->input('name') ?? $location->name
            ]);
            return ResponseHelper::Out('success','Location Update successfully',$location, 200);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    public function destroy($id)
    {
        try {
            $location = Location::where('id', $id)->first();
            if(!$location){
                return ResponseHelper::Out('failed','Location not found',null, 404);
            }
            $location->delete();
            return ResponseHelper::Out('success','Location Delete successfully',null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
}
