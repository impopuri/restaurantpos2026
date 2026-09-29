<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_key')->unique();
            $table->string('name')->unique();
            $table->string('unit', 30)->default('pcs');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('low_stock_threshold', 12, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('inventory_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity_per_item', 10, 3)->default(1);
            $table->timestamps();
            $table->unique(['menu_item_id', 'inventory_item_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type', 30);
            $table->decimal('quantity_change', 12, 3);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['inventory_item_id', 'created_at']);
        });

        $ingredients = [
            'egg' => ['Eggs', 'pcs'],
            'hotdog' => ['Hotdog', 'pcs'],
            'ham' => ['Ham', 'pcs'],
            'corned-beef' => ['Corned beef', 'servings'],
            'luncheon-meat' => ['Luncheon meat', 'slices'],
            'bangus' => ['Bangus', 'pcs'],
            'tocino' => ['Tocino', 'servings'],
            'siomai' => ['Siomai', 'pcs'],
            'longganisa' => ['Longganisa', 'pcs'],
            'chicken' => ['Chicken', 'pcs'],
            'porkchop' => ['Pork chop', 'pcs'],
            'bacon' => ['Bacon', 'slices'],
            'tapa' => ['Tapa', 'servings'],
            'bagnet' => ['Bagnet', 'servings'],
        ];

        foreach ($ingredients as $key => [$name, $unit]) {
            DB::table('inventory_items')->insert([
                'item_key' => $key,
                'name' => $name,
                'unit' => $unit,
                'quantity' => 0,
                'low_stock_threshold' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $ingredientByMenuKey = [
            'tohsilog' => 'hotdog',
            'mahsilog' => 'ham',
            'nrocsilog' => 'corned-beef',
            'taemnulsilog' => 'luncheon-meat',
            'gnabsilog' => 'bangus',
            'cotsilog' => 'tocino',
            'oissilog' => 'siomai',
            'gnolsilog' => 'longganisa',
            'kcihcsilog' => 'chicken',
            'kropsilog' => 'porkchop',
            'breaded-kropsilog' => 'porkchop',
            'cabsilog' => 'bacon',
            'breaded-cabsilog' => 'bacon',
            'patsilog' => 'tapa',
            'gabsilog' => 'bagnet',
            'siomai' => 'siomai',
            'egg' => 'egg',
        ];

        $menuItems = DB::table('menu_items')->get()->keyBy('item_key');
        $inventoryItems = DB::table('inventory_items')->get()->keyBy('item_key');
        foreach ($menuItems as $menuItem) {
            if ($menuItem->category === 'MEALS') {
                DB::table('inventory_recipes')->insert([
                    'menu_item_id' => $menuItem->id,
                    'inventory_item_id' => $inventoryItems['egg']->id,
                    'quantity_per_item' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $ingredientKey = $ingredientByMenuKey[$menuItem->item_key] ?? null;
            if ($ingredientKey) {
                DB::table('inventory_recipes')->insert([
                    'menu_item_id' => $menuItem->id,
                    'inventory_item_id' => $inventoryItems[$ingredientKey]->id,
                    'quantity_per_item' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_recipes');
        Schema::dropIfExists('inventory_items');
    }
};