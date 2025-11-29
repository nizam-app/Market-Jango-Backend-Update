<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Location;
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
            $invoices = InvoiceItem::where('driver_id', $driver->id)
                ->with(['invoice','product'])
                ->get();
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', ['data'=>$invoices], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    //new orders Driver
    public function newOrdersDriver(Request $request): JsonResponse
    {
        try {
            // get login buyer
            $user_id = $request->header('id');
            $driver = Driver::where('user_id', $user_id)->first();
            if (!$driver) {
                return ResponseHelper::Out('failed', 'Driver not found', null, 404);
            }
            $invoices = InvoiceItem::where('driver_id', $driver->id)
                ->where('status',  'AssignedOrder')
                ->with(['invoice','product'])
                ->get();
            if ($invoices->isEmpty()) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'All order successfully fetched', ['data'=>$invoices], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
    public function driverHomeStats(Request $request)
    {
        try {
            // Total Active Orders
            $activeOrders = InvoiceItem::where('status', '!=', 'Complete')->where('driver_id', $request->header('id'))->count();

            // Picked Orders
            $picked = InvoiceItem::where('status', 'On The Way')->where('driver_id', $request->header('id'))->count();

            // Delivered Today
            $deliveredToday = InvoiceItem::where('status', 'Complete')
                ->whereDate('updated_at', today())->where('driver_id', $request->header('id'))
                ->count();

            // Pending Deliveries (picked but not delivered today) // not complete
            $pendingDeliveries = InvoiceItem::where('status', 'On The Way')
                ->where('driver_id', $request->header('id'))
                ->count();
            return ResponseHelper::Out('success', 'Driver Order all Status', [ 'data' => [
                'total_active_orders' => $activeOrders,
                'picked' => $picked,
                'pending_deliveries' => $pendingDeliveries,
                'delivered_today' => $deliveredToday,
            ]], 200);
        } catch (Exception $e) {
            return ResponseHelper::Out('failed', 'Something went wrong', $e->getMessage(), 500);
        }
    }
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
                ->where('id', $invoiceId)
                ->with(['invoice', 'user', 'driver'])->first();
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
                ->with(['invoice'])->first();
            if (!$invoice) {
                return ResponseHelper::Out('success', 'order not found', null, 200);
            }
            return ResponseHelper::Out('success', 'Status History fetched successfully', $invoice, 200);
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
    public function driverTotalOrderCount($invoiceId)
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
    // DRIVER SEARCH BY DRIVER ROUTE
    public function driverSearchByLocation(Request $request)
    {
        $pickup = $request->pickup_location;
        $drop = $request->drop_location;
        // pickup location
        $pickupRoute = Location::where('name', 'LIKE', "%$pickup%")->pluck('route_id');
        // drop location
        $dropRoute = Location::where('name', 'LIKE', "%$drop%")->pluck('route_id');
        // match routes
        $commonRoutes = $pickupRoute->intersect($dropRoute);
        if ($commonRoutes->count() == 0) {
            return ResponseHelper::Out('success', 'No driver found for this route', null, 200);
        }
        // Route match driver
        $drivers = Driver::whereIn('route_id', $commonRoutes)->with('user')->get();
        return ResponseHelper::Out('success', 'Driver fetched successfully', $drivers, 200);
    }

}
