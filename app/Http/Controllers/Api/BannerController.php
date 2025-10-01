<?php

namespace App\Http\Controllers\Api;
use App\Helpers\FileHelper;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Helpers\ResponseHelper;

class BannerController extends Controller
{
    // Get All Banners
    public function index(): JsonResponse
    {
        try {
            $banners = Banner::with('product')->get();
            return ResponseHelper::Out('success', 'All banners successfully fetched', $banners, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Store Banner
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:50',
                'description' => 'required|string',
                'discount' => 'required|string|max:50',
                'image' => 'required',
                'product_id' => 'required|unique:banners,product_id|exists:products,id'
            ]);
            // File upload using your helper
            $uploadedFiles = FileHelper::upload($request->file('image'), 'banner'); // example: single or multiple files
            // If multiple files, take first image path
            $imagePath = $uploadedFiles[0]['path'] ?? null;
            $banner = Banner::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'discount' => $request->input('discount'),
                'image' => $imagePath,
                'product_id' => $request->input('product_id'),
            ]);
            return ResponseHelper::Out('success', 'Banner successfully created', $banner, 201);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->errors(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Update Banner
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $banner = Banner::findOrFail($id);
            $request->validate([
                'name' => 'required|string|max:50',
                'description' => 'required|string',
                'discount' => 'required|string|max:50'
            ]);
            // Handle main image update
            if ($request->hasFile('image')) {
                // Delete old main image
                if ($banner->image) {
                    FileHelper::delete($banner->image);
                }
                // Upload new main image
                $uploadedFiles = FileHelper::upload($request->file('image'), 'product');
                $imagePath = $uploadedFiles[0]['path'] ?? $banner->image;
            } else {
                $imagePath = $banner->image;
            }
            $banner->update([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'discount' => $request->input('discount'),
                'image' => $imagePath,
                'product_id' => $request->input('product_id'),
            ]);
            return ResponseHelper::Out('success', 'Banner successfully updated', $banner, 200);
        } catch (ValidationException $e) {
            return ResponseHelper::Out('failed', 'Validation exception', $e->getMessage(), 422);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    // Delete Banner
    public function destroy(Request $request): JsonResponse
    {
        try {
            $banner = Banner::findOrFail($request->id);
            if ($banner->image) {
                FileHelper::delete($banner->image); // your delete helper
            }
            $banner->delete();
            return ResponseHelper::Out('success', 'Banner successfully deleted', null, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
}
