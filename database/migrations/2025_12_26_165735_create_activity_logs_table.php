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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            
            // Usuario que realizó la acción (puede ser admin o candidate)
            $table->nullableMorphs('causer'); // causer_type, causer_id
            
            // Entidad afectada
            $table->nullableMorphs('subject'); // subject_type, subject_id
            
            // Acción realizada
            $table->string('event'); // 'created', 'updated', 'deleted', 'exported', etc.
            $table->text('description')->nullable();
            
            // Datos adicionales
            $table->json('properties')->nullable(); // JSON con datos antes/después
            $table->json('changes')->nullable(); // Cambios específicos
            
            // Metadata
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            
            $table->timestamps();
            
            // Índices
            $table->index('event');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
