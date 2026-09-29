<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('inventory_recipes')
            ->join('menu_items', 'inventory_recipes.menu_item_id', '=', 'menu_items.id')
            ->where('menu_items.category', 'SNACKS')
            ->delete();

        DB::table('inventory_recipes')
            ->join('menu_items', 'inventory_recipes.menu_item_id', '=', 'menu_items.id')
            ->where('menu_items.category', 'EXTRAS')
            ->where('menu_items.item_key', '!=', 'egg')
            ->delete();
    }

    public function down(): void
    {
        // Removed optional mappings cannot be reconstructed safely.
    }
};