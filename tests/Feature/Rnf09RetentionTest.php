<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\RetentionLog;
use App\Models\TestResult;
use App\Models\TestSession;
use App\Services\RetentionService;
use App\Support\RetentionSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RNF-09.04 y RNF-09.05 — Retención de datos y su aplicación automática.
 *
 * 09.04: el sistema permite configurar períodos por categoría y aplica
 *        supresión o disociación al vencer el plazo.
 * 09.05: la aplicación es automática y queda registrada.
 *
 * El punto más delicado que se comprueba aquí es que la disociación elimine de
 * verdad todos los identificadores directos, y que la retención no toque lo que
 * todavía está dentro de su plazo.
 */
class Rnf09RetentionTest extends TestCase
{
    use RefreshDatabase;

    private function servicio(): RetentionService
    {
        return app(RetentionService::class);
    }

    private function candidato(array $atributos = []): Candidate
    {
        $candidato = Candidate::create(array_merge([
            'name' => 'Candidato Antiguo',
            'email' => 'antiguo@example.test',
            'dui_nit' => '05123456-7',
            'password' => 'clave-segura-123',
            'age' => 30,
            'occupation' => 'Docente',
            'education_level' => 'Universitario',
            'is_active' => true,
        ], $atributos));

        if (isset($atributos['created_at'])) {
            DB::table('candidates')->where('id', $candidato->id)->update(['created_at' => $atributos['created_at']]);
        }

        return $candidato;
    }

    private function resultadoDe(Candidate $candidato, \DateTimeInterface $creado): TestResult
    {
        $sesion = TestSession::create([
            'candidate_id' => $candidato->id,
            'status' => 'completed',
            'started_at' => $creado,
            'completed_at' => $creado,
            'elapsed_time' => 1800,
        ]);

        DB::table('test_sessions')->where('id', $sesion->id)->update(['created_at' => $creado]);

        $resultado = TestResult::create([
            'test_session_id' => $sesion->id,
            'candidate_id' => $candidato->id,
            'series_a_score' => 10,
            'total_score' => 40,
            'percentile' => 80,
            'diagnostic_range' => 2,
            'diagnostic_label' => 'Superior al Término Medio',
            'is_valid' => true,
            'total_time_seconds' => 1800,
            'calculated_at' => $creado,
        ]);

        DB::table('test_results')->where('id', $resultado->id)->update(['created_at' => $creado]);

        return $resultado;
    }

    // ------------------------------------------------------------ 09.04

    public function test_disocia_solo_los_datos_que_superaron_su_plazo(): void
    {
        $viejo = $this->candidato([
            'created_at' => now()->subDays(2000),
            'email' => 'viejo@example.test',
        ]);
        $reciente = $this->candidato([
            'dui_nit' => '14725836-9',
            'email' => 'reciente@example.test',
        ]);

        $this->servicio()->aplicar(categoria: 'personal');

        // El vencido queda anonimizado.
        $this->assertNotNull($viejo->fresh()->disociado_at);
        $this->assertStringNotContainsString('Antiguo', $viejo->fresh()->name);

        // El reciente no se toca.
        $this->assertNull($reciente->fresh()->disociado_at);
        $this->assertSame('Candidato Antiguo', $reciente->fresh()->name);
        $this->assertSame('reciente@example.test', $reciente->fresh()->email);

        // Y el correo del vencido deja de ser el real.
        $this->assertStringNotContainsString('@example.test', (string) $viejo->fresh()->email);
    }

    public function test_la_disociacion_elimina_todos_los_identificadores_directos(): void
    {
        $candidato = $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->servicio()->aplicar(categoria: 'personal');

        $crudo = DB::table('candidates')->where('id', $candidato->id)->first();

        $this->assertNull($crudo->dui_nit, 'El identificador cifrado debe eliminarse.');
        $this->assertNull($crudo->dui_nit_hash, 'El índice de búsqueda debe eliminarse.');
        $this->assertNull($crudo->occupation);
        $this->assertNull($crudo->education_level);
        $this->assertSame(0, (int) $crudo->age, 'La edad es un dato cuasi-identificador: se neutraliza.');
        $this->assertStringNotContainsString('@example.test', (string) $crudo->email);
        $this->assertStringNotContainsString('Antiguo', (string) $crudo->name);
        $this->assertNotNull($crudo->disociado_at, 'Debe quedar constancia de cuándo se disoció.');
    }

    public function test_la_disociacion_conserva_los_datos_psicometricos(): void
    {
        $candidato = $this->candidato(['created_at' => now()->subDays(2000)]);
        $resultado = $this->resultadoDe($candidato, now()->subDays(2000));

        $this->servicio()->aplicar(categoria: 'personal');

        // El puntaje sobrevive: es lo que mantiene la validez estadística del
        // baremo. Lo que desaparece es la atribución a una persona.
        $crudo = DB::table('test_results')->where('id', $resultado->id)->first();

        $this->assertNotNull($crudo, 'El resultado no debe borrarse al disociar.');
        $this->assertSame(40, (int) $crudo->total_score);
        $this->assertSame(80, (int) $crudo->percentile);

        // Y el candidato asociado ya no es identificable.
        $this->assertSame('Candidato disociado #'.$candidato->id, Candidate::find($candidato->id)->name);
    }

    public function test_un_candidato_disociado_no_se_procesa_dos_veces(): void
    {
        $candidato = $this->candidato(['created_at' => now()->subDays(2000)]);

        $primera = $this->servicio()->aplicar(categoria: 'personal');
        $segunda = $this->servicio()->aplicar(categoria: 'personal');

        $this->assertSame(1, $primera[0]->registros_afectados);
        $this->assertSame(0, $segunda[0]->registros_afectados, 'Lo ya disociado no debe volver a procesarse.');
        $this->assertNotNull($candidato->fresh()->disociado_at);
    }

    public function test_suprime_los_registros_de_actividad_vencidos(): void
    {
        $viejo = ActivityLog::create([
            'event' => 'prueba_vieja',
            'description' => 'Registro antiguo',
        ]);
        DB::table('activity_logs')->where('id', $viejo->id)->update(['created_at' => now()->subDays(1000)]);

        $reciente = ActivityLog::create([
            'event' => 'prueba_reciente',
            'description' => 'Registro reciente',
        ]);

        $this->servicio()->aplicar(categoria: 'actividad');

        $this->assertDatabaseMissing('activity_logs', ['id' => $viejo->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $reciente->id]);
    }

    public function test_suprime_los_datos_tecnicos_sin_borrar_la_sesion(): void
    {
        $candidato = $this->candidato();
        $sesion = TestSession::create([
            'candidate_id' => $candidato->id,
            'status' => 'completed',
            'started_at' => now()->subDays(400),
            'completed_at' => now()->subDays(400),
            'ip_address' => '190.85.1.20',
            'user_agent' => 'Mozilla/5.0 (prueba)',
            'browser_info' => ['platform' => 'Linux'],
        ]);
        DB::table('test_sessions')->where('id', $sesion->id)->update(['created_at' => now()->subDays(400)]);

        $this->servicio()->aplicar(categoria: 'tecnico');

        $crudo = DB::table('test_sessions')->where('id', $sesion->id)->first();

        $this->assertNotNull($crudo, 'La sesión debe conservarse: es evidencia del proceso psicométrico.');
        $this->assertNull($crudo->ip_address);
        $this->assertNull($crudo->user_agent);
        $this->assertNull($crudo->browser_info);
    }

    public function test_los_plazos_son_configurables(): void
    {
        $candidato = $this->candidato(['created_at' => now()->subDays(100)]);

        // Con el plazo por defecto (1825 días) no se toca.
        $this->servicio()->aplicar(categoria: 'personal');
        $this->assertNull($candidato->fresh()->disociado_at);

        // Con un plazo de 30 días, el mismo registro queda vencido.
        config(['retention.categorias.personal.dias' => 30]);

        $this->servicio()->aplicar(categoria: 'personal');
        $this->assertNotNull($candidato->fresh()->disociado_at);
    }

    public function test_una_categoria_sin_plazo_no_se_aplica(): void
    {
        $this->candidato(['created_at' => now()->subDays(5000)]);

        config(['retention.categorias.personal.dias' => null]);

        $resultado = $this->servicio()->aplicar(categoria: 'personal');

        $this->assertSame(0, $resultado[0]->registros_afectados);
        $this->assertSame(0, Candidate::whereNotNull('disociado_at')->count());
    }

    // ------------------------------------------------------------ 09.05

    public function test_cada_operacion_queda_registrada(): void
    {
        $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->servicio()->aplicar(categoria: 'personal');

        $log = RetentionLog::where('categoria', 'personal')->latest('id')->first();

        $this->assertNotNull($log, 'La operación debe quedar registrada.');
        $this->assertSame('disociar', $log->accion);
        $this->assertSame(1825, $log->dias_retencion);
        $this->assertNotNull($log->fecha_corte);
        $this->assertSame(1, $log->registros_afectados);
        $this->assertSame('exitosa', $log->resultado);
        $this->assertSame('manual', $log->origen);
        $this->assertFalse($log->simulacion);
        $this->assertArrayHasKey('candidatos_disociados', $log->detalle);
    }

    public function test_el_registro_de_retencion_no_guarda_datos_personales(): void
    {
        $candidato = $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->servicio()->aplicar(categoria: 'personal');

        $contenido = json_encode(RetentionLog::all()->map(fn (RetentionLog $l): array => [
            'detalle' => $l->detalle,
            'error' => $l->error,
        ])->all());

        $this->assertStringNotContainsString('05123456', $contenido);
        $this->assertStringNotContainsString('antiguo@example.test', $contenido);
        $this->assertStringNotContainsString('Candidato Antiguo', $contenido);
    }

    public function test_el_modo_simulacion_no_modifica_nada_pero_queda_registrado(): void
    {
        $candidato = $this->candidato(['created_at' => now()->subDays(2000)]);
        $antes = DB::table('candidates')->where('id', $candidato->id)->first();

        $this->servicio()->aplicar(categoria: 'personal', simulacion: true);

        $despues = DB::table('candidates')->where('id', $candidato->id)->first();

        $this->assertSame($antes->name, $despues->name, 'La simulación no debe modificar datos.');
        $this->assertSame($antes->dui_nit, $despues->dui_nit);
        $this->assertNull($despues->disociado_at);

        // Pero sí deja constancia de que se evaluó (y de cuántos se verían afectados).
        $log = RetentionLog::where('categoria', 'personal')->latest('id')->first();
        $this->assertTrue($log->simulacion);
        $this->assertSame(1, $log->registros_afectados);
    }

    public function test_el_comando_aplica_la_retencion_y_reporta(): void
    {
        $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->artisan('retention:apply')
            ->assertSuccessful()
            ->expectsOutputToContain('Retención aplicada');

        // Una fila por categoría procesada: el requisito es que cada operación
        // quede registrada, no que haya una sola.
        $this->assertSame(
            count(config('retention.categorias')),
            RetentionLog::where('simulacion', false)->count()
        );
        $this->assertNotNull(Candidate::first()->disociado_at);
    }

    public function test_el_comando_en_simulacion_no_modifica_datos(): void
    {
        $candidato = $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->artisan('retention:apply --dry-run')->assertSuccessful();

        $this->assertNull($candidato->fresh()->disociado_at);
        $this->assertSame(
            count(config('retention.categorias')),
            RetentionLog::where('simulacion', true)->count()
        );
    }

    public function test_el_comando_acepta_una_categoria_concreta(): void
    {
        $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->artisan('retention:apply --categoria=actividad')->assertSuccessful();

        // Solo se procesó la categoría indicada.
        $this->assertSame(0, RetentionLog::where('categoria', 'personal')->count());
        $this->assertSame(1, RetentionLog::where('categoria', 'actividad')->count());
    }

    public function test_el_comando_rechaza_una_categoria_inexistente(): void
    {
        $this->artisan('retention:apply --categoria=inventada')->assertFailed();
    }

    public function test_el_comando_respeta_el_interruptor_general(): void
    {
        config(['retention.enabled' => false]);

        $this->artisan('retention:apply')
            ->assertSuccessful()
            ->expectsOutputToContain('desactivada');

        $this->assertSame(0, RetentionLog::count());
    }

    public function test_la_retencion_esta_programada_diariamente_por_defecto(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($evento): bool => str_contains($evento->command ?? '', 'retention:apply'));

        $this->assertCount(1, $eventos, 'La aplicación automática debe estar programada (RNF-09.05).');

        $evento = $eventos->first();
        $this->assertSame('0 3 * * *', $evento->expression, 'Por defecto debe ejecutarse a diario de madrugada.');
        $this->assertStringContainsString(
            '--schedule',
            $evento->command,
            'La tarea programada debe marcarse como automática en la evidencia.'
        );
    }

    // ------------------------------- periodicidad configurable (RNF-09.05)

    public function test_la_periodicidad_de_la_retencion_es_configurable(): void
    {
        $casos = [
            'daily' => [['frecuencia' => 'daily', 'hora' => '05:15'], '15 5 * * *'],
            'weekly' => [['frecuencia' => 'weekly', 'dia_semana' => 3, 'hora' => '05:15'], '15 5 * * 3'],
            'monthly' => [['frecuencia' => 'monthly', 'dia_mes' => 10, 'hora' => '05:15'], '15 5 10 * *'],
            'quarterly' => [['frecuencia' => 'quarterly', 'dia_mes' => 10, 'hora' => '05:15'], '15 5 10 1-12/3 *'],
            'yearly' => [['frecuencia' => 'yearly', 'mes' => 12, 'dia_mes' => 20, 'hora' => '05:15'], '15 5 20 12 *'],
        ];

        foreach ($casos as $nombre => [$configuracion, $expresionEsperada]) {
            $evento = RetentionSchedule::register(new Schedule, $configuracion + ['activa' => true]);

            $this->assertNotNull($evento, "La frecuencia {$nombre} debería programar la tarea.");
            $this->assertSame($expresionEsperada, $evento->expression, "Frecuencia {$nombre}.");
        }
    }

    public function test_la_tarea_programada_puede_desactivarse(): void
    {
        $this->assertNull(
            RetentionSchedule::register(new Schedule, ['activa' => false]),
            'Con la programación desactivada solo queda la ejecución manual.'
        );

        // Y con la configuración por defecto del proyecto, sigue programada.
        $this->assertNotNull(RetentionSchedule::register(new Schedule, config('retention.schedule')));
    }

    public function test_la_tarea_programada_puede_ejecutar_la_retencion_en_simulacion(): void
    {
        $evento = RetentionSchedule::register(new Schedule, ['activa' => true, 'simulacion' => true]);

        $this->assertNotNull($evento);
        $this->assertStringContainsString('--schedule', $evento->command);
        $this->assertStringContainsString('--dry-run', $evento->command);
    }

    public function test_la_ejecucion_automatica_se_registra_como_tal(): void
    {
        $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->artisan('retention:apply --schedule')->assertSuccessful();

        $this->assertSame(
            count(config('retention.categorias')),
            RetentionLog::where('origen', 'schedule')->count()
        );
        $this->assertSame(0, RetentionLog::where('origen', 'manual')->count());
    }

    public function test_la_ejecucion_manual_se_sigue_registrando_como_manual(): void
    {
        $this->candidato(['created_at' => now()->subDays(2000)]);

        $this->artisan('retention:apply')->assertSuccessful();

        $this->assertSame(0, RetentionLog::where('origen', 'schedule')->count());
        $this->assertSame(
            count(config('retention.categorias')),
            RetentionLog::where('origen', 'manual')->count()
        );
    }
}
