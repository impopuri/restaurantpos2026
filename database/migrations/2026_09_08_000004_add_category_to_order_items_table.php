<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('order_items', 'category')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('category', 30)->nullable()->index();
            });
        }

        $items = DB::table('order_items')
            ->join('menu_items', 'order_items.product_key', '=', 'menu_items.item_key')
            ->select('order_items.id', 'menu_items.category')
            ->get();

        foreach ($items as $item) {
            DB::table('order_items')->where('id', $item->id)->update(['category' => $item->category]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};