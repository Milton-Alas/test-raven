<?php

namespace Tests\Feature;

use App\Filament\Resources\Candidates\Tables\CandidatesTable;
use App\Models\Candidate;
use App\Models\TestResult;
use App\Models\TestSession;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exportación CSV de candidatos.
 *
 * Motivo de esta prueba: al añadir la restricción de las acciones masivas por
 * rol, las llamadas a `DeleteBulkAction::make()` quedaron insertadas por error
 * dentro del array de cabeceras del CSV, y desaparecieron tres columnas. El
 * archivo exportado quedaba descuadrado (más columnas de datos que de
 * cabeceras) sin que nada fallara de forma visible.
 */
class CandidateCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private function candidatoConResultado(): Candidate
    {
        $candidate = Candidate::create([
            'name' => 'Candidato CSV',
            'email' => 'csv@example.test',
            'dui_nit' => '12345678-9',
            'password' => 'clave-123456',
            'age' => 30,
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

        TestResult::create([
            'test_session_id' => $session->id,
            'candidate_id' => $candidate->id,
            'total_score' => 40,
            'percentile' => 80,
            'diagnostic_range' => 2,
            'diagnostic_label' => 'Superior al Término Medio',
            'is_valid' => true,
            'total_time_seconds' => 1800,
        ]);

        return $candidate;
    }

    /**
     * Ejecuta la exportación y devuelve el CSV como líneas.
     *
     * @return array<int, array<int, string|null>>
     */
    private function exportar(Candidate $candidate): array
    {
        $response = CandidatesTable::exportToCsv(
            new EloquentCollection([$candidate->fresh()])
        );

        ob_start();
        $response->sendContent();
        $csv = (string) ob_get_clean();

        // Se quita el BOM UTF-8 que se añade para Excel.
        $csv = ltrim($csv, "\xEF\xBB\xBF");

        return array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
    }

    public function test_las_cabeceras_del_csv_estan_completas_y_en_orden(): void
    {
        $filas = $this->exportar($this->candidatoConResultado());

        $this->assertSame([
            'ID',
            'Nombre',
            'Correo',
            'DUI/NIT',
            'Edad',
            'Activo',
            'Test completado',
            'Percentil',
            'Diagnostico',
            'Puntaje total',
            'Fecha finalizacion test',
        ], $filas[0], 'Las cabeceras del CSV deben estar completas y en orden.');
    }

    public function test_el_csv_no_contiene_restos_de_codigo_de_acciones(): void
    {
        $csv = implode("\n", array_map(
            fn (array $fila): string => implode(',', array_map(fn ($c) => (string) $c, $fila)),
            $this->exportar($this->candidatoConResultado())
        ));

        foreach (['DeleteBulkAction', 'ForceDeleteBulkAction', 'RestoreBulkAction', 'authorize(', 'fn ()'] as $resto) {
            $this->assertStringNotContainsString(
                $resto,
                $csv,
                "El CSV no debe contener restos de código ({$resto})."
            );
        }
    }

    public function test_cada_fila_tiene_el_mismo_numero_de_columnas_que_la_cabecera(): void
    {
        $filas = $this->exportar($this->candidatoConResultado());

        $this->assertCount(2, $filas, 'Debe haber una cabecera y una fila de datos.');

        $this->assertCount(
            count($filas[0]),
            $filas[1],
            'La fila de datos debe tener el mismo número de columnas que la cabecera.'
        );
    }

    public function test_los_datos_del_resultado_caen_en_sus_columnas_correctas(): void
    {
        $filas = $this->exportar($this->candidatoConResultado());

        $cabecera = $filas[0];
        $datos = $filas[1];

        $porColumna = array_combine($cabecera, $datos);

        $this->assertSame('Candidato CSV', $porColumna['Nombre']);
        $this->assertSame('csv@example.test', $porColumna['Correo']);
        $this->assertSame('Si', $porColumna['Activo']);
        $this->assertSame('Si', $porColumna['Test completado']);
        $this->assertSame('80', $porColumna['Percentil']);
        $this->assertSame('Superior al Término Medio', $porColumna['Diagnostico']);
        $this->assertSame('40', $porColumna['Puntaje total']);
    }
}
