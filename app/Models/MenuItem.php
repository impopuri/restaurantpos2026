<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = ['item_key', 'category', 'name', 'price', 'options'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'options' => 'array',
        ];
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(InventoryRecipe::class);
    }
}