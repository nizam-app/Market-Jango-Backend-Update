<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Exception;
use App\Helpers\ResponseHelper;

class CategoryController extends Controller
{
    // Get All Categories
    public function index(): JsonResponse
    {
        try {
            $categories = Category::with('vendor')->get();
            return ResponseHelper::Out('success', 'All categories successfully fetched', $categories, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Store Category
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'description' => 'required|string',
                'image' => 'required',
                'status' => 'required|in:Active,Inactive'
            ]);
            $userId = $request->header('id');
            $userEmail = $request->header('email');
            $user = User::where('id', $userId)->where('email', $userEmail)->with('vendor')->first();
            if(!$user){
                return ResponseHelper::Out('failed','Vendor not found',null, 404);
            }
            // File upload using your helper
            $uploadedFiles = FileHelper::upload($request->file('image'), 'category'); // example: single or multiple files
            // If multiple files, take first image path
            $imagePath = $uploadedFiles[0]['path'] ?? null;
            $category = Category::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $request->input('status'),
                'vendor_id' => $user->vendor->id,
                'image'       => $imagePath
            ]);
            return ResponseHelper::Out('success', 'Category successfully created', $category, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Update Category
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'description' => 'required|string',
                'status' => 'required|in:Active,Inactive'
            ]);
            $user = User::where('id', $request->header('id'))->where('email', $request->header('email'))->with('vendor')->first();
            $vendorId = $user->vendor->id;
            if (!$user || !$user->vendor) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            // Step 2: Get category and check ownership
            $category = Category::where('id', $id)->where('vendor_id', $vendorId)->first();
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }
            // Step 3: Handle file update
            $newFiles = $request->file('image'); // new image(s)
            $oldFiles = $category->image ? [['path' => $category->image]] : null;
            $uploadedFiles = FileHelper::update($newFiles, $oldFiles, 'vendor'); // using your helper
            $imagePath = $uploadedFiles[0]['path'] ?? $category->image; // fallback to old if no new file
            $category->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'image' => $imagePath,
                'status' => $request->input('status'),
                'vendor_id' => $vendorId
            ]);
            return ResponseHelper::Out('success', 'Category successfully updated', $category, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Delete Category
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = User::where('id', $request->header('id'))->where('email', $request->header('email'))->with('vendor')->first();
            if (!$user || !$user->vendor) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            $category = Category::where('id', $id)->where('vendor_id', $user->vendor->id)->first();
            if ($category->image) {
                FileHelper::delete($category->image); // your delete helper
            }
            $category->delete();
            return ResponseHelper::Out('success', 'Category successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
