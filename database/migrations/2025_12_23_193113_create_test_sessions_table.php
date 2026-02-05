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
        Schema::create('test_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')
                  ->constrained('candidates')
                  ->onDelete('cascade');
            $table->foreignId('current_question_id')
                  ->nullable()
                  ->constrained('test_questions')
                  ->onDelete('set null');
            
            // Timestamps del test
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            
            // Control de tiempo
            $table->integer('elapsed_time')->default(0); // segundos totales
            $table->integer('time_limit')->default(2700); // 45 minutos en segundos
            $table->integer('remaining_time')->default(2700);
            
            // Estado del test
            $table->enum('status', [
                'not_started',
                'in_progress', 
                'paused',
                'completed',
                'timeout',
                'abandoned'
            ])->default('not_started');
            
            // Metadata
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('browser_info')->nullable(); // JSON con info del navegador
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('candidate_id');
            $table->index('status');
            $table->index(['candidate_id', 'status']);
            $table->unique('candidate_id'); // Solo una sesión activa por candidato

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_sessions');
    }
};
