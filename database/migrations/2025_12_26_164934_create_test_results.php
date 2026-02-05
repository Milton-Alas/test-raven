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
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')
                  ->constrained('test_sessions')
                  ->onDelete('cascade');
            $table->foreignId('candidate_id')
                  ->constrained('candidates')
                  ->onDelete('cascade');
            
            // Puntajes por serie (0-12 cada uno)
            $table->integer('series_a_score')->default(0);
            $table->integer('series_b_score')->default(0);
            $table->integer('series_c_score')->default(0);
            $table->integer('series_d_score')->default(0);
            $table->integer('series_e_score')->default(0);
            
            // Puntaje total (0-60)
            $table->integer('total_score')->default(0);
            
            // Análisis estadístico
            $table->integer('percentile')->nullable(); // 1-100
            $table->integer('diagnostic_range')->nullable(); // 1-5 (Rango I-V)
            $table->string('diagnostic_label')->nullable(); // Texto del diagnóstico
            
            // Discrepancia (validación)
            $table->decimal('discrepancy', 5, 2)->nullable(); // PS - PE
            $table->boolean('is_valid')->default(true); // Si discrepancia está en rango
            $table->text('validity_notes')->nullable(); // Razón si es inválido
            
            // Puntajes esperados (para cálculo de discrepancia)
            $table->integer('expected_score')->nullable(); // PE
            $table->json('score_distribution')->nullable(); // JSON con análisis detallado
            
            // Tiempos
            $table->integer('total_time_seconds'); // Tiempo total usado
            $table->integer('average_time_per_question')->nullable(); // Promedio por pregunta
            
            // Metadata
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('calculated_by')->nullable() // Usuario admin que calculó
                  ->constrained('users')
                  ->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->unique('test_session_id');
            $table->index('candidate_id');
            $table->index('total_score');
            $table->index('percentile');
            $table->index('diagnostic_range');
            $table->index(['is_valid', 'diagnostic_range']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
