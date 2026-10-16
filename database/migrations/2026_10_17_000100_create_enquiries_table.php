<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_listing_id')->nullable()->constrained('car_listings')->nullOnDelete();
            $table->string('car_title')->nullable();
            $table->string('type', 20);
            $table->string('name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->date('preferred_date')->nullable();
            $table->text('message')->nullable();
            $table->string('source_path')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['car_listing_id', 'type']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
