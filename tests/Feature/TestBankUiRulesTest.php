<?php

namespace Tests\Feature;

use App\Filament\Resources\DiagnosticRanges\Pages\ListDiagnosticRanges;
use App\Filament\Resources\PercentileTables\Pages\ListPercentileTables;
use App\Filament\Resources\TestQuestions\Pages\ListTestQuestions;
use App\Filament\Resources\TestSeries\Pages\ListTestSeries;
use App\Models\DiagnosticRange;
use App\Models\PercentileTable;
use App\Models\TestQuestion;
use App\Models\TestSeries;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Reglas de interfaz del panel para el instrumento del test.
 *
 * El instrumento (series, reactivos, baremos y rangos diagnósticos) es contenido
 * normalizado: el panel solo lo muestra. No hay alta, edición ni borrado para
 * ningún rol, `admin` incluido, porque cualquier cambio real debe entrar por
 * seeder o migración, con control de versiones y rastro. El modelo lo refuerza
 * por su cuenta (ver TestBankIntegrityTest); aquí se comprueba la interfaz.
 */
class TestBankUiRulesTest extends TestCase
{
    use RefreshDatabase;

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

    private function serie(): TestSeries
    {
        return TestSeries::create(['code' => 'A', 'order' => 1, 'name' => 'Serie A', 'is_active' => true]);
    }

    private function pregunta(TestSeries $serie): TestQuestion
    {
        return TestQuestion::create([
            'test_series_id' => $serie->id,
            'question_number' => 1,
            'global_order' => 1,
            'matrix_image_path' => 'matrices/A/A1-0.png',
            'correct_answer' => 3,
            'is_active' => true,
        ]);
    }

    private function baremo(): PercentileTable
    {
        return PercentileTable::create([
            'age_min' => 18,
            'age_max' => 24,
            'raw_score' => 40,
            'percentile' => 80,
            'norm_group' => 'Montevideo',
            'is_active' => true,
        ]);
    }

    private function rango(): DiagnosticRange
    {
        return DiagnosticRange::create([
            'percentile_min' => 75,
            'percentile_max' => 94,
            'range_number' => 2,
            'range_label' => 'II',
            'diagnostic_label' => 'Superior al Término Medio',
            'interpretation' => 'Texto',
        ]);
    }

    /**
     * Autentica a un usuario y limpia la sesión.
     *
     * Hace falta porque el panel incluye `AuthenticateSession`: al cambiar de
     * usuario dentro de la misma prueba, el hash de contraseña guardado en la
     * sesión deja de coincidir y el middleware cierra la sesión (la petición
     * siguiente acabaría redirigida al login en vez de evaluar el permiso).
     */
    private function autenticarComo(User $usuario): void
    {
        $this->actingAs($usuario);
        $this->flushSession();
    }

    /**
     * Rutas de alta y edición del instrumento, con un registro real de cada
     * recurso para que el 403 no dependa de un identificador inexistente.
     *
     * @return array<string, string>
     */
    private function rutasDeAltaYEdicion(): array
    {
        $serie = $this->serie();
        $pregunta = $this->pregunta($serie);
        $baremo = $this->baremo();
        $rango = $this->rango();

        return [
            'alta de series' => '/admin/test-series/create',
            'edición de series' => "/admin/test-series/{$serie->getKey()}/edit",
            'alta de reactivos' => '/admin/test-questions/create',
            'edición de reactivos' => "/admin/test-questions/{$pregunta->getKey()}/edit",
            'alta de baremos' => '/admin/percentile-tables/create',
            'edición de baremos' => "/admin/percentile-tables/{$baremo->getKey()}/edit",
            'alta de rangos' => '/admin/diagnostic-ranges/create',
            'edición de rangos' => "/admin/diagnostic-ranges/{$rango->getKey()}/edit",
        ];
    }

    // ------------------------------------------------- sin alta ni edición

    public function test_las_paginas_de_alta_y_edicion_del_instrumento_estan_cerradas_para_todos_los_roles(): void
    {
        $rutas = $this->rutasDeAltaYEdicion();

        foreach ([$this->admin(), $this->reporter(), $this->evaluador()] as $usuario) {
            $this->autenticarComo($usuario);

            foreach ($rutas as $descripcion => $ruta) {
                $this->assertSame(
                    403,
                    $this->get($ruta)->getStatusCode(),
                    "El rol {$usuario->role} no debería poder entrar a la {$descripcion}."
                );
            }
        }
    }

    public function test_las_tablas_del_instrumento_no_ofrecen_alta_edicion_ni_borrado(): void
    {
        $serie = $this->serie();
        $pregunta = $this->pregunta($serie);

        // Con el rol más privilegiado: si el admin no ve estas acciones, ningún
        // otro rol las ve.
        $this->autenticarComo($this->admin());

        $tablas = [
            'series' => [ListTestSeries::class, $serie],
            'reactivos' => [ListTestQuestions::class, $pregunta],
            'baremos' => [ListPercentileTables::class, $this->baremo()],
            'rangos' => [ListDiagnosticRanges::class, $this->rango()],
        ];

        foreach ($tablas as $nombre => [$pagina, $registro]) {
            // No están ocultas: simplemente no existen en la tabla.
            Livewire::test($pagina)
                ->assertActionDoesNotExist(TestAction::make('create'))
                ->assertActionDoesNotExist(TestAction::make('edit')->table($registro))
                ->assertActionDoesNotExist(TestAction::make('delete')->table($registro))
                ->assertActionDoesNotExist(TestAction::make('delete')->table()->bulk());
        }
    }

    // -------------------------------------------------------- solo consulta

    public function test_el_instrumento_se_consulta_desde_el_panel_con_su_contenido(): void
    {
        $this->serie();
        $this->baremo();
        $this->rango();

        foreach ([$this->admin(), $this->evaluador()] as $usuario) {
            $this->autenticarComo($usuario);

            // La lista es la vía de consulta: muestra el contenido del
            // instrumento sin ofrecer ninguna forma de cambiarlo.
            $this->get('/admin/test-series')
                ->assertOk()
                ->assertSee('Serie A');

            foreach ([
                '/admin/test-questions',
                '/admin/percentile-tables',
                '/admin/diagnostic-ranges',
            ] as $ruta) {
                $this->get($ruta)->assertOk();
            }
        }
    }
}
