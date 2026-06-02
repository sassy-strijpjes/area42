<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained('restaurant_tables');
            $table->string('guest_name');
            $table->string('guest_phone')->nullable();
            $table->date('booking_date');
            $table->time('booking_start');
            $table->time('booking_end')->nullable();
            $table->integer('party_size');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_bookings');
    }
};

