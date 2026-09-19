<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Salvaguarda: la suite nunca debe correr contra una base que no sea SQLite.
     *
     * `RefreshDatabase` ejecuta `migrate:fresh`, que elimina TODAS las tablas de
     * la conexión configurada. Si el entorno de pruebas apuntara a MySQL (por
     * ejemplo porque un `.env` con más prioridad que `phpunit.xml` define
     * `DB_CONNECTION=mysql`), una corrida de tests borraría la base de desarrollo
     * o de producción de forma irreversible.
     *
     * La comprobación se hace en dos momentos porque son complementarios:
     *
     *  1. Antes de arrancar la aplicación, leyendo el entorno (`getenv`/`$_ENV`).
     *     Es lo único disponible en ese punto, y cubre el caso de que el entorno
     *     de pruebas esté mal configurado.
     *  2. Después de arrancar, leyendo la configuración real (`config('database')`),
     *     que es la fuente de verdad de la conexión que usará `migrate:fresh`.
     */
    protected function setUp(): void
    {
        @file_put_contents(dirname(__DIR__).'/storage/logs/dbg-setup.json', json_encode([
            'server' => $_SERVER['DB_CONNECTION'] ?? 'AUSENTE',
            'env' => $_ENV['DB_CONNECTION'] ?? 'AUSENTE',
            'getenv' => getenv('DB_CONNECTION') ?: 'AUSENTE',
            'config_default' => function_exists('config') ? (app()->bound('config') ? config('database.default') : 'sin-config') : 'sin-helper',
        ], JSON_PRETTY_PRINT));

        $this->assertTestEnvironmentUsesSqlite();

        parent::setUp();

        $this->assertConfiguredConnectionUsesSqlite();
    }

    /**
     * Comprobación previa al arranque: ¿el entorno pide SQLite?
     */
    private function assertTestEnvironmentUsesSqlite(): void
    {
        $connection = $_ENV['DB_CONNECTION'] ?? $_SERVER['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: null;

        if ($connection !== null && $connection !== 'sqlite') {
            throw new RuntimeException(
                "El entorno de pruebas define DB_CONNECTION=[{$connection}], pero las pruebas solo pueden "
                .'ejecutarse contra SQLite: `RefreshDatabase` borraría esa base completa. '
                .'Revisa phpunit.xml y que ningún archivo .env con más prioridad lo sobrescriba.'
            );
        }
    }

    /**
     * Comprobación definitiva: ¿la conexión configurada es SQLite?
     */
    private function assertConfiguredConnectionUsesSqlite(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite') {
            throw new RuntimeException(
                "Las pruebas deben ejecutarse contra SQLite, pero la conexión activa es [{$connection}] "
                ."apuntando a [{$database}]: `RefreshDatabase` borraría esa base completa. "
                .'Revisa phpunit.xml y asegúrate de que ningún .env con más prioridad defina DB_CONNECTION.'
            );
        }
    }
}
