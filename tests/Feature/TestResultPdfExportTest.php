<?php

namespace Tests\Feature;

use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\TestResults\Pages\ListTestResults;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\DiagnosticRange;
use App\Models\TestResult;
use App\Models\TestSession;
use App\Models\User;
use App\Services\ActivityLogService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Exportación del informe PDF individual con el diagnóstico completo.
 *
 * Requisitos cubiertos:
 *  1. El rol `reporter` puede exportar el PDF individual desde el panel.
 *  2. Cada exportación queda registrada en activity_logs con el evento
 *     "exported", para poder auditar quién descargó qué.
 */
class TestResultPdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // DomPDF incrusta el logo del informe en PNG y para eso necesita la
        // extensión GD. Sin ella no hay nada que probar aquí: la aplicación ya
        // avisa de forma explícita en el panel ("Falta la extensión GD de PHP").
        if (! extension_loaded('gd')) {
            $this->markTestSkipped(
                'La extensión GD de PHP es necesaria para generar el PDF (logo en PNG). '
                .'Instálala con: sudo apt-get install -y php8.4-gd'
            );
        }
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin de Prueba',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function reporter(): User
    {
        return User::create([
            'name' => 'Reportero de Prueba',
            'email' => 'reporter@example.test',
            'password' => 'password',
            'role' => User::ROLE_REPORTER,
            'is_active' => true,
        ]);
    }

    /**
     * Resultado de test con diagnóstico completo, listo para exportar.
     */
    private function resultado(): TestResult
    {
        // La vista del informe consulta la interpretación por rango diagnóstico.
        DiagnosticRange::create([
            'range_number' => 2,
            'range_label' => 'II',
            'percentile_min' => 75,
            'percentile_max' => 94,
            'diagnostic_label' => 'Superior al Término Medio',
            'interpretation' => 'Capacidad intelectual definitivamente superior al promedio.',
        ]);

        $candidate = Candidate::create([
            'name' => 'Candidato Informe',
            'email' => 'informe@example.test',
            'dui_nit' => '12345678-9',
            'password' => 'clave-123456',
            'age' => 30,
            'occupation' => 'Docente',
            'education_level' => 'Universitario',
            'is_active' => true,
            'test_completed' => true,
            'test_completed_at' => now(),
        ]);

        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
            'status' => 'completed',
            'elapsed_time' => 1800,
        ]);

        return TestResult::create([
            'test_session_id' => $session->id,
            'candidate_id' => $candidate->id,
            'series_a_score' => 10,
            'series_b_score' => 9,
            'series_c_score' => 8,
            'series_d_score' => 7,
            'series_e_score' => 6,
            'total_score' => 40,
            'percentile' => 80,
            'diagnostic_range' => 2,
            'diagnostic_label' => 'Superior al Término Medio',
            'is_valid' => true,
            'total_time_seconds' => 1800,
            'average_time_per_question' => 30,
            'calculated_at' => now(),
        ]);
    }

    public function test_el_reportero_ve_y_ejecuta_la_exportacion_del_pdf(): void
    {
        $reporter = $this->reporter();
        $resultado = $this->resultado();

        $this->actingAs($reporter);

        Livewire::test(ListTestResults::class)
            ->assertActionVisible(TestAction::make('pdf')->table($resultado))
            ->callAction(TestAction::make('pdf')->table($resultado))
            ->assertHasNoActionErrors()
            ->assertFileDownloaded();

        $this->assertDatabaseHas('activity_logs', [
            'event' => ActivityLogService::EVENT_EXPORTED,
            'causer_type' => User::class,
            'causer_id' => $reporter->id,
            'subject_type' => TestResult::class,
            'subject_id' => $resultado->id,
        ]);
    }

    public function test_la_exportacion_queda_auditada_con_el_detalle_de_lo_descargado(): void
    {
        $reporter = $this->reporter();
        $resultado = $this->resultado();

        $this->actingAs($reporter);

        Livewire::test(ListTestResults::class)
            ->callAction(TestAction::make('pdf')->table($resultado));

        $log = ActivityLog::where('event', ActivityLogService::EVENT_EXPORTED)->firstOrFail();

        $this->assertSame(User::class, $log->causer_type);
        $this->assertSame($reporter->id, $log->causer_id);
        $this->assertSame('Reportero de Prueba', $log->properties['exported_by_name']);
        $this->assertSame(User::ROLE_REPORTER, $log->properties['exported_by_role']);
        $this->assertSame('pdf', $log->properties['export_format']);
        $this->assertSame('diagnostico_completo', $log->properties['report_scope']);
        $this->assertSame($resultado->candidate_id, $log->properties['candidate_id']);
        $this->assertSame('Candidato Informe', $log->properties['candidate_name']);
        $this->assertSame(40, $log->properties['total_score']);
        $this->assertSame(80, $log->properties['percentile']);
        $this->assertSame(2, $log->properties['diagnostic_range']);
        $this->assertStringContainsString('resultado-candidato-informe-', $log->properties['filename']);
        $this->assertStringContainsString('Reportero de Prueba', $log->description);
        $this->assertNotNull($log->properties['exported_at']);
    }

    public function test_el_administrador_tambien_genera_registro_al_exportar(): void
    {
        $admin = $this->admin();
        $resultado = $this->resultado();

        $this->actingAs($admin);

        Livewire::test(ListTestResults::class)
            ->callAction(TestAction::make('pdf')->table($resultado));

        $log = ActivityLog::where('event', ActivityLogService::EVENT_EXPORTED)->firstOrFail();

        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame(User::ROLE_ADMIN, $log->properties['exported_by_role']);
    }

    public function test_dos_exportaciones_dejan_dos_registros(): void
    {
        $reporter = $this->reporter();
        $resultado = $this->resultado();

        $this->actingAs($reporter);

        Livewire::test(ListTestResults::class)
            ->callAction(TestAction::make('pdf')->table($resultado));

        Livewire::test(ListTestResults::class)
            ->callAction(TestAction::make('pdf')->table($resultado));

        $this->assertSame(
            2,
            ActivityLog::where('event', ActivityLogService::EVENT_EXPORTED)->count(),
            'Cada descarga debe auditarse por separado.'
        );
    }

    public function test_el_boton_de_pdf_es_visible_en_la_pagina_de_resultados(): void
    {
        $reporter = $this->reporter();
        $this->resultado();

        $this->actingAs($reporter)
            ->get('/admin/test-results')
            ->assertOk()
            ->assertSee('Descargar PDF');
    }

    /**
     * La otra exportación del panel (CSV de candidatos) usa el mismo evento.
     */
    public function test_la_exportacion_csv_de_candidatos_tambien_queda_auditada(): void
    {
        $reporter = $this->reporter();
        $candidate = $this->resultado()->candidate;

        $this->actingAs($reporter);

        Livewire::test(ListCandidates::class)
            ->selectTableRecords([$candidate->getKey()])
            ->callAction(TestAction::make('export')->table()->bulk());

        $log = ActivityLog::where('event', ActivityLogService::EVENT_EXPORTED)
            ->where('properties->export_format', 'csv')
            ->firstOrFail();

        $this->assertSame($reporter->id, $log->causer_id);
        $this->assertSame(1, $log->properties['candidate_count']);
        $this->assertSame([$candidate->id], $log->properties['candidate_ids']);
        $this->assertTrue($log->properties['includes_pii']);
    }
}
