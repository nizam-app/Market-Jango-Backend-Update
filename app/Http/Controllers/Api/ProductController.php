<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Helpers\ResponseHelper;

class ProductController extends Controller
{
    // Get All Products
    public function index(): JsonResponse
    {
        try {
            $products = Product::with(['vendor', 'category','variants','variants.variantValues'])->get();
            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Store Product
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'description' => 'required|string',
                'previous_price' => 'required|string|max:50',
                'current_price' => 'required|string|max:50',
                'image' => 'required',
                'vendor_id' => 'nullable|exists:vendors,id',
                'category_id' => 'nullable|exists:categories,id',
            ]);
            $userId = $request->header('id');
            $userEmail = $request->header('email');
            $user = User::where('id', $userId)->where('email', $userEmail)->with('vendor')->first();
            if(!$user){
                return ResponseHelper::Out('failed','Vendor not found',null, 404);
            }
            // File upload using your helper
            $uploadedFiles = FileHelper::upload($request->file('image'), 'product'); // example: single or multiple files
            // If multiple files, take first image path
            $imagePath = $uploadedFiles[0]['path'] ?? null;
            $product = Product::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'previous_price' => $request->input('previous_price'),
                'current_price' => $request->input('current_price'),
                'image' => $imagePath,
                'vendor_id' => $user->vendor->id,
                'category_id' => $request->input('category_id')
            ]);
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // all file upload
                $uploadedImages = FileHelper::upload($files, null);
                foreach ($uploadedImages as $f) {
                    ProductImage::create([
                        'image_path' => 'storage/' . $f['path'],
                        'product_id'    => $product->id,
                        'file_type'  => $f['type'],
                    ]);
                }
            }
            return ResponseHelper::Out('success', 'Product successfully created', $product, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Update Product
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'description' => 'required|string',
                'previous_price' => 'required|string|max:50',
                'current_price' => 'required|string|max:50',
                'vendor_id' => 'nullable|exists:vendors,id',
                'category_id' => 'nullable|exists:categories,id',
            ]);
            //  Get user
            $userId = $request->header('id');
            $userEmail = $request->header('email');
            $user = User::where('id', $userId)
                ->where('email', $userEmail)
                ->with('vendor')
                ->first();
            if (!$user) {
                return ResponseHelper::Out('failed','Vendor not found',null, 404);
            }
            // Get product
            $product = Product::where('id',$id)->where('vendor_id', $user->vendor->id)->first();
            if (!$product) {
                return response()->json(['error' => 'Product not found'], 404);
            }
            // Handle main image update
            if ($request->hasFile('image')) {
                // Delete old main image
                if ($product->image) {
                    FileHelper::delete($product->image);
                }
                // Upload new main image
                $uploadedFiles = FileHelper::upload($request->file('image'), 'product');
                $imagePath = $uploadedFiles[0]['path'] ?? $product->image;
            } else {
                $imagePath = $product->image;
            }
            // Update product
            $product->update([
                'name' => $request->input('name', $product->name),
                'description' => $request->input('description', $product->description),
                'previous_price' => $request->input('previous_price', $product->previous_price),
                'current_price' => $request->input('current_price', $product->current_price),
                'image' => $imagePath,
                'vendor_id' => $user->vendor->id, // keep vendor consistent
                'category_id' => $request->input('category_id', $product->category_id),
            ]);
            // Handle additional files
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // Optional: delete old ProductImages if needed
                 $product->images()->each(fn($img) => FileHelper::delete($img->image_path));
                 $product->images()->delete();
                $uploadedImages = FileHelper::upload($files, null);
                foreach ($uploadedImages as $f) {
                    ProductImage::create([
                        'image_path' => 'storage/' . $f['path'],
                        'product_id' => $product->id,
                        'file_type' => $f['type'],
                    ]);
                }
            }
            return ResponseHelper::Out('success', 'Product successfully updated', $product, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Delete Product
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $user = User::where('id', $request->header('id'))->where('email', $request->header('email'))->with('vendor')->first();
            if (!$user || !$user->vendor) {
                return response()->json(['error' => 'Vendor not found'], 404);
            }
            $product = Product::where('id',$id)->where('vendor_id', $user->vendor->id)->first();
            if ($product->image) {
                FileHelper::delete($product->image); // your delete helper
            }
            $product->delete();
            return ResponseHelper::Out('success', 'Product successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}

