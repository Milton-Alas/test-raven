<?php

/**
 * Bootstrap de la suite de pruebas.
 *
 * Hace una sola cosa, pero crítica: aislar la suite de la base de datos de
 * desarrollo.
 *
 * Incidente que lo motiva: `RefreshDatabase` ejecuta `migrate:fresh`, que elimina
 * todas las tablas de la conexión activa. Esta máquina tiene `DB_CONNECTION=mysql`
 * exportado en el entorno del shell, y eso hizo que `composer test` vaciara la
 * base de desarrollo completa (60 preguntas, candidatos, sesiones y resultados).
 *
 * Por qué no basta con `phpunit.xml` (verificado, no supuesto):
 *
 *  1. El atributo `force="true"` de `<env>` escribe en `$_ENV` y con `putenv()`,
 *     pero NO en `$_SERVER`, y Dotenv lee `$_SERVER` antes que `$_ENV`.
 *  2. PHPUnit aplica su configuración `<env>` DESPUÉS de este bootstrap y además
 *     restaura los valores originales del proceso al preparar cada prueba. En
 *     concreto reponía `APP_ENV=local`, así que Laravel cargaba `.env` (MySQL) en
 *     lugar de `.env.testing` (SQLite).
 *
 * Por eso aquí los valores se fijan de forma incondicional y en los tres orígenes
 * que consulta Laravel. `APP_ENV=testing` es el que hace que Laravel lea
 * `.env.testing` y el que impide que se cargue el `.env` de desarrollo.
 */

// PHPUnit usa este archivo EN LUGAR de vendor/autoload.php: hay que cargarlo.
require __DIR__.'/../vendor/autoload.php';

$forzadas = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    // Drivers en memoria: el .env de desarrollo exporta SESSION_DRIVER=database
    // y, al ser inmutable Dotenv, ganaba sobre .env.testing. Con SQLite en memoria
    // no hay tabla `sessions`, así que cualquier prueba que usara sesión fallaba
    // con "no such table: sessions".
    'SESSION_DRIVER' => 'array',
    'CACHE_STORE' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array',
];

foreach ($forzadas as $clave => $valor) {
    putenv("{$clave}={$valor}");
    $_ENV[$clave] = $valor;
    $_SERVER[$clave] = $valor;
}

// Credenciales de la base de datos de desarrollo: se retiran de los tres orígenes
// para que no puedan filtrarse a la configuración de pruebas.
foreach (['DB_HOST', 'DB_PORT', 'DB_USERNAME', 'DB_PASSWORD'] as $variable) {
    putenv($variable);
    unset($_ENV[$variable], $_SERVER[$variable]);
}

// Salvaguarda de último recurso: si la aplicación acabara apuntando a otra
// conexión, se avisa en la salida de la suite.
register_shutdown_function(static function (): void {
    try {
        $connection = config('database.default');
    } catch (Throwable) {
        return;
    }

    if ($connection !== null && $connection !== 'sqlite') {
        fwrite(
            STDERR,
            PHP_EOL.'[BLOQUEADO] La suite terminó con la conexión ['.$connection.'] activa. '
            .'Las pruebas solo pueden ejecutarse contra SQLite: `RefreshDatabase` habría borrado esa base.'.PHP_EOL
        );
    }
});
