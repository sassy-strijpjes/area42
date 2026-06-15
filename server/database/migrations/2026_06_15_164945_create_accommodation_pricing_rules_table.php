<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_type_id')
                ->unique()
                ->constrained('accommodation_types')
                ->cascadeOnDelete();
            $table->decimal('base_price', 8, 2);
            $table->timestamps();
        });

        Schema::create('accommodation_rate_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_type_id')
                ->constrained('accommodation_types')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('season');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price_per_night', 8, 2);
            $table->timestamps();
        });

        Schema::create('accommodation_surcharges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_type_id')
                ->constrained('accommodation_types')
                ->cascadeOnDelete();
            $table->string('type');
            $table->string('label');
            $table->string('amount_type');
            $table->decimal('amount', 8, 2);
            $table->integer('days_threshold')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('accommodation_extras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_type_id')
                ->constrained('accommodation_types')
                ->cascadeOnDelete();
            $table->string('label');
            $table->string('amount_type');
            $table->decimal('amount', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_pricing');
        Schema::dropIfExists('accommodation_rate_bands');
        Schema::dropIfExists('accommodation_surcharges');
        Schema::dropIfExists('accommodation_extras');
    }
};
