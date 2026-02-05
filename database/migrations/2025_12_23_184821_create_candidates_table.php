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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('dui_nit')->unique();
            $table->string('password');
            $table->integer('age');
            $table->string('occupation')->nullable();
            $table->string('education_level')->nullable();

            // Control del test
            $table->boolean('test_completed')->default(false);
            $table->timestamp('test_started_at')->nullable();
            $table->timestamp('test_completed_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // Para no perder datos al "eliminar"
            
            // Índices
            $table->index('email');
            $table->index('test_completed');
            $table->index(['test_completed', 'is_active']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
