<?php

namespace Tests\Feature;

use App\Exceptions\HistoricalDataException;
use App\Models\AnswerOption;
use App\Models\Candidate;
use App\Models\DiagnosticRange;
use App\Models\PercentileTable;
use App\Models\TestAnswer;
use App\Models\TestQuestion;
use App\Models\TestSeries;
use App\Models\TestSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El contenido del test (series, preguntas, opciones y tablas normativas) no se
 * puede borrar ni reescribir una vez que forma parte de resultados emitidos.
 *
 * Causa raíz que se ataca aquí: `test_answers.test_question_id` tiene borrado en
 * cascada, así que eliminar una pregunta elimina las respuestas de todos los
 * candidatos que la respondieron, y un recálculo posterior daría un puntaje
 * sobre menos ítems sin que nadie lo note. Las tablas normativas tienen el mismo
 * problema: cambiarlas altera el percentil de cualquier recálculo.
 */
class TestBankIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function serie(): TestSeries
    {
        return TestSeries::create([
            'code' => 'A',
            'order' => 1,
            'name' => 'Serie A',
            'is_active' => true,
        ]);
    }

    private function pregunta(TestSeries $serie, int $numero = 1): TestQuestion
    {
        return TestQuestion::create([
            'test_series_id' => $serie->id,
            'question_number' => $numero,
            'global_order' => $numero,
            'matrix_image_path' => 'matrices/A/A1-0.png',
            'correct_answer' => 3,
            'is_active' => true,
        ]);
    }

    private function candidatoConRespuesta(TestQuestion $pregunta): TestAnswer
    {
        $candidate = Candidate::create([
            'name' => 'Candidato Historia',
            'email' => 'historia@example.test',
            'dui_nit' => '12345678-9',
            'password' => 'clave-123456',
            'age' => 30,
            'is_active' => true,
        ]);

        $session = TestSession::create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
            'elapsed_time' => 1800,
        ]);

        return TestAnswer::create([
            'test_session_id' => $session->id,
            'test_question_id' => $pregunta->id,
            'selected_answer' => 3,
            'is_correct' => true,
            'answered_at' => now(),
        ]);
    }

    // ------------------------------------------------------------ borrado

    public function test_no_se_puede_borrar_una_pregunta_con_respuestas(): void
    {
        $pregunta = $this->pregunta($this->serie());
        $respuesta = $this->candidatoConRespuesta($pregunta);

        try {
            $pregunta->delete();
            $this->fail('El borrado de una pregunta con respuestas debía bloquearse.');
        } catch (HistoricalDataException $e) {
            $this->assertStringContainsString('evidencia histórica', $e->getMessage());
        }

        // Lo importante: la respuesta del candidato sigue existiendo.
        $this->assertDatabaseHas('test_answers', ['id' => $respuesta->id]);
        $this->assertDatabaseHas('test_questions', ['id' => $pregunta->id]);
    }

    public function test_no_se_puede_borrar_una_serie(): void
    {
        $serie = $this->serie();
        $this->pregunta($serie);

        $this->expectException(HistoricalDataException::class);
        $serie->delete();
    }

    public function test_no_se_puede_borrar_una_opcion_de_respuesta(): void
    {
        $opcion = AnswerOption::create([
            'test_question_id' => $this->pregunta($this->serie())->id,
            'option_number' => 1,
            'option_image_path' => 'options/A/A1/A1-1.png',
        ]);

        $this->expectException(HistoricalDataException::class);
        $opcion->delete();
    }

    public function test_no_se_puede_borrar_una_tabla_de_percentiles(): void
    {
        $tabla = PercentileTable::create([
            'age_min' => 18,
            'age_max' => 24,
            'raw_score' => 40,
            'percentile' => 80,
            'norm_group' => 'montevideo',
        ]);

        $this->expectException(HistoricalDataException::class);
        $tabla->delete();
    }

    public function test_no_se_puede_borrar_un_rango_diagnostico(): void
    {
        $rango = DiagnosticRange::create([
            'percentile_min' => 75,
            'percentile_max' => 94,
            'range_number' => 2,
            'range_label' => 'II',
            'diagnostic_label' => 'Superior al Término Medio',
            'interpretation' => 'Texto',
        ]);

        $this->expectException(HistoricalDataException::class);
        $rango->delete();
    }

    // -------------------------------------------------------------- edición

    public function test_no_se_puede_cambiar_la_respuesta_correcta(): void
    {
        $pregunta = $this->pregunta($this->serie());

        try {
            $pregunta->update(['correct_answer' => 5]);
            $this->fail('Cambiar la respuesta correcta debía bloquearse.');
        } catch (HistoricalDataException $e) {
            $this->assertStringContainsString('correct_answer', $e->getMessage());
        }

        $this->assertSame(3, $pregunta->fresh()->correct_answer);
    }

    public function test_no_se_puede_mover_una_pregunta_de_orden_o_de_serie(): void
    {
        $serie = $this->serie();
        $otra = TestSeries::create(['code' => 'B', 'order' => 2, 'name' => 'Serie B', 'is_active' => true]);
        $pregunta = $this->pregunta($serie);

        $this->expectException(HistoricalDataException::class);
        $pregunta->update(['test_series_id' => $otra->id]);
    }

    public function test_no_se_puede_cambiar_el_percentil_ni_el_puntaje_bruto(): void
    {
        $tabla = PercentileTable::create([
            'age_min' => 18,
            'age_max' => 24,
            'raw_score' => 40,
            'percentile' => 80,
            'norm_group' => 'montevideo',
        ]);

        try {
            $tabla->update(['percentile' => 95]);
            $this->fail('Cambiar el percentil debía bloquearse.');
        } catch (HistoricalDataException $e) {
            $this->assertStringContainsString('percentile', $e->getMessage());
        }

        $this->assertSame(80, $tabla->fresh()->percentile);
    }

    public function test_no_se_puede_cambiar_los_limites_de_un_rango_diagnostico(): void
    {
        $rango = DiagnosticRange::create([
            'percentile_min' => 75,
            'percentile_max' => 94,
            'range_number' => 2,
            'range_label' => 'II',
            'diagnostic_label' => 'Superior al Término Medio',
            'interpretation' => 'Texto',
        ]);

        $this->expectException(HistoricalDataException::class);
        $rango->update(['diagnostic_label' => 'Otro diagnóstico']);
    }

    // -------------------------------------------------------- desactivación

    public function test_se_puede_desactivar_una_pregunta_sin_destruir_la_historia(): void
    {
        $pregunta = $this->pregunta($this->serie());
        $respuesta = $this->candidatoConRespuesta($pregunta);

        $pregunta->update(['is_active' => false]);

        $this->assertFalse($pregunta->fresh()->is_active);
        // La respuesta sigue intacta: retirar un ítem no borra la historia.
        $this->assertDatabaseHas('test_answers', ['id' => $respuesta->id]);
    }

    public function test_se_puede_desactivar_una_tabla_normativa(): void
    {
        $tabla = PercentileTable::create([
            'age_min' => 18,
            'age_max' => 24,
            'raw_score' => 40,
            'percentile' => 80,
            'norm_group' => 'montevideo',
        ]);

        $tabla->update(['is_active' => false]);

        $this->assertFalse($tabla->fresh()->is_active);
    }

    // ------------------------------------------------------------ creación

    public function test_no_se_puede_crear_contenido_mientras_hay_un_test_en_curso(): void
    {
        $serie = $this->serie();

        $candidate = Candidate::create([
            'name' => 'Candidato En Curso',
            'email' => 'encurso@example.test',
            'dui_nit' => '87654321-0',
            'password' => 'clave-123456',
            'age' => 25,
            'is_active' => true,
        ]);

        TestSession::create([
            'candidate_id' => $candidate->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->expectException(HistoricalDataException::class);
        $this->pregunta($serie, 2);
    }

    public function test_se_puede_crear_contenido_cuando_no_hay_tests_en_curso(): void
    {
        $serie = $this->serie();

        // Sin sesiones activas (la única existente está finalizada).
        $candidate = Candidate::create([
            'name' => 'Candidato Terminado',
            'email' => 'terminado@example.test',
            'dui_nit' => '11111111-1',
            'password' => 'clave-123456',
            'age' => 25,
            'is_active' => true,
        ]);

        TestSession::create([
            'candidate_id' => $candidate->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $pregunta = $this->pregunta($serie, 3);

        $this->assertDatabaseHas('test_questions', ['id' => $pregunta->id]);
    }
}
