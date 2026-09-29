<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = ['inventory_item_id', 'user_id', 'order_id', 'movement_type', 'quantity_change', 'note'];

    protected function casts(): array
    {
        return ['quantity_change' => 'decimal:3'];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}