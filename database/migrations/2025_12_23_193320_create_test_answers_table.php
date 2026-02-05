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
        Schema::create('test_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_session_id')
                  ->constrained('test_sessions')
                  ->onDelete('cascade');
            $table->foreignId('test_question_id')
                  ->constrained('test_questions')
                  ->onDelete('cascade');
            
            // Respuesta del candidato
            $table->integer('selected_answer')->nullable(); // 1-8 o null si no respondió
            $table->boolean('is_correct')->default(false);
            
            // Tiempo de respuesta
            $table->integer('time_spent')->nullable(); // segundos en esta pregunta
            $table->timestamp('answered_at')->nullable();
            
            // Metadata
            $table->integer('attempt_number')->default(1); // por si permite cambiar respuesta
            $table->boolean('was_changed')->default(false);
            $table->json('interaction_log')->nullable(); // Log de clicks, cambios, etc.
            
            $table->timestamps();
            
            // Índices
            $table->index('test_session_id');
            $table->index('test_question_id');
            $table->unique(['test_session_id', 'test_question_id']); // Una respuesta por pregunta
            $table->index('is_correct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_answers');
    }
};
