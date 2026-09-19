<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RNF-09.05 — Registro de las operaciones de retención.
 *
 * El requisito exige que la aplicación automática de las políticas quede
 * registrada. Esta tabla es la evidencia: qué categoría se procesó, con qué
 * política, cuántos registros se vieron afectados y con qué resultado.
 *
 * No guarda datos personales: solo conteos y metadatos de la operación, de modo
 * que el propio registro de la retención no se convierte en un dato sensible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_logs', function (Blueprint $table) {
            $table->id();

            // Categoría de la política: psicometrico, personal, actividad, tecnico.
            $table->string('categoria', 50);

            // Acción aplicada: disociar o suprimir.
            $table->string('accion', 20);

            // Plazo configurado cuando se ejecutó (evidencia de bajo qué política).
            $table->unsignedInteger('dias_retencion');

            // Fecha de corte usada: lo anterior a esta fecha se consideró vencido.
            $table->timestamp('fecha_corte');

            // Resultado: cuántos registros se disociaron / suprimieron y en qué tablas.
            $table->unsignedInteger('registros_afectados')->default(0);
            $table->json('detalle')->nullable();

            // Cómo se ejecutó: 'schedule' (automático) o 'manual' (comando).
            $table->string('origen', 20)->default('manual');

            // Modo simulación: se registra para dejar constancia de una ejecución
            // que no modificó nada.
            $table->boolean('simulacion')->default(false);

            // Resultado de la ejecución: exitosa o con error, con su mensaje.
            $table->string('resultado', 20)->default('exitosa');
            $table->text('error')->nullable();

            $table->unsignedInteger('duracion_ms')->nullable();

            $table->timestamps();

            // Índices para consultar el histórico de la política.
            $table->index('categoria');
            $table->index('created_at');
            $table->index(['categoria', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_logs');
    }
};
