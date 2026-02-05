<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('test_series', function (Blueprint $table) {
            $table->id();
            $table->string('code', 1); // A, B, C, D, E
            $table->integer('order'); // 1, 2, 3, 4, 5
            $table->string('name'); // Serie A, Serie B, etc.
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices
            $table->unique('code');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_series');
    }
};
