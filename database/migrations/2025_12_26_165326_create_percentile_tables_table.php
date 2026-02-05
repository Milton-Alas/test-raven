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
        Schema::create('percentile_tables', function (Blueprint $table) {
            $table->id();
            
            // Rango de edad
            $table->integer('age_min');
            $table->integer('age_max');
            
            // Puntaje raw (0-60)
            $table->integer('raw_score');
            
            // Percentil correspondiente (1-100)
            $table->integer('percentile');
            
            // Rango diagnóstico (I-V)
            $table->integer('diagnostic_range'); // 1, 2, 3, 4, 5
            $table->string('diagnostic_label'); // Texto descriptivo
            
            // Puntaje equivalente (para discrepancia)
            $table->integer('equivalent_score');
            
            // Metadata
            $table->string('norm_group')->default('general'); // 'general', 'escolar', etc.
            $table->year('norm_year')->default(2024); // Año de las normas
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Índices
            $table->index(['age_min', 'age_max', 'raw_score']);
            $table->index('percentile');
            $table->index('diagnostic_range');
            $table->unique(['age_min', 'age_max', 'raw_score', 'norm_group']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('percentile_tables');
    }
};
