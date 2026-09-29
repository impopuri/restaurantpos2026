<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('cashier')->index();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('served_at')->nullable()->index();
            $table->timestamp('archived_at')->nullable()->index();
        });

        DB::table('orders')->where('status', 'served')->update(['served_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['served_at']);
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['served_at', 'archived_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};