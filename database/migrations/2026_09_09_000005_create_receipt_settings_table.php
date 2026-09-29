<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 120)->default('ETIVACSILOG');
            $table->string('address', 255)->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('footer', 255)->default('Thank you!');
            $table->string('paper_width', 3)->default('58');
            $table->string('survey_url', 500)->nullable();
            $table->timestamps();
        });

        DB::table('receipt_settings')->insert([
            'id' => 1,
            'business_name' => 'ETIVACSILOG',
            'footer' => 'Thank you!',
            'paper_width' => '58',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_settings');
    }
};