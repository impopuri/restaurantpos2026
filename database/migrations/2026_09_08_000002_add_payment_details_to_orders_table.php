<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->string('discount_type')->default('none');
            $table->string('discount_label')->nullable();
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_other')->nullable();
            $table->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'discount_type',
                'discount_label',
                'discount_rate',
                'discount_amount',
                'total',
                'payment_method',
                'payment_other',
                'paid_at',
            ]);
        });
    }
};