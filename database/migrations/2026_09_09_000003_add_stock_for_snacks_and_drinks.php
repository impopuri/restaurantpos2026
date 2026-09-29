<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $stockedCategories = ['SNACKS', 'DRINKS'];
        $menuItems = DB::table('menu_items')->whereIn('category', $stockedCategories)->get();

        foreach ($menuItems as $menuItem) {
            $inventoryKey = 'menu-'.$menuItem->item_key;
            $inventoryName = $menuItem->name;
            $unit = match ($menuItem->item_key) {
                'coca-cola-290ml', 'royal-250ml', 'mountain-dew-290ml' => 'bottles',
                'fries' => 'servings',
                'cheesy-pimiento' => 'servings',
                default => 'pcs',
            };

            if ($menuItem->item_key === 'siomai') {
                $inventoryKey = 'siomai';
                $inventoryName = 'Siomai';
                $unit = 'pcs';
            }

            DB::table('inventory_items')->insertOrIgnore([
                'item_key' => $inventoryKey,
                'name' => $inventoryName,
                'unit' => $unit,
                'quantity' => 0,
                'low_stock_threshold' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inventoryItem = DB::table('inventory_items')->where('item_key', $inventoryKey)->first();
            DB::table('inventory_recipes')->insertOrIgnore([
                'menu_item_id' => $menuItem->id,
                'inventory_item_id' => $inventoryItem->id,
                'quantity_per_item' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $menuItems = DB::table('menu_items')->whereIn('category', ['SNACKS', 'DRINKS'])->get();
        foreach ($menuItems as $menuItem) {
            $recipe = DB::table('inventory_recipes')->where('menu_item_id', $menuItem->id)->first();
            DB::table('inventory_recipes')->where('menu_item_id', $menuItem->id)->delete();
            if ($recipe && $menuItem->item_key !== 'siomai') {
                DB::table('inventory_items')->where('id', $recipe->inventory_item_id)->delete();
            }
        }
    }
};