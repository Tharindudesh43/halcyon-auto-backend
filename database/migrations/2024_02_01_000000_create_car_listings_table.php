<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('brand');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->decimal('price', 12, 2);
            $table->string('mileage')->nullable();
            $table->string('cc')->nullable();
            $table->string('power_source')->nullable();
            $table->string('transmission')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('whatsapp')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['pending', 'sold'])->default('pending');
            $table->string('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_listings');
    }
};
