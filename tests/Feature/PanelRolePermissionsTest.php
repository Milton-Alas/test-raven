<?php

namespace Tests\Feature;

use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\DiagnosticRanges\DiagnosticRangeResource;
use App\Filament\Resources\PercentileTables\PercentileTableResource;
use App\Filament\Resources\TestQuestions\TestQuestionResource;
use App\Filament\Resources\TestResults\Pages\ListTestResults;
use App\Filament\Resources\TestResults\TestResultResource;
use App\Filament\Resources\TestSeries\TestSeriesResource;
use App\Filament\Resources\TestSessions\TestSessionResource;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\DiagnosticRange;
use App\Models\PercentileTable;
use App\Models\TestQuestion;
use App\Models\TestResult;
use App\Models\TestSeries;
use App\Models\TestSession;
use App\Models\User;
use App\Services\ActivityLogService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Matriz de permisos por rol del panel (admin, reporter y evaluador).
 *
 * | Recurso                          | admin    | reporter                  | evaluador                |
 * | -------------------------------- | -------- | ------------------------- | ------------------------ |
 * | Candidatos                       | todo     | consulta + CSV            | consulta                 |
 * | Sesiones                         | consulta | consulta                  | consulta                 |
 * | Resultados                       | todo     | consulta + exportaciones  | consulta + PDF individual |
 * | Reactivos, series, baremos y rangos | consulta | sin acceso             | consulta                 |
 * | Usuarios                         | todo     | sin acceso                | sin acceso               |
 *
 * El instrumento (reactivos, series, baremos y rangos) quedó de solo lectura
 * para todos los roles, `admin` incluido: ver TestBankUiRulesTest. Los métodos
 * `canX()` de cada Resource siguen siendo la única fuente de verdad de la
 * autorización —no hay policies ni gates—, así que aquí se comprueban tanto las
 * rutas como esos métodos.
 */
class PanelRolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol, string $email): User
    {
        return User::create([
            'name' => "Usuario {$rol}",
            'email' => $email,
            'password' => 'password',
            'role' => $rol,
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return $this->usuario(User::ROLE_ADMIN, 'admin@example.test');
    }

    private function reporter(): User
    {
        return $this->usuario(User::ROLE_REPORTER, 'reporter@example.test');
    }

    private function evaluador(): User
    {
        return $this->usuario(User::ROLE_EVALUADOR, 'evaluador@example.test');
    }

    /**
     * Autentica a un usuario y limpia la sesión.
     *
     * Hace falta porque el panel incluye `AuthenticateSession`: al cambiar de
     * usuario dentro de la misma prueba, el hash de contraseña guardado en la
     * sesión deja de coincidir y el middleware cierra la sesión, con lo que la
     * petición siguiente acabaría redirigida al login en vez de evaluar el
     * permiso que se quiere comprobar.
     */
    private function autenticarComo(User $usuario): void
    {
        $this->actingAs($usuario);
        $this->flushSession();
    }

    private function candidato(): Candidate
    {
        return Candidate::create([
            'name' => 'Candidato Panel',
            'email' => 'candidato@example.test',
            'dui_nit' => '12345678-9',
            'password' => 'clave-123456',
            'age' => 30,
            'is_active' => true,
            'test_completed' => true,
            'test_completed_at' => now(),
        ]);
    }

    private function sesion(Candidate $candidato): TestSession
    {
        return TestSession::create([
            'candidate_id' => $candidato->id,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
            'status' => 'completed',
            'elapsed_time' => 1800,
        ]);
    }

    /**
     * Resultado con diagnóstico completo.
     *
     * Incluye la norma del rango porque el informe PDF la consulta para
     * interpretar el resultado.
     */
    private function resultado(Candidate $candidato, TestSession $sesion): TestResult
    {
        DiagnosticRange::create([
            'range_number' => 2,
            'range_label' => 'II',
            'percentile_min' => 75,
            'percentile_max' => 94,
            'diagnostic_label' => 'Superior al Término Medio',
            'interpretation' => 'Capacidad intelectual superior al promedio.',
        ]);

        return TestResult::create([
            'test_session_id' => $sesion->id,
            'candidate_id' => $candidato->id,
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
            'calculated_at' => now(),
        ]);
    }

    // -------------------------------------------------------------- evaluador

    public function test_el_rol_evaluador_esta_disponible_en_la_gestion_de_usuarios(): void
    {
        $this->assertArrayHasKey(
            User::ROLE_EVALUADOR,
            User::getRoles(),
            'El alta de usuarios del panel debe ofrecer el rol evaluador.'
        );

        $evaluador = $this->evaluador();

        $this->assertTrue($evaluador->isEvaluador());
        $this->assertFalse($evaluador->isAdmin());
        $this->assertFalse($evaluador->isReporter());
    }

    public function test_el_evaluador_consulta_las_evaluaciones_del_panel(): void
    {
        $this->autenticarComo($this->evaluador());

        $this->get('/admin')->assertOk();

        foreach ([
            '/admin/candidates',
            '/admin/test-sessions',
            '/admin/test-results',
            '/admin/test-questions',
            '/admin/test-series',
            '/admin/percentile-tables',
            '/admin/diagnostic-ranges',
        ] as $ruta) {
            $this->assertSame(
                200,
                $this->get($ruta)->getStatusCode(),
                "El evaluador debería poder consultar {$ruta}."
            );
        }
    }

    public function test_el_evaluador_no_entra_a_la_gestion_de_usuarios(): void
    {
        $this->autenticarComo($this->evaluador());

        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/users/create')->assertForbidden();
    }

    public function test_el_evaluador_no_puede_escribir_ni_borrar_en_las_evaluaciones(): void
    {
        $candidato = $this->candidato();
        $sesion = $this->sesion($candidato);
        $resultado = $this->resultado($candidato, $sesion);

        $this->autenticarComo($this->evaluador());

        // Las páginas de escritura quedan fuera de su alcance.
        $this->get("/admin/candidates/{$candidato->getKey()}/edit")->assertForbidden();
        $this->get("/admin/test-results/{$resultado->getKey()}/edit")->assertForbidden();

        // Y la autorización del Resource responde que no, no solo la interfaz.
        $this->assertFalse(CandidateResource::canCreate());
        $this->assertFalse(CandidateResource::canEdit($candidato));
        $this->assertFalse(CandidateResource::canDelete($candidato));
        $this->assertFalse(CandidateResource::canDeleteAny());
        $this->assertFalse(CandidateResource::canForceDeleteAny());
        $this->assertFalse(CandidateResource::canRestoreAny());

        $this->assertFalse(TestResultResource::canCreate());
        $this->assertFalse(TestResultResource::canEdit($resultado));
        $this->assertFalse(TestResultResource::canDelete($resultado));
        $this->assertFalse(TestResultResource::canDeleteAny());
        $this->assertFalse(TestResultResource::canForceDeleteAny());
        $this->assertFalse(TestResultResource::canRestoreAny());

        $this->assertFalse(TestSessionResource::canCreate());
        $this->assertFalse(TestSessionResource::canEdit($sesion));
        $this->assertFalse(TestSessionResource::canDelete($sesion));
        $this->assertFalse(TestSessionResource::canDeleteAny());
    }

    public function test_el_evaluador_no_ve_las_exportaciones_masivas_pero_si_el_informe_individual(): void
    {
        $candidato = $this->candidato();
        $sesion = $this->sesion($candidato);
        $resultado = $this->resultado($candidato, $sesion);

        $this->autenticarComo($this->evaluador());

        Livewire::test(ListTestResults::class)
            ->assertActionHidden(TestAction::make('exportar_excel')->table())
            ->assertActionHidden(TestAction::make('exportar_csv')->table())
            ->assertActionVisible(TestAction::make('pdf')->table($resultado))
            ->assertActionVisible(TestAction::make('view')->table($resultado));

        Livewire::test(ListCandidates::class)
            ->assertActionHidden(TestAction::make('export')->table()->bulk());

        $this->assertFalse(CandidateResource::canExport());
        $this->assertFalse(TestResultResource::canExport());
    }

    public function test_la_descarga_del_informe_por_un_evaluador_queda_auditada_con_su_rol(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped(
                'La extensión GD de PHP es necesaria para generar el PDF (logo en PNG). '
                .'Instálala con: sudo apt-get install -y php8.4-gd'
            );
        }

        $evaluador = $this->evaluador();
        $candidato = $this->candidato();
        $resultado = $this->resultado($candidato, $this->sesion($candidato));

        $this->autenticarComo($evaluador);

        Livewire::test(ListTestResults::class)
            ->callAction(TestAction::make('pdf')->table($resultado))
            ->assertHasNoActionErrors()
            ->assertFileDownloaded();

        // El informe individual sí se descarga, y deja rastro con el rol nuevo.
        $log = ActivityLog::where('event', ActivityLogService::EVENT_EXPORTED)->firstOrFail();

        $this->assertSame($evaluador->id, $log->causer_id);
        $this->assertSame(User::ROLE_EVALUADOR, $log->properties['exported_by_role']);
        $this->assertSame('pdf', $log->properties['export_format']);
        $this->assertSame($resultado->getKey(), $log->subject_id);
    }

    public function test_un_evaluador_desactivado_no_entra_al_panel(): void
    {
        $evaluador = $this->evaluador();
        $evaluador->update(['is_active' => false]);

        $this->autenticarComo($evaluador);

        $this->get('/admin/candidates')->assertForbidden();
    }

    // -------------------------------------------------- instrumento congelado

    public function test_el_instrumento_es_de_solo_lectura_para_los_tres_roles(): void
    {
        $serie = TestSeries::create(['code' => 'A', 'order' => 1, 'name' => 'Serie A', 'is_active' => true]);

        $pregunta = TestQuestion::create([
            'test_series_id' => $serie->id,
            'question_number' => 1,
            'global_order' => 1,
            'matrix_image_path' => 'matrices/A/A1-0.png',
            'correct_answer' => 3,
            'is_active' => true,
        ]);

        $baremo = PercentileTable::create([
            'age_min' => 18,
            'age_max' => 24,
            'raw_score' => 40,
            'percentile' => 80,
            'norm_group' => 'Montevideo',
            'is_active' => true,
        ]);

        $rango = DiagnosticRange::create([
            'percentile_min' => 75,
            'percentile_max' => 94,
            'range_number' => 2,
            'range_label' => 'II',
            'diagnostic_label' => 'Superior al Término Medio',
            'interpretation' => 'Texto',
        ]);

        // Ni el admin: el instrumento se cambia por seeder o migración.
        foreach ([$this->admin(), $this->reporter(), $this->evaluador()] as $usuario) {
            $this->autenticarComo($usuario);
            $rol = $usuario->role;

            foreach ([
                TestSeriesResource::class,
                TestQuestionResource::class,
                PercentileTableResource::class,
                DiagnosticRangeResource::class,
            ] as $recurso) {
                $this->assertFalse(
                    $recurso::canCreate(),
                    "El rol {$rol} no debería poder crear en {$recurso}."
                );

                $this->assertFalse(
                    $recurso::canDeleteAny(),
                    "El rol {$rol} no debería poder borrar en masa en {$recurso}."
                );
            }

            foreach ([[TestSeriesResource::class, $serie], [TestQuestionResource::class, $pregunta], [PercentileTableResource::class, $baremo], [DiagnosticRangeResource::class, $rango]] as [$recurso, $registro]) {
                $this->assertFalse(
                    $recurso::canEdit($registro),
                    "El rol {$rol} no debería poder editar en {$recurso}."
                );

                $this->assertFalse(
                    $recurso::canDelete($registro),
                    "El rol {$rol} no debería poder borrar en {$recurso}."
                );
            }
        }
    }

    // ----------------------------------------------------------------- reporter

    public function test_el_reportero_conserva_sus_permisos(): void
    {
        $this->autenticarComo($this->reporter());

        // Consulta de las evaluaciones: igual que antes.
        foreach (['/admin/candidates', '/admin/test-sessions', '/admin/test-results'] as $ruta) {
            $this->assertSame(
                200,
                $this->get($ruta)->getStatusCode(),
                "El reportero debería seguir consultando {$ruta}."
            );
        }

        // Instrumento y usuarios: sigue sin acceso.
        foreach ([
            '/admin/users',
            '/admin/test-questions',
            '/admin/test-series',
            '/admin/percentile-tables',
            '/admin/diagnostic-ranges',
        ] as $ruta) {
            $this->assertSame(
                403,
                $this->get($ruta)->getStatusCode(),
                "El reportero no debería entrar a {$ruta}."
            );
        }
    }

    public function test_el_reportero_conserva_sus_exportaciones(): void
    {
        $candidato = $this->candidato();
        $resultado = $this->resultado($candidato, $this->sesion($candidato));

        $this->autenticarComo($this->reporter());

        $this->assertTrue(CandidateResource::canExport());
        $this->assertTrue(TestResultResource::canExport());

        Livewire::test(ListCandidates::class)
            ->assertActionVisible(TestAction::make('export')->table()->bulk());

        Livewire::test(ListTestResults::class)
            ->assertActionVisible(TestAction::make('exportar_excel')->table())
            ->assertActionVisible(TestAction::make('exportar_csv')->table())
            ->assertActionVisible(TestAction::make('pdf')->table($resultado));
    }

    // -------------------------------------------------------------------- admin

    public function test_el_administrador_conserva_la_operacion_diaria_y_los_usuarios(): void
    {
        $candidato = $this->candidato();
        $resultado = $this->resultado($candidato, $this->sesion($candidato));

        $this->autenticarComo($this->admin());

        $this->get('/admin/users')->assertOk();
        $this->get('/admin/candidates')->assertOk();
        $this->get('/admin/test-results')->assertOk();

        // Candidatos y resultados siguen siendo su operación diaria.
        $this->assertTrue(CandidateResource::canEdit($candidato));
        $this->assertTrue(CandidateResource::canDelete($candidato));
        $this->assertTrue(CandidateResource::canDeleteAny());
        $this->assertTrue(TestResultResource::canEdit($resultado));
        $this->assertTrue(CandidateResource::canExport());
        $this->assertTrue(TestResultResource::canExport());
    }
}
