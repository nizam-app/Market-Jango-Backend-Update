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
    public function index():JsonResponse
    {
        try {
            $route = Route::all();
            return ResponseHelper::Out('success','All route successfully fetched',$route,200);
        }catch (Exception $e) {
           return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    // $route store
    public function store(Request $request):JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string'
            ]);
            // Route Store
            $route = Route::create([
                'name' => $request->input('name')
            ]);
        return ResponseHelper::Out('success','Route successfully created',$route,201);
        } catch (ValidationException $e) {
        return ResponseHelper::Out('failed','Validation exception',$e->getMessage(),422);
        } catch (Exception $e) {
       return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    // $route update
    public function update(Request $request, $id):JsonResponse
    {
        try {
            $route = Route::findOrFail($id);
            $request->validate([
                'name' => 'required|string'
            ]);
            // Route update
            $route->update([
                'name' => $request->input('name')
            ]);
           return ResponseHelper::Out('success','Route successfully updated',$route,200);
        } catch (ValidationException $e) {
         return ResponseHelper::Out('failed','Validation exception',$e->getMessage(),422);
        } catch (Exception $e) {
          return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
    // $route delete
    public function destroy(Request $request):JsonResponse
    {
        try {
            $route = Route::findOrFail($request->id);
            $route->delete();
           return ResponseHelper::Out('success','Route successfully deleted',null,200);
        } catch (Exception $e) {
           return ResponseHelper::Out('failed','Something went wrong',$e->getMessage(),500);
        }
    }
}
