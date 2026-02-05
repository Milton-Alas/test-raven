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
        Schema::create('answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_question_id')
                  ->constrained('test_questions')
                  ->onDelete('cascade');
            $table->integer('option_number'); // 1-8
            $table->string('option_image_path'); // ruta de la imagen de la opción
            $table->timestamps();
            
            // Índices
            $table->index('test_question_id');
            $table->unique(['test_question_id', 'option_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_options');
    }
};
