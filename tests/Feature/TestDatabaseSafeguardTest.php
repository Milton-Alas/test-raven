<?php

namespace Tests\Feature;

use RuntimeException;
use Tests\TestCase;

/**
 * La salvaguarda de la base de datos de pruebas debe funcionar.
 *
 * Motivo: `RefreshDatabase` ejecuta `migrate:fresh`, que elimina todas las tablas
 * de la conexión configurada. Si la suite corriera contra MySQL, borraría la base
 * de desarrollo o producción. La salvaguarda en Tests\TestCase convierte eso en un
 * error inmediato; aquí se verifica que realmente dispare.
 */
class TestDatabaseSafeguardTest extends TestCase
{
    public function test_la_suite_corre_contra_sqlite(): void
    {
        $this->assertSame(
            'sqlite',
            config('database.default'),
            'La suite debe ejecutarse contra SQLite.'
        );
    }

    public function test_la_salvaguarda_rechaza_una_conexion_que_no_sea_sqlite(): void
    {
        $original = config('database.default');

        try {
            config(['database.default' => 'mysql']);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageMatches('/borraría esa base completa/');

            // Se invoca la misma comprobación que corre setUp() en cada prueba.
            $this->setUp();
        } finally {
            config(['database.default' => $original]);
        }
    }

    public function test_la_conexion_configurada_por_phpunit_es_en_memoria(): void
    {
        // Refuerzo de la intención: en memoria, para no tocar ningún archivo real.
        $this->assertSame(
            ':memory:',
            config('database.connections.sqlite.database'),
            'La base de pruebas debe ser la de memoria.'
        );
    }
}
