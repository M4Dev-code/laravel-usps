<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usps_rate_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->json('request_params');
            $table->json('rates_data');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usps_rate_cache');
    }
};
