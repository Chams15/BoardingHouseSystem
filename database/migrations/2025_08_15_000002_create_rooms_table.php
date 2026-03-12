<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id('room_id');
            $table->string('room_number', 10)->unique();
            $table->string('category', 50);
            $table->decimal('price_monthly', 10, 2);
            $table->integer('capacity');
            $table->enum('status', ['Available', 'Occupied', 'Maintenance'])->default('Available');
            $table->text('amenities')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
