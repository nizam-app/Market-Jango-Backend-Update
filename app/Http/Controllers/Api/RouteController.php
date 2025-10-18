<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RouteController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $routes = Route::with(['locations:id,name,route_id'])
                ->select(['id', 'name'])
                ->get();
            return ResponseHelper::Out('success', 'All routes successfully fetched', $routes, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50'
            ]);
            $route = Route::create(['name' => $request->input('name')]);
            return ResponseHelper::Out('success','Route create successfully',$route, 201);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    public function show($id)
    {
        try{
        $route = Route::where('id', $id)->with(['locations:id,name,route_id'])
            ->select(['id', 'name'])
            ->first();
        if(!$route){
            return ResponseHelper::Out('failed','Route not found',null, 404);
        }
        return ResponseHelper::Out('success','Route fetched successfully',$route, 200);
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
            $route = Route::where('id', $id)->first();
            if(!$route){
                return ResponseHelper::Out('failed','Route not found',null, 404);
            }
            $route->update([
                'name' => $request->input('name') ?? $route->name
            ]);
            return ResponseHelper::Out('success','Route Update successfully',$route, 200);
        }  catch (ValidationException $e) {
            return ResponseHelper::Out('failed','Validation Failed',$e->errors(),422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    public function destroy($id)
    {
        try {
        $route = Route::where('id', $id)->first();
        if(!$route){
            return ResponseHelper::Out('failed','Route not found',null, 404);
        }
        $route->delete();
            return ResponseHelper::Out('success','Route Delete successfully',null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
}
