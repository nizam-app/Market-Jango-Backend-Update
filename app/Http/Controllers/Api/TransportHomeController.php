<?php

namespace App\Http\Controllers\Api;

use App\Helpers\PaymentSystem;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\SearchHistory;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransportHomeController extends Controller
{
    public function productSearchByBuyer(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|min:1|max:50',
            ]);
            $userId = $request->header('id');
            $user = Buyer::where('user_id', $userId)->select('id')->first();
            if(!$user){
                return ResponseHelper::Out('failed','user not found',null, 404);
            }
            $query = trim($request->input('name'));
            //  Save search to history only if > 10 chars and only once per 24h
            if (strlen($query) >= 10) {
                $lastSearch = SearchHistory::where('user_id', $userId)
                    ->where('created_at', '>=', now()->subDay())
                    ->first();

                if (!$lastSearch) {
                    SearchHistory::create([
                        'user_id' => $userId,
                        'name' => $query,
                    ]);
                }
            }
            // search product
            $products = Product::where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%");
            })
                ->with([
                    'vendor:id,user_id',
                    'vendor.user:id,name',
                    'vendor.reviews:id,vendor_id,description,rating',
                    'category:id,name',
                    'images:id,image_path,public_id,product_id'
                ])
                ->select([
                    'id',
                    'name',
                    'description',
                    'regular_price',
                    'image',
                    'size',
                    'color',
                    'vendor_id',
                    'category_id'
                ])
                ->orderByRaw("
                CASE
                    WHEN name = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    WHEN name LIKE ? THEN 3
                    WHEN description LIKE ? THEN 4
                    ELSE 5
                END, id ASC
            ", [
                    $query,
                    "{$query}%",
                    "%{$query}%",
                    "%{$query}%"
                ])
                ->limit(20)
                ->paginate(10);
            if ($products->isEmpty()) {
                return ResponseHelper::Out('success', 'No products found', null, 200);
            }
            return ResponseHelper::Out('success', 'Products found', $products, 200);

        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
// get driver details
    public function driverDetails(Request $request, $id): JsonResponse
    {
        try {
            $driver = Driver::where('id', $id)->with('user')->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            return ResponseHelper::Out('success', 'All user successfully fetched', $driver, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    function InvoiceCreateTransport(Request $request, $id): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');
            $user = User::where('id', '=', $user_id)->first();
            if (!$user) {
                return ResponseHelper::Out('failed', 'User not found', null, 404);
            }
            $driver = Driver::where('id', $id)->with('user')->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            $tran_id = uniqid();
            $payable = $driver->price;
            $currency = "USD";
            $cus_name = $user->name;
            $cus_phone = $user->phone;
            $delivery_status = 'Pending';
            $payment_status = 'Pending';
            $vat='0';
            $invoice = Invoice::create([
                'total' => $payable,
                'vat' => $vat,
                'payable' => $payable,
                'cus_name' => $cus_name,
                'cus_email' => $user_email,
                'cus_phone' => $cus_phone,
                'ship_address' => null,
                'ship_city' => null,
                'ship_country' => null,
                'tax_ref' => $tran_id,
                'currency' => $currency,
                'delivery_status' => $delivery_status,
                'status' => $payment_status,
                'user_id' => $user_id
            ]);
            $invoiceID = $invoice->id;

                InvoiceItem::create([
                    'invoice_id' => $invoiceID,
                    'tran_id' => $tran_id,
                    'product_id' => null,
                    'vendor_id' =>null,
                    'quantity' => 1,
                    'sale_price' =>$invoice->payable,
                ]);

            $paymentMethod = PaymentSystem::InitiatePayment($invoice);
            DB::commit();
            return ResponseHelper::Out('success', '', array(['paymentMethod' => $paymentMethod, 'payable' => $payable, 'vat' => $vat, 'total' => $payable]), 200);
        } catch (Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('fail', 'Something went wrong', $e->getMessage(), 200);
        }
    }



}
