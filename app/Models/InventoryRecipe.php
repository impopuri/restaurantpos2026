<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRecipe extends Model
{
    protected $fillable = ['menu_item_id', 'inventory_item_id', 'quantity_per_item'];

    protected function casts(): array
    {
        return ['quantity_per_item' => 'decimal:3'];
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}