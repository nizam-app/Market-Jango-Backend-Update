<?php

namespace App\Http\Controllers\Api;

use App\Helpers\FileHelper;
use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\OrderItem;
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
            //Get All Product But New Product First
            $products = Product::with([
                'vendor:id,user_id',
                'vendor.user:id,name',
                'vendor.reviews:id,vendor_id,review,rating',
                'category:id,name',
                'images:id,image_path,public_id,product_id'
            ])
                ->select(['id','name','description','regular_price','sell_price','image','vendor_id','category_id', 'color', 'size'])
                ->latest()
                ->paginate(20);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no products', [], 200);
            }
            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function adminProduct(Request $request): JsonResponse
    {
        try {
            //Get All Product But New Product First
            $products = Product::where('is_active', 1 )->with([
                'vendor:id,user_id',
                'vendor.user:id,name',
                'vendor.reviews:id,vendor_id,review,rating',
                'category:id,name',
                'images:id,image_path,public_id,product_id'
            ])
            ->latest()
            ->paginate(20);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no products', [], 200);
            }
            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    public function getTopProduct(Request $request): JsonResponse
    {
        try {
            //Get All Product But New Product First
            $products = Product::where('is_active', 1 )->where('top_product',1)->with([
                'vendor:id,user_id',
                'vendor.user:id,name',
                'vendor.reviews:id,vendor_id,review,rating',
                'category:id,name',
                'images:id,image_path,public_id,product_id'
            ])
            ->latest()
            ->paginate(20);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no top products', [], 200);
            }
            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }public function getNewItem(Request $request): JsonResponse
    {
        try {
            //Get All Product But New Product First
            $products = Product::where('is_active', 1 )->where('new_item',1)->with([
                'vendor:id,user_id',
                'vendor.user:id,name',
                'vendor.reviews:id,vendor_id,review,rating',
                'category:id,name',
                'images:id,image_path,public_id,product_id'
            ])
            ->latest()
            ->paginate(20);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no new products', [], 200);
            }
            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }public function getJustForYou(Request $request): JsonResponse
    {
        try {
            //Get All Product But New Product First
            $products = Product::where('is_active', 1 )->where('just_for_you', 1)->with([
                'vendor',
                'vendor.user',
                'vendor.reviews',
                'category',
                'images:id,image_path,public_id,product_id'
            ])
            ->latest()
            ->paginate(20);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'You have no just for you products', [], 200);
            }
            return ResponseHelper::Out('success', 'All products successfully fetched', $products, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Get All Products
    public function productDetails(Request $request, $id): JsonResponse
    {
        try {
            //Get All Product But New Product First
            $products = Product::where('id', $id)
            ->with([
                'vendor',
                'vendor.user',
                'vendor.reviews',
                'category',
                'images:id,image_path,public_id,product_id'
            ])
                ->first();
            if (!$products) {
                return ResponseHelper::Out('success', 'You have no products', [], 200);
            }
            return ResponseHelper::Out('success', 'Product successfully fetched', $products, 200);
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
                'image*' => 'required|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'color' => 'required|array',
                'color.*' => 'string',
                'size' => 'required|array',
                'size.*' => 'string',
                'category_id' => 'nullable|exists:categories,id'
            ]);
            $userId = $request->header('id');
            $userEmail = $request->header('email');
            $user = User::where('id', $userId)->where('email', $userEmail)->with('vendor')->first();
            if(!$user){
                return ResponseHelper::Out('failed','Vendor not found',null, 404);
            }
            // File upload using your helper
            $uploadedFiles = FileHelper::upload($request->file('image'), 'product');
            // If multiple files, take first image path
            $imagePath = $uploadedFiles[0]?? null;
            $product = Product::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'regular_price' => $request->input('regular_price'),
                'sell_price' => $request->input('sell_price'),
                'color' => $request->input('color'),
                'size' => $request->input('size'),
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
                'name' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'regular_price' => 'nullable|string',
                'sell_price' => 'nullable|string',
                'image' => 'nullable',
                'size' => 'nullable',
                'color' => 'nullable',
                'category_id' => 'nullable|exists:categories,id'
            ]);
            //  Get user
            $userId = $request->header('id');
            // Vendor fetch
            $vendor = Vendor::where('user_id',$userId)
                ->select(['id', 'user_id'])
                ->first();
            if (!$vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // Get product
            $product = Product::where('id',$id)->where('vendor_id', $vendor->id)->first();
            if (!$product) {
                return ResponseHelper::Out('failed', 'product not found', null, 404);
            }
            // Handle main image update
            if ($request->hasFile('image')) {
                // Delete old main image
                if ($product->public_id) {
                    FileHelper::delete($product->public_id);
                }
                // File upload using your helper
                $uploadedFiles = FileHelper::upload($request->file('image'), 'product');
                // If multiple files, take first image path
                $imagePath = $uploadedFiles[0]?? null;
                $product->image = $imagePath['url'];
                $product->public_id = $imagePath['public_id'];
                $product->save();
            } else {
                $imagePath = $product->image;
            }
            // Update product
            $product->update([
                'name' => $request->input('name', $product->name),
                'description' => $request->input('description', $product->description),
                'regular_price' => $request->input('regular_price', $product->regular_price),
                'sell_price' => $request->input('sell_price', $product->sell_price),
                'color' => isset($request->color) ? $request->input('color') : $product->color,
                'size' => isset($request->size) ? $request->input('size') : $product->size,
                'category_id' => $request->input('category_id', $product->category_id),
            ]);
            // Handle additional files
            if ($request->hasFile('files')) {
                $files = $request->file('files');
                $uploadedImages = FileHelper::upload($files, 'productImage');
                foreach ($uploadedImages as $file) {
                    ProductImage::create([
                        'image_path' => $file['url'],
                        'public_id'  => $file['public_id'],
                        'product_id'  => $product->id
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
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $product = Product::where('id',$id)->where('vendor_id', $user->vendor->id)->first();
            if ($product->image) {
                FileHelper::delete($product->image); // your delete helper
            }
            if ($product->public_id) {
                FileHelper::delete($product->public_id);
            }
            $oldImages =ProductImage::where('product_id', $id)->get();
            if ($oldImages->count() > 0) {
                foreach ($oldImages as $old) {
                    if (!empty($old->public_id)) {
                        FileHelper::delete($old->public_id);
                    }
                    $old->delete();
                }
            }
            $product->delete();
            return ResponseHelper::Out('success', 'Product successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function vendorProductImageDestroy(Request $request, $id): JsonResponse
    {
        try {
            $user = User::where('id', $request->header('id'))->where('email', $request->header('email'))->with('vendor')->first();
            if (!$user || !$user->vendor) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $imagesToDelete = ProductImage::where('id', $id)->first();
            if($imagesToDelete){
                FileHelper::delete($imagesToDelete->public_id);
                $imagesToDelete->delete();
            }
            return ResponseHelper::Out('success', 'Product image successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}

