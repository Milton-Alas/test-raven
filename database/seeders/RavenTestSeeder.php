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
        $basePath = public_path('storage/test-images');
        $matricesPath = $basePath . '/matrices';
        $optionsPath = $basePath . '/options';

        if (!File::isDirectory($matricesPath) || !File::isDirectory($optionsPath)) {
            $this->command?->warn('No se encontró test-images/matrices u options. Seeder cancelado.');
            return;
        }

        $seriesOrder = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5];
        $globalOrder = 1;

        foreach ($seriesOrder as $seriesCode => $order) {
            $series = TestSeries::firstOrCreate(
                ['code' => $seriesCode],
                ['order' => $order, 'name' => "Serie {$seriesCode}", 'description' => "Serie {$seriesCode}", 'is_active' => true]
            );

            $seriesMatrixDir = $matricesPath . '/' . $seriesCode;
            if (!File::isDirectory($seriesMatrixDir)) {
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
                        'correct_answer' => 1,
                        'is_active' => true,
                    ]
                );

                if (!$question->wasRecentlyCreated) {
                    $question->update([
                        'global_order' => $question->global_order ?: $globalOrder,
                        'matrix_image_path' => $question->matrix_image_path ?: $publicMatrixPath,
                    ]);
                }

                $this->seedOptionsForQuestion($seriesCode, $questionNumber, $question->id, $optionsPath);

                $globalOrder++;
            }
        }
    }

    private function seedOptionsForQuestion(string $seriesCode, int $questionNumber, int $questionId, string $optionsPath): void
    {
        $seriesOptionsDir = $optionsPath . '/' . $seriesCode;
        if (!File::isDirectory($seriesOptionsDir)) {
            return;
        }

        $pattern = sprintf('%s/%s%d-*.png', $seriesOptionsDir, $seriesCode, $questionNumber);
        $files = glob($pattern);
        if (!$files) {
            return;
        }

        sort($files, SORT_NATURAL);
        $optionNumber = 1;
        $maxOptions = 6;

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
        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }
        $disk->put($to, File::get($from));
    }
}
