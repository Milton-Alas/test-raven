<?php

namespace Tests\Feature;

use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\TestResults\Pages\ListTestResults;
use App\Filament\Resources\TestSessions\Pages\ListTestSessions;
use App\Models\Candidate;
use App\Models\TestResult;
use App\Models\TestSession;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Acciones destructivas del panel: el rol `reporter` debe poder consultar y
 * exportar, pero NO borrar, borrar definitivamente ni restaurar.
 *
 * El panel no define policies y Filament está en modo de autorización no
 * estricto: cuando no existe policy ni método `canX()` en el Resource,
 * Filament permite la acción por defecto. Por eso hay que cubrir tanto las
 * acciones masivas (`canDeleteAny`, `canForceDeleteAny`, `canRestoreAny`) como
 * las individuales (`canDelete`, `canForceDelete`, `canRestore`).
 */
class PanelDestructiveActionsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function reporter(): User
    {
        return User::create([
            'name' => 'Reportero',
            'email' => 'reporter@example.test',
            'password' => 'password',
            'role' => User::ROLE_REPORTER,
            'is_active' => true,
        ]);
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

    private function sesion(Candidate $candidate): TestSession
    {
        return TestSession::create([
            'candidate_id' => $candidate->id,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
            'status' => 'completed',
            'elapsed_time' => 1800,
        ]);
    }

    private function resultado(Candidate $candidate, TestSession $session): TestResult
    {
        return TestResult::create([
            'test_session_id' => $session->id,
            'candidate_id' => $candidate->id,
            'total_score' => 40,
            'percentile' => 80,
            'diagnostic_range' => 2,
            'diagnostic_label' => 'Superior al Término Medio',
            'is_valid' => true,
            'total_time_seconds' => 1800,
        ]);
    }

    // ---------------------------------------------------------------- candidatos

    public function test_el_reportero_no_ve_las_acciones_masivas_destructivas_de_candidatos(): void
    {
        $this->candidato();
        $this->actingAs($this->reporter());

        Livewire::test(ListCandidates::class)
            ->assertActionHidden(TestAction::make('delete')->table()->bulk())
            ->assertActionHidden(TestAction::make('forceDelete')->table()->bulk())
            ->assertActionHidden(TestAction::make('restore')->table()->bulk());
    }

    public function test_el_administrador_si_ve_las_acciones_masivas_de_candidatos(): void
    {
        $this->candidato();
        $this->actingAs($this->admin());

        // forceDelete y restore solo tienen sentido con la papelera a la vista.
        Livewire::test(ListCandidates::class)
            ->filterTable('trashed', true)
            ->assertActionVisible(TestAction::make('delete')->table()->bulk())
            ->assertActionVisible(TestAction::make('forceDelete')->table()->bulk())
            ->assertActionVisible(TestAction::make('restore')->table()->bulk());
    }

    public function test_el_reportero_no_puede_entrar_a_editar_un_candidato(): void
    {
        // La página de edición (donde viven borrar, borrado permanente y
        // restaurar) está reservada al admin: el reportero recibe 403, así que
        // esas acciones le son inalcanzables desde ahí.
        $candidate = $this->candidato();

        $this->actingAs($this->reporter())
            ->get("/admin/candidates/{$candidate->getKey()}/edit")
            ->assertForbidden();
    }

    public function test_el_administrador_si_puede_forzar_ni_restaurar_un_candidato(): void
    {
        $candidate = $this->candidato();
        $candidate->delete();

        $this->actingAs($this->admin());

        Livewire::test(EditCandidate::class, ['record' => $candidate->getKey()])
            ->assertActionVisible(TestAction::make('forceDelete'))
            ->assertActionVisible(TestAction::make('restore'));
    }

    // ----------------------------------------------------------------- resultados

    public function test_el_reportero_no_ve_acciones_destructivas_en_resultados(): void
    {
        $candidate = $this->candidato();
        $session = $this->sesion($candidate);
        $resultado = $this->resultado($candidate, $session);

        $this->actingAs($this->reporter());

        Livewire::test(ListTestResults::class)
            ->filterTable('trashed', true)
            ->assertActionHidden(TestAction::make('delete')->table()->bulk())
            ->assertActionHidden(TestAction::make('forceDelete')->table()->bulk())
            ->assertActionHidden(TestAction::make('restore')->table()->bulk());

        // La acción de exportar PDF sí debe seguir disponible para el reporter.
        Livewire::test(ListTestResults::class)
            ->assertActionVisible(TestAction::make('pdf')->table($resultado));
    }

    public function test_el_reportero_no_puede_entrar_a_editar_un_resultado(): void
    {
        $candidate = $this->candidato();
        $session = $this->sesion($candidate);
        $resultado = $this->resultado($candidate, $session);

        $this->actingAs($this->reporter())
            ->get("/admin/test-results/{$resultado->getKey()}/edit")
            ->assertForbidden();
    }

    // ------------------------------------------------------------------ sesiones

    public function test_las_sesiones_son_de_solo_lectura_para_todos(): void
    {
        $candidate = $this->candidato();
        $this->sesion($candidate);

        // La sesión del test es evidencia del proceso: nadie la borra, ni el admin.
        foreach ([$this->admin(), $this->reporter()] as $usuario) {
            $this->actingAs($usuario);

            Livewire::test(ListTestSessions::class)
                ->assertActionHidden(TestAction::make('delete')->table()->bulk())
                ->assertActionHidden(TestAction::make('forceDelete')->table()->bulk())
                ->assertActionHidden(TestAction::make('restore')->table()->bulk());
        }
    }
}
