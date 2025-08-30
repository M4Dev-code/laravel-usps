<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usps_shipments', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_number')->unique()->index();
            $table->string('service_type');
            $table->json('from_address');
            $table->json('to_address');
            $table->decimal('weight', 8, 2);
            $table->json('dimensions')->nullable();
            $table->decimal('cost', 8, 2)->nullable();
            $table->string('label_url')->nullable();
            $table->longText('label_base64')->nullable();
            $table->string('status')->default('created')->index();
            $table->json('tracking_events')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['service_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usps_shipments');
    }
};
