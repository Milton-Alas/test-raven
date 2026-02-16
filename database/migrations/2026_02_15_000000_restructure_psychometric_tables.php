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
        // 1. Reestructurar percentile_tables (quitar diagnostic/equivalent)
        Schema::dropIfExists('percentile_tables');
        
        Schema::create('percentile_tables', function (Blueprint $table) {
            $table->id();
            $table->integer('age_min');
            $table->integer('age_max');
            $table->integer('raw_score');       // Puntaje directo (0-60)
            $table->integer('percentile');       // Percentil (0-100)
            $table->string('norm_group')->default('montevideo');
            $table->integer('norm_year')->default(2024);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índice compuesto para búsqueda rápida
            $table->index(['age_min', 'age_max', 'raw_score', 'is_active']);
        });

        // 2. Crear diagnostic_ranges (clasificación por percentil)
        Schema::create('diagnostic_ranges', function (Blueprint $table) {
            $table->id();
            $table->integer('percentile_min');   // Percentil mínimo
            $table->integer('percentile_max');   // Percentil máximo
            $table->integer('range_number');     // 1, 2, 3, 4, 5
            $table->string('range_label');       // I, II, III, IV, V
            $table->string('diagnostic_label');  // Superior, Término Medio, etc.
            $table->text('interpretation');       // Descripción completa
            $table->timestamps();

            $table->index(['percentile_min', 'percentile_max']);
        });

        // 3. Crear discrepancy_patterns (puntajes esperados por serie)
        Schema::create('discrepancy_patterns', function (Blueprint $table) {
            $table->id();
            $table->integer('total_score');     // Puntaje total exacto (0-60)
            $table->integer('expected_a');       // Esperado en Serie A
            $table->integer('expected_b');       // Esperado en Serie B
            $table->integer('expected_c');       // Esperado en Serie C
            $table->integer('expected_d');       // Esperado en Serie D
            $table->integer('expected_e');       // Esperado en Serie E
            $table->timestamps();

            $table->unique('total_score');
        });

        // 4. Quitar columnas obsoletas de test_results
        Schema::table('test_results', function (Blueprint $table) {
            // Eliminar discrepancy y expected_score
            if (Schema::hasColumn('test_results', 'expected_score')) {
                $table->dropColumn('expected_score');
            }
            if (Schema::hasColumn('test_results', 'discrepancy')) {
                $table->dropColumn('discrepancy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discrepancy_patterns');
        Schema::dropIfExists('diagnostic_ranges');

        // Restaurar test_results columns
        Schema::table('test_results', function (Blueprint $table) {
            $table->integer('expected_score')->nullable();
            $table->decimal('discrepancy', 5, 2)->nullable();
        });

        // Restaurar percentile_tables original
        Schema::dropIfExists('percentile_tables');
        
        Schema::create('percentile_tables', function (Blueprint $table) {
            $table->id();
            $table->integer('age_min');
            $table->integer('age_max');
            $table->integer('raw_score');
            $table->integer('percentile');
            $table->integer('diagnostic_range');
            $table->string('diagnostic_label');
            $table->integer('equivalent_score');
            $table->string('norm_group')->default('general');
            $table->year('norm_year')->default(2024);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['age_min', 'age_max', 'raw_score']);
            $table->index('percentile');
            $table->index('diagnostic_range');
            $table->unique(['age_min', 'age_max', 'raw_score', 'norm_group']);
        });
    }
};
