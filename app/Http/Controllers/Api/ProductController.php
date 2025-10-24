<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Helpers\ResponseHelper;

class ProductController extends Controller
{
    // Get All Products
    public function index(Request $request): JsonResponse
    {
        try {
            $vendorId = $request->header('id');

            if (!$vendorId) {
                return ResponseHelper::Out('failed', 'Vendor ID missing in request header', null, 400);
            }

            $products = Product::with(['vendor', 'category', 'variants', 'variants.variantValues'])
                ->where('vendor_id', $vendorId)
                ->latest()
                ->get();

            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no products', [], 200);
            }

            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    // Store Product
    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'description' => 'required|string',
                'regular_price' => 'required|string|max:50',
                'sell_price' => 'required|string|max:50',
                'image*' => 'required',
                'size' => 'required',
                'color' => 'required',
                'category_id' => 'nullable|exists:categories,id',
                'is_active' => 'nullable',
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
            $imagePath = $uploadedFiles[0]?? null;
            $product = Product::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'regular_price' => $request->input('regular_price'),
                'sell_price' => $request->input('sell_price'),
                'color' => json_encode($request->input('color')),
                'size' => json_encode($request->input('size')),
                'is_active' => $request->input('is_active'),
                'image' => $imagePath['url'],
                'public_id' => $imagePath['public_id'],
                'vendor_id' => $user->vendor->id,
                'category_id' => $request->input('category_id')
            ]);
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // all file upload
                $uploadedFiles = FileHelper::upload($files, "productImage");
                foreach ($uploadedFiles as $file) {
                    ProductImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'product_id'  => $product->id,
                        'file_type'  => $file['type'],
                    ]);
                }
            }
            DB::commit();
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
                'name' => 'sometimes|string|max:50',
                'description' => 'sometimes|string',
                'regular_price' => 'sometimes|string|max:50',
                'sell_price' => 'sometimes|string|max:50',
                'image' => 'nullable',
                'size' => 'nullable',
                'color' => 'nullable',
                'category_id' => 'nullable|exists:categories,id',
                'is_active' => 'nullable',
            ]);
            //  Get user
            $userId = $request->header('id');
            // Vendor fetch
            $vendor = Vendor::where('user_id', $request->header('id'))
                ->select(['id', 'user_id'])
                ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Get product
            $product = Product::where('id',$id)->where('vendor_id', $vendor->id)->first();
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
                'regular_price' => $request->input('regular_price', $product->regular_price),
                'sell_price' => $request->input('sell_price', $product->sell_price),
                'color' => isset($request->color) ? json_encode($request->color) : $product->color,
                'size' => isset($request->size) ? json_encode($request->size) : $product->size,
                'is_active' => $request->input('is_active', $product->is_active),
                'category_id' => $request->input('category_id', $product->category_id),
            ]);
            // Handle additional files
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                // Optional: delete old ProductImages if needed
                 $product->images()->each(fn($img) => FileHelper::delete($img->image_path));
                 $product->images()->delete();
                $uploadedImages = FileHelper::upload($files, 'productImage');
                foreach ($uploadedImages as $file) {
                    ProductImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'product_id'  => $product->id,
                        'file_type'  => $file['type'],
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

