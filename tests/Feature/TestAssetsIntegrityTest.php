<?php

namespace Tests\Feature;

use App\Filament\Resources\TestQuestions\Schemas\TestQuestionForm;
use App\Models\AnswerOption;
use App\Models\TestQuestion;
use App\Models\TestSeries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Las láminas del test deben existir en el repositorio.
 *
 * Motivo: la base guarda rutas como 'matrices/A/A1-0.png' y el candidato las
 * resuelve con asset('storage/...'), pero esas carpetas no estaban versionadas
 * (el .gitignore de storage/app/public solo permitía test-images/). En la
 * máquina de desarrollo funcionaba porque los archivos estaban en disco; en un
 * clon limpio del repositorio —staging, producción, la entrega a la UTI— las 60
 * láminas habrían dado 404 y el test no se podría rendir.
 *
 * Este test comprueba dos cosas:
 *  1. Que la convención de rutas sea UNA sola: la que escribe la base.
 *  2. Que los archivos existan realmente dentro de storage/app/public, que es la
 *     ruta que el candidato consume a través de public/storage.
 */
class TestAssetsIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private string $publicRoot;

    protected function setUp(): void
    {
        parent::setUp();

        // Se comprueba contra storage/app/public, no contra public/storage: el
        // enlace simbólico no existe en todos los entornos, pero los archivos
        // versionados sí tienen que estar ahí.
        $this->publicRoot = storage_path('app/public');
    }

    private function serie(string $code = 'A'): TestSeries
    {
        return TestSeries::create(['code' => $code, 'order' => 1, 'name' => "Serie {$code}", 'is_active' => true]);
    }

    private function pregunta(TestSeries $serie, int $numero = 1): TestQuestion
    {
        return TestQuestion::create([
            'test_series_id' => $serie->id,
            'question_number' => $numero,
            'global_order' => $numero,
            'matrix_image_path' => "matrices/{$serie->code}/{$serie->code}{$numero}-0.png",
            'correct_answer' => 3,
            'is_active' => true,
        ]);
    }

    public function test_las_laminas_de_las_preguntas_estan_en_el_repositorio(): void
    {
        $pregunta = $this->pregunta($this->serie());

        $this->assertFileExists(
            $this->publicRoot.'/'.$pregunta->matrix_image_path,
            "La lámina '{$pregunta->matrix_image_path}' que guarda la base no está en storage/app/public. "
            .'En un clon limpio el candidato vería un 404 al rendir el test.'
        );
    }

    public function test_las_imagenes_de_las_opciones_estan_en_el_repositorio(): void
    {
        $serie = $this->serie();
        $pregunta = $this->pregunta($serie);

        $opcion = AnswerOption::create([
            'test_question_id' => $pregunta->id,
            'option_number' => 1,
            'option_image_path' => "options/{$serie->code}/{$serie->code}1/{$serie->code}1-1.png",
        ]);

        $this->assertFileExists(
            $this->publicRoot.'/'.$opcion->option_image_path,
            "La imagen '{$opcion->option_image_path}' que guarda la base no está en storage/app/public."
        );
    }

    /**
     * El panel resolvía la miniatura anteponiendo "test-images/", un prefijo que
     * la base nunca escribe. Eso obligaba a mantener dos copias de cada lámina.
     */
    public function test_el_panel_no_antepone_un_prefijo_que_la_base_no_escribe(): void
    {
        $contenido = file_get_contents(app_path('Filament/Resources/TestQuestions/Tables/TestQuestionsTable.php'));

        // Se busca la interpolación real ("test-images/{$...}"), no la mención en
        // un comentario explicativo.
        $this->assertStringNotContainsString(
            '"test-images/{',
            $contenido,
            'El panel no debe anteponer el prefijo "test-images/": la ruta guardada en la base ya es la definitiva.'
        );
    }

    /**
     * Al crear un reactivo desde el panel, la imagen debe subirse a la misma
     * carpeta que después espera el candidato.
     */
    public function test_las_subidas_del_panel_usan_la_convencion_de_la_base(): void
    {
        $serie = $this->serie('B');

        $metodo = new ReflectionMethod(TestQuestionForm::class, 'optionDirectory');
        $metodo->setAccessible(true);

        $this->assertSame(
            'options/B/B3',
            $metodo->invoke(null, $serie->id, 3),
            'Las opciones deben subirse a options/{serie}/{serie}{pregunta}/, igual que las guarda la base.'
        );

        // Sin serie seleccionada todavía no se puede saber el código: se usa X.
        $this->assertSame('options/X/X0', $metodo->invoke(null, null, null));
    }
}
