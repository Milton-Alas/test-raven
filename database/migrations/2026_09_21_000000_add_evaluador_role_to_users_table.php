<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amplía el enum de `users.role` con el rol `evaluador`.
     *
     * Motivo: se necesita un rol de solo consulta sobre las evaluaciones
     * (candidatos, sesiones, resultados y el instrumento) para el personal que
     * aplica y lee el test sin administrar el sistema. Se añade al enum —y no
     * como texto libre— para que la base siga rechazando cualquier rol no
     * previsto, igual que hasta ahora.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'reporter', 'evaluador'])
                ->default('reporter')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Los usuarios con el rol retirado vuelven al rol de menor privilegio del
        // panel antes de estrechar el enum: si no, la migración fallaría al
        // encontrar valores que la nueva definición no admite.
        DB::table('users')
            ->where('role', 'evaluador')
            ->update(['role' => 'reporter']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'reporter'])
                ->default('reporter')
                ->change();
        });
    }
};
