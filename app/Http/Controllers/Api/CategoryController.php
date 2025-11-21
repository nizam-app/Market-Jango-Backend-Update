<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Models\Category;
use App\Models\CategoryImage;
use App\Models\User;
use App\Models\Vendor;
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
            $categories = Category::with([
                    'products' => function ($query) {
                        $query->where('is_active', 1)
                            ->select('id','name', 'description', 'regular_price', 'sell_price','discount','image','color', 'size', 'vendor_id','remark', 'category_id')
                            ->with([
                                'images:id,product_id,image_path,public_id',
                                'vendor:id,country,address,business_name,business_type,user_id',
                                'vendor.user:id,name,image,email,phone,language',
                            ]);
                        },
                    'categoryImages:id,category_id,image_path,public_id',
                    'vendor:id'
                    ])
            ->paginate(10);
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
                'images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240',
                'status' => 'required|in:Active,Inactive'
            ]);
            $userId = $request->header('id');
            $vendor = Vendor::where('user_id',$userId)->first();
            if(!$vendor){
                return ResponseHelper::Out('failed','Vendor not found',null, 404);
            }
            $category = Category::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $request->input('status'),
                'vendor_id' => $vendor->id,
            ]);
            if ($request->hasFile('images')) {
                $files = $request->file('images');
                // all file upload
                $uploadedFiles = FileHelper::upload($files, "category");
                foreach ($uploadedFiles as $file) {
                    CategoryImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'vendor_id'    => $vendor->id,
                        'category_id'    => $category->id
                    ]);
                }
            }
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
                'status' => 'required|in:Active,Inactive',
                'images.*' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240'
            ]);
            $vendor = Vendor::where('user_id',$request->header('id'))->with('user:id,name,email,image')->select('id','address','user_id')->first();
            $vendorId = $vendor->id;
            if (!$vendor) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            // Step 2: Get category and check ownership
            $category = Category::where('id', $id)->where('vendor_id', $vendorId)->first();
            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }
            $category->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $request->input('status'),
                'vendor_id' => $vendorId
            ]);
            if ($request->hasFile('images')) {
                $oldImages =CategoryImage::where('category_id', $id)->where('vendor_id', $vendorId)->get();
                if ($oldImages->count() > 0) {
                    foreach ($oldImages as $old) {
                        if (!empty($old->public_id)) {
                            FileHelper::delete($old->public_id);
                        }
                        $old->delete();
                    }
                }
                $files = $request->file('images');
                // all file upload
                $uploadedFiles = FileHelper::upload($files, "category");
                foreach ($uploadedFiles as $file) {
                    CategoryImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'vendor_id'    => $vendorId,
                        'category_id'    => $id
                    ]);
                }
            }
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
            $vendor = Vendor::where('user_id', $request->header('id'))->first();
            if (!$vendor) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            $vendorId = $vendor->id;
            $category = Category::where('id', $id)->where('vendor_id', $vendorId)->first();
            $oldImages =CategoryImage::where('category_id', $id)->where('vendor_id', $vendorId)->get();
            if ($oldImages->count() > 0) {
                foreach ($oldImages as $old) {
                    if (!empty($old->public_id)) {
                        FileHelper::delete($old->public_id);
                    }
                    $old->delete();
                }
            }
            $category->delete();
            return ResponseHelper::Out('success', 'Category successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
