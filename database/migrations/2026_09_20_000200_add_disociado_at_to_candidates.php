<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RNF-09.04 / RNF-09.05 — Marca de disociación.
 *
 * Registra cuándo se disoció (se anonimizó) un candidato. Sin esta marca, la
 * política de retención no podría distinguir un registro pendiente de uno ya
 * procesado, y volvería a disociarlo en cada ejecución sin poder demostrar
 * cuándo ocurrió.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('candidates', 'disociado_at')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->timestamp('disociado_at')->nullable()->after('test_completed_at');
            $table->index('disociado_at');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('candidates', 'disociado_at')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex(['disociado_at']);
            $table->dropColumn('disociado_at');
        });
    }
};
