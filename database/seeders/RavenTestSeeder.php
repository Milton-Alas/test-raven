<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\TestQuestion;
use App\Models\TestSeries;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RavenTestSeeder extends Seeder
{
    public function run(): void
    {
        $rightAnswers = [
            'A1' => 4, 'A2' => 5, 'A3' => 1, 'A4' => 2, 'A5' => 6, 'A6' => 3, 'A7' => 6, 'A8' => 2, 'A9' => 1, 'A10' => 3, 'A11' => 5, 'A12' => 4,
            'B1' => 2, 'B2' => 6, 'B3' => 1, 'B4' => 2, 'B5' => 1, 'B6' => 3, 'B7' => 5, 'B8' => 6, 'B9' => 4, 'B10' => 3, 'B11' => 4, 'B12' => 5,
            'C1' => 8, 'C2' => 2, 'C3' => 3, 'C4' => 8, 'C5' => 7, 'C6' => 4, 'C7' => 5, 'C8' => 1, 'C9' => 7, 'C10' => 6, 'C11' => 1, 'C12' => 2,
            'D1' => 3, 'D2' => 4, 'D3' => 3, 'D4' => 7, 'D5' => 8, 'D6' => 6, 'D7' => 5, 'D8' => 4, 'D9' => 1, 'D10' => 2, 'D11' => 5, 'D12' => 6,
            'E1' => 7, 'E2' => 6, 'E3' => 8, 'E4' => 2, 'E5' => 1, 'E6' => 5, 'E7' => 2, 'E8' => 4, 'E9' => 1, 'E10' => 6, 'E11' => 3, 'E12' => 5,
        ];

        // El material de origen vive en storage/app/public/test-images, que es la
        // ruta versionada en el repositorio. Antes se leía a través de
        // public/storage (el enlace simbólico de storage:link), así que el seeder
        // fallaba en cualquier entorno donde el enlace todavía no existiera.
        $basePath = storage_path('app/public/test-images');
        $matricesPath = $basePath.'/matrices';
        $optionsPath = $basePath.'/options';

        if (! File::isDirectory($matricesPath) || ! File::isDirectory($optionsPath)) {
            $this->command?->warn("No se encontró {$basePath}/matrices u options. Seeder cancelado.");

            return;
        }

        $seriesOrder = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
        $globalOrder = 1;

        foreach ($seriesOrder as $seriesCode => $order) {
            $series = TestSeries::firstOrCreate(
                ['code' => $seriesCode],
                ['order' => $order, 'name' => "Serie {$seriesCode}", 'description' => "Serie {$seriesCode}", 'is_active' => true]
            );

            $seriesMatrixDir = $matricesPath.'/'.$seriesCode;
            if (! File::isDirectory($seriesMatrixDir)) {
                continue;
            }

            $matrixFiles = File::files($seriesMatrixDir);
            usort($matrixFiles, function ($a, $b) use ($seriesCode) {
                $aNum = (int) str_replace($seriesCode, '', pathinfo($a->getFilename(), PATHINFO_FILENAME));
                $bNum = (int) str_replace($seriesCode, '', pathinfo($b->getFilename(), PATHINFO_FILENAME));

                return $aNum <=> $bNum;
            });

            foreach ($matrixFiles as $matrixFile) {
                $filename = $matrixFile->getFilename(); // A1.png
                $questionNumber = (int) str_replace($seriesCode, '', pathinfo($filename, PATHINFO_FILENAME));
                if ($questionNumber <= 0) {
                    continue;
                }

                $answerKey = $seriesCode.$questionNumber;
                $correctAnswer = $rightAnswers[$answerKey] ?? 1;

                $publicMatrixPath = "matrices/{$seriesCode}/{$filename}";
                $this->copyToPublic($matrixFile->getPathname(), $publicMatrixPath);

                $question = TestQuestion::firstOrCreate(
                    [
                        'test_series_id' => $series->id,
                        'question_number' => $questionNumber,
                    ],
                    [
                        'global_order' => $globalOrder,
                        'matrix_image_path' => $publicMatrixPath,
                        'correct_answer' => $correctAnswer,
                        'is_active' => true,
                    ]
                );

                if (! $question->wasRecentlyCreated) {
                    $question->update([
                        'global_order' => $question->global_order ?: $globalOrder,
                        'matrix_image_path' => $question->matrix_image_path ?: $publicMatrixPath,
                        'correct_answer' => $correctAnswer,
                    ]);
                }

                $this->seedOptionsForQuestion($seriesCode, $questionNumber, $question->id, $optionsPath);

                $globalOrder++;
            }
        }
    }

    private function seedOptionsForQuestion(string $seriesCode, int $questionNumber, int $questionId, string $optionsPath): void
    {
        $seriesOptionsDir = $optionsPath.'/'.$seriesCode;
        if (! File::isDirectory($seriesOptionsDir)) {
            return;
        }

        $pattern = sprintf('%s/%s%d-*.png', $seriesOptionsDir, $seriesCode, $questionNumber);
        $files = glob($pattern);
        if (! $files) {
            return;
        }

        sort($files, SORT_NATURAL);
        $optionNumber = 1;
        $maxOptions = in_array($seriesCode, ['C', 'D', 'E']) ? 8 : 6;

        foreach ($files as $filePath) {
            if ($optionNumber > $maxOptions) {
                break;
            }
            $filename = basename($filePath); // A1-0.png
            $publicOptionPath = "options/{$seriesCode}/{$seriesCode}{$questionNumber}/{$filename}";
            $this->copyToPublic($filePath, $publicOptionPath);

            AnswerOption::firstOrCreate(
                [
                    'test_question_id' => $questionId,
                    'option_number' => $optionNumber,
                ],
                [
                    'option_image_path' => $publicOptionPath,
                ]
            );

            $optionNumber++;
        }
    }

    private function copyToPublic(string $from, string $to): void
    {
        $disk = Storage::disk('public');
        $directory = dirname($to);
        if (! $disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }
        $disk->put($to, File::get($from));
    }
}
