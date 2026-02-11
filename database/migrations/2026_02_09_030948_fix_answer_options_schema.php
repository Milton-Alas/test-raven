<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('answer_options', function (Blueprint $table) {
            if (!Schema::hasColumn('answer_options', 'test_question_id')) {
                $table->foreignId('test_question_id')
                    ->nullable()
                    ->constrained('test_questions')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('answer_options', 'option_number')) {
                $table->integer('option_number')->nullable();
            }

            if (!Schema::hasColumn('answer_options', 'option_image_path')) {
                $table->string('option_image_path')->nullable();
            }
        });

        Schema::table('answer_options', function (Blueprint $table) {
            if (Schema::hasColumn('answer_options', 'test_question_id')) {
                if (!$this->indexExists('answer_options', 'answer_options_test_question_id_index')) {
                    $table->index('test_question_id');
                }
                if (!$this->indexExists('answer_options', 'answer_options_test_question_id_option_number_unique')) {
                    $table->unique(['test_question_id', 'option_number']);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answer_options', function (Blueprint $table) {
            if (Schema::hasColumn('answer_options', 'test_question_id')) {
                $table->dropForeign(['test_question_id']);
                $table->dropIndex(['test_question_id']);
            }
            if (Schema::hasColumn('answer_options', 'option_number')) {
                $table->dropUnique(['test_question_id', 'option_number']);
                $table->dropColumn('option_number');
            }
            if (Schema::hasColumn('answer_options', 'option_image_path')) {
                $table->dropColumn('option_image_path');
            }
            if (Schema::hasColumn('answer_options', 'test_question_id')) {
                $table->dropColumn('test_question_id');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'sqlite') {
            $result = DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?", [$table, $indexName]);
            return !empty($result);
        } else if ($connection === 'mysql') {
            $result = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($result);
        }

        // Fallback for other database drivers (may not be accurate)
        try {
            $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();
            $indexes = $schemaManager->listTableIndexes(DB::getTablePrefix() . $table);
            return isset($indexes[strtolower($indexName)]);
        } catch (\Exception $e) {
            return false; // Or handle the exception as needed
        }
    }
};
