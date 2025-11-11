<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverHomeController extends Controller
{
    //all orders Driver
    public function allOrdersDriver(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $driver = Driver::where('user_id', '=', $user_id)->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = InvoiceItem::where('driver_id', $driver->id)
                ->with(['invoice'])
                ->withCount('invoice')
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //New orders Driver
    public function newOrdersDriver(Request $request): JsonResponse
    {
        try {
            // get login driver
            $user_id = $request->header('id');
            $driver = Driver::where('user_id', '=', $user_id)->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = InvoiceItem::where('driver_id', $driver->id)
                ->whereHas('invoice', function ($query) {
                    $query->where('delivery_status', 'Pending');
                })
                ->with('invoice')
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //tracking Driver Pending order Details
    public function showDriverTracking(Request $request, $invoiceId)
    {
        try {
            // get login driver
            $user_id = $request->header('id');
            $driver = Driver::where('user_id', '=', $user_id)->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            $invoice = InvoiceItem::where('driver_id', $driver->id)
                ->whereHas('invoice', function ($query) {
                    $query->where('delivery_status', 'Pending');
                })
                ->where('id', $invoiceId)
                ->with(['invoice','invoice.statusLogTransports'])->first();
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //tracking Driver order Details
    public function DriverTracking(Request $request, $invoiceId)
    {
        try {
            // get login driver
            $user_id = $request->header('id');
            $driver = Driver::where('user_id', '=', $user_id)->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            $invoice = InvoiceItem::where('driver_id', $driver->id)
                ->where('id', $invoiceId)
                ->with(['invoice','invoice.statusLogTransports'])->first();
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function ongoingOrderDriver(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = Invoice::where('user_id', $user_id)
                ->where('delivery_status', 'ongoing')
                ->with(['items', 'items.driver'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }

    //all complete orders Driver
    public function completeOrderDriver(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = Invoice::where('user_id', $user_id)
                ->where('delivery_status', 'successful')
                ->with(['items', 'items.driver'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //all cancel orders transport
    public function cancelOrderDriver(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $buyer = User::where('id', '=', $user_id)->first();
            if (!$buyer) {
                return ResponseHelper::Out('failed', 'Vendor not found', null, 404);
            }
            // get cart data by login buyer
            $invoices = Invoice::where('user_id', $user_id)
                ->where('delivery_status', 'cancel')
                ->with(['items', 'items.driver'])
                ->paginate(10);
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', $invoices, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //tracking successful transport order Details
    public function showSuccessfulDriverTracking($invoiceId)
    {
        try {
            $invoice = Invoice::where('delivery_status', 'successful')->with(['items', 'items.driver','statusLogTransports'])->findOrFail($invoiceId);
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //tracking cancel transport order Details
    public function showCancelDriverTracking($invoiceId)
    {
        try {
            $invoice = Invoice::where('delivery_status', 'cancel')->with('statusLogTransports')->findOrFail($invoiceId);
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //all ongoing orders Driver

}
