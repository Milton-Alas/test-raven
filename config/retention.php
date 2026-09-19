<?php

/**
 * RNF-09.04 — Políticas de retención de datos.
 *
 * Cada categoría declara cuántos días conserva la información y qué se hace al
 * vencer el plazo:
 *
 *   - 'disociar': se eliminan los identificadores directos (nombre, correo,
 *     DUI/NIT y el vínculo con la persona) pero se conserva el dato desagregado.
 *     Es lo que permite seguir teniendo estadística del instrumento sin
 *     conservar datos personales (principio de minimización).
 *   - 'suprimir': se elimina el dato por completo.
 *
 * La elección por categoría no es arbitraria:
 *
 *   - Los datos psicométricos se **disocian**, no se suprimen: si se borraran,
 *     se perdería la serie histórica con la que se calibra el baremo. Al
 *     disociar, el resultado deja de ser atribuible a una persona.
 *   - Los registros de actividad y los datos técnicos se **suprimen**: no son
 *     datos de investigación y conservarlos más tiempo no aporta nada.
 *
 * Los plazos se ajustan por variables de entorno para poder cambiarlos en
 * staging/producción sin tocar el código.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Interruptor general
    |--------------------------------------------------------------------------
    |
    | Permite desactivar la aplicación automática (por ejemplo durante una
    | migración de datos o una auditoría). El comando seguirá siendo ejecutable
    | de forma manual indicando explícitamente que se ignore este interruptor.
    |
    */
    'enabled' => env('RETENCION_ACTIVA', true),

    /*
    |--------------------------------------------------------------------------
    | Categorías
    |--------------------------------------------------------------------------
    |
    | 'dias' => null significa "sin retención definida": la categoría se omite y
    | el comando lo informa, en lugar de tratar null como cero.
    |
    */
    'categorias' => [

        /*
         * Datos psicométricos: resultados, puntajes por serie, percentil,
         * diagnóstico y respuestas del test.
         */
        'psicometrico' => [
            'dias' => env('RETENCION_PSICOMETRICO_DIAS', 1825), // 5 años
            'accion' => env('RETENCION_PSICOMETRICO_ACCION', 'disociar'),
            'descripcion' => 'Resultados, puntajes, percentiles, diagnósticos y respuestas del test.',
        ],

        /*
         * Información personal del candidato: nombre, correo, edad, ocupación,
         * nivel educativo y el identificador.
         */
        'personal' => [
            'dias' => env('RETENCION_PERSONAL_DIAS', 1825), // 5 años
            'accion' => env('RETENCION_PERSONAL_ACCION', 'disociar'),
            'descripcion' => 'Datos de identificación y de contacto del candidato.',
        ],

        /*
         * Registros de actividad: auditoría de acciones (incluye los reseteos de
         * contraseña y las exportaciones).
         */
        'actividad' => [
            'dias' => env('RETENCION_ACTIVIDAD_DIAS', 730), // 2 años
            'accion' => env('RETENCION_ACTIVIDAD_ACCION', 'suprimir'),
            'descripcion' => 'Registro de auditoría de acciones del sistema.',
        ],

        /*
         * Datos técnicos: IP, user agent e información del navegador asociados a
         * las sesiones de test y a los registros de actividad.
         */
        'tecnico' => [
            'dias' => env('RETENCION_TECNICO_DIAS', 180), // 6 meses
            'accion' => env('RETENCION_TECNICO_ACCION', 'suprimir'),
            'descripcion' => 'Direcciones IP, user agent y datos del navegador.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Operaciones que NO se ven afectadas
    |--------------------------------------------------------------------------
    |
    | Se documenta a propósito para que quede claro el alcance de la política:
    |
    | - El banco de ítems (series, reactivos, opciones y tablas normativas) es
    |   inmutable y queda fuera de la retención: es el instrumento, no un dato
    |   personal, y su borrado invalidaría resultados históricos.
    | - Los usuarios administradores no se ven alcanzados por estas políticas;
    |   su ciclo de vida depende de la institución.
    | - Las copias de seguridad siguen su propio ciclo, gestionado por la
    |   institución. La retención se aplica sobre la base activa, no sobre los
    |   respaldos ya generados.
    |
    */
    'exclusiones' => [
        'banco_de_items',
        'usuarios_administradores',
        'copias_de_seguridad',
    ],
];
