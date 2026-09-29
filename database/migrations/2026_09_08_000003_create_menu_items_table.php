<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_key')->unique();
            $table->string('category', 30)->index();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->json('options')->nullable();
            $table->timestamps();
        });

        $now = now();
        foreach (config('menu.categories') as $category => $items) {
            foreach ($items as $item) {
                DB::table('menu_items')->insert([
                    'item_key' => $item['key'],
                    'category' => $category,
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'options' => isset($item['options']) ? json_encode($item['options']) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};