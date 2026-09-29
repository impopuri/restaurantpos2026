<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    protected $fillable = ['item_key', 'name', 'unit', 'quantity', 'low_stock_threshold'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'low_stock_threshold' => 'decimal:3'];
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(InventoryRecipe::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}