<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Permission;
use Exception;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::all();
    }

    public function store(Request $request)
    {
        try {
            $request->validate(['name' => 'required|unique:permissions']);

            $permission = Permission::create(['name' => $request->name]);

            return response()->json([
                'status' => 'success',
                'message' => 'Permission created',
                'data' => $permission
            ]);
        }catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'string|unique:permissions,name,' . $id,
                'description' => 'nullable|string',
            ]);

            $permission = Permission::findOrFail($id);
            $permission->update($request->only('name', 'description'));

            return response()->json([
                'status'  => 'success',
                'message' => 'Permission updated successfully',
                'data'    => $permission,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $permission = Permission::findOrFail($id);
            $permission->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Permission deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'failed',
                'message' => 'Something went wrong',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
