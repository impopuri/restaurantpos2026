<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ArchiveServedOrders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KitchenController extends Controller
{
    public function __invoke(ArchiveServedOrders $archiveServedOrders): View
    {
        $archiveServedOrders->run();

        return view('kitchen', [
            'ordersByStatus' => Order::whereNull('archived_at')->where('status', '!=', 'voided')->with(['items', 'user'])->latest()->get()->groupBy('status'),
        ]);
    }

    public function archive(ArchiveServedOrders $archiveServedOrders): View
    {
        $archiveServedOrders->run();

        return view('kitchen-archive', [
            'orders' => Order::whereNotNull('archived_at')->with(['items', 'user'])->latest('archived_at')->get(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        abort_if($order->archived_at, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:pending,cooking,served'],
        ]);

        $order->update([
            'status' => $validated['status'],
            'served_at' => $validated['status'] === 'served' ? ($order->served_at ?? now()) : null,
            'archived_at' => null,
        ]);

        return redirect()->route('kitchen')->with('status', "Ticket #{$order->id} status updated.");
    }
}