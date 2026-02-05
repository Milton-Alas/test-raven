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
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_series_id')
                  ->constrained('test_series')
                  ->onDelete('cascade');
            $table->integer('question_number'); // 1-12 dentro de cada serie
            $table->integer('global_order'); // 1-60 en todo el test
            $table->string('matrix_image_path'); // ruta de la imagen de la matriz
            $table->integer('correct_answer'); // 1-8 (respuesta correcta)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index('test_series_id');
            $table->index('global_order');
            $table->unique(['test_series_id', 'question_number']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_questions');
    }
};
