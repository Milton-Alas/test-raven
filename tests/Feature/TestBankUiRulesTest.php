<?php

namespace Tests\Feature;

use App\Filament\Resources\DiagnosticRanges\Pages\EditDiagnosticRange;
use App\Filament\Resources\DiagnosticRanges\Pages\ListDiagnosticRanges;
use App\Filament\Resources\PercentileTables\Pages\EditPercentileTable;
use App\Filament\Resources\TestQuestions\Pages\ListTestQuestions;
use App\Filament\Resources\TestSeries\Pages\EditTestSeries;
use App\Filament\Resources\TestSeries\Pages\ListTestSeries;
use App\Models\Candidate;
use App\Models\DiagnosticRange;
use App\Models\PercentileTable;
use App\Models\TestQuestion;
use App\Models\TestSeries;
use App\Models\TestSession;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Reglas de interfaz del panel para el contenido histórico del test.
 *
 * El modelo ya impide borrar y reescribir (ver TestBankIntegrityTest); aquí se
 * comprueba que la interfaz no ofrezca esas acciones y que el administrador
 * entienda por qué antes de intentarlo.
 */
class TestBankUiRulesTest extends TestCase
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

    private function iniciarTestEnCurso(): void
    {
        $candidate = Candidate::create([
            'name' => 'En Curso',
            'email' => 'encurso@example.test',
            'dui_nit' => '12345678-9',
            'password' => 'clave-123456',
            'age' => 25,
            'is_active' => true,
        ]);

        TestSession::create([
            'candidate_id' => $candidate->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    // ------------------------------------------------------- sin borrado

    public function test_las_tablas_del_banco_no_ofrecen_borrado(): void
    {
        $serie = $this->serie();
        $this->pregunta($serie);
        $this->actingAs($this->admin());

        // No están ocultas: simplemente no existen en la tabla.
        Livewire::test(ListTestSeries::class)
            ->assertActionDoesNotExist(TestAction::make('delete')->table($serie))
            ->assertActionDoesNotExist(TestAction::make('delete')->table()->bulk());

        Livewire::test(ListTestQuestions::class)
            ->assertActionDoesNotExist(TestAction::make('delete')->table()->bulk());
    }

    public function test_la_pagina_de_edicion_no_tiene_acciones_de_borrado(): void
    {
        $serie = $this->serie();
        $this->actingAs($this->admin());

        $pregunta = $this->pregunta($serie);

        foreach ([
            "/admin/test-series/{$serie->getKey()}/edit",
            "/admin/test-questions/{$pregunta->getKey()}/edit",
        ] as $ruta) {
            $this->get($ruta)
                ->assertOk()
                ->assertDontSee('Eliminar')
                ->assertDontSee('Borrar');
        }
    }

    // ------------------------------------------- solo consulta al editar

    public function test_el_formulario_de_edicion_explica_que_es_consulta(): void
    {
        $serie = $this->serie();

        $this->actingAs($this->admin())
            ->get("/admin/test-series/{$serie->getKey()}/edit")
            ->assertOk()
            ->assertSee('Registro histórico');
    }

    public function test_los_campos_de_contenido_estan_deshabilitados_al_editar(): void
    {
        $serie = $this->serie();

        // El campo de contenido se renderiza deshabilitado: el administrador no
        // puede cambiarlo desde la interfaz.
        $html = $this->actingAs($this->admin())
            ->get("/admin/test-series/{$serie->getKey()}/edit")
            ->assertOk()
            ->getContent();

        // El orden de atributos del HTML no es estable, así que se extrae la
        // etiqueta del campo y se comprueba si incluye "disabled".
        $this->assertInputIsDisabled($html, 'form.code');
        $this->assertInputIsDisabled($html, 'form.name');

        // La vigencia no se deshabilita (es un checkbox y no expone id propio):
        // queda cubierto por el guardado real que se hace más abajo.

        // ...pero la vigencia sí se puede cambiar, para retirar la serie sin
        // destruir la historia.
        Livewire::test(EditTestSeries::class, ['record' => $serie->getKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($serie->fresh()->is_active);
    }

    public function test_el_baremo_y_el_rango_muestran_sus_campos_en_consulta(): void
    {
        $this->actingAs($this->admin());

        $tabla = PercentileTable::create([
            'age_min' => 18,
            'age_max' => 24,
            'raw_score' => 40,
            'percentile' => 80,
            'norm_group' => 'Montevideo',
            'is_active' => true,
        ]);

        Livewire::test(EditPercentileTable::class, ['record' => $tabla->getKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($tabla->fresh()->is_active);

        $rango = DiagnosticRange::create([
            'percentile_min' => 75,
            'percentile_max' => 94,
            'range_number' => 2,
            'range_label' => 'II',
            'diagnostic_label' => 'Superior al Término Medio',
            'interpretation' => 'Texto',
        ]);

        // Este modelo no tiene columna is_active: todo queda en consulta, y
        // cualquier intento de guardar un cambio de contenido se rechaza con un
        // mensaje que explica el motivo.
        Livewire::test(EditDiagnosticRange::class, ['record' => $rango->getKey()])
            ->fillForm(['diagnostic_label' => 'Otro diagnóstico'])
            ->call('save');

        $this->assertSame('Superior al Término Medio', $rango->fresh()->diagnostic_label);
    }

    // -------------------------------------------------- alta condicionada

    public function test_no_se_puede_crear_contenido_mientras_hay_un_test_en_curso(): void
    {
        $this->iniciarTestEnCurso();
        $this->actingAs($this->admin());

        Livewire::test(ListTestSeries::class)
            ->assertActionDisabled(TestAction::make('create'));

        Livewire::test(ListTestQuestions::class)
            ->assertActionDisabled(TestAction::make('create'));

        Livewire::test(ListDiagnosticRanges::class)
            ->assertActionDisabled(TestAction::make('create'));
    }

    public function test_se_puede_crear_contenido_cuando_no_hay_tests_en_curso(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListTestSeries::class)
            ->assertActionEnabled(TestAction::make('create'));
    }

    /**
     * Comprueba que el campo se renderiza deshabilitado, sin depender del orden
     * de los atributos del HTML.
     */
    private function assertInputIsDisabled(string $html, string $id): void
    {
        $etiqueta = $this->inputTagFor($html, $id);

        $this->assertStringContainsString(
            'disabled',
            $etiqueta,
            "El campo {$id} debe renderizarse deshabilitado."
        );
    }

    /**
     * Devuelve la etiqueta <input> del campo indicado.
     */
    private function inputTagFor(string $html, string $id): string
    {
        $encontrado = preg_match(
            '/<input[^>]*id="'.preg_quote($id, '/').'"[^>]*>/s',
            $html,
            $coincidencias
        );

        $this->assertSame(1, $encontrado, "No se encontró el campo {$id} en el formulario.");

        return $coincidencias[0];
    }
}
