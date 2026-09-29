<?php

namespace App\Services;

use App\Models\Order;

class ArchiveServedOrders
{
    public function run(): int
    {
        return Order::where('status', 'served')
            ->whereNotNull('served_at')
            ->where('served_at', '<', today()->startOfDay())
            ->update([
                'status' => 'archived',
                'archived_at' => now(),
            ]);
    }
}