<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Section;

/**
 * Reglas de interfaz para el contenido histórico del test.
 *
 * El contenido del test (series, reactivos, opciones y tablas normativas) es
 * instrumento normalizado: está protegido en los modelos por
 * `PreservesHistoricalData` y el panel no lo crea, edita ni borra para ningún
 * rol, `admin` incluido (ver `canCreate()`/`canEdit()` de cada Resource). Este
 * helper traduce esa regla a la interfaz para que se entienda al consultarlo, en
 * lugar de encontrarse con una excepción al guardar.
 *
 * Criterio de la interfaz:
 *
 *  - **Consultar**: los campos de contenido se muestran deshabilitados junto al
 *    aviso `notice()`, que explica por qué y por dónde sí se cambian los datos
 *    (seeder o migración, con control de versiones).
 */
class HistoricalContent
{
    /**
     * Deshabilita un campo en la edición.
     *
     * @template T of Field
     *
     * @param  T  $field
     * @return T
     */
    public static function lockedOnEdit(Field $field): Field
    {
        return $field
            ->disabledOn('edit')
            ->helperText('El contenido del test no se puede modificar: determina el puntaje y el diagnóstico de tests ya rendidos.');
    }

    /**
     * Igual que lockedOnEdit() pero sin el texto de ayuda, para campos donde el
     * mensaje ya aparece en el encabezado de la sección (evita repetirlo 8 veces
     * en el mismo formulario).
     *
     * @template T of Field
     *
     * @param  T  $field
     * @return T
     */
    public static function lockedOnEditQuiet(Field $field): Field
    {
        return $field->disabledOn('edit');
    }

    /**
     * Aviso que se muestra al inicio del formulario del instrumento.
     */
    public static function notice(string $recurso): Section
    {
        return Section::make('Registro histórico')
            ->description(
                "Este {$recurso} forma parte del instrumento normalizado con el que se calcularon resultados ya "
                .'emitidos, así que se muestra en modo consulta. No se puede crear, modificar ni borrar desde el '
                .'panel: cualquier cambio real debe entrar por seeder o migración, con control de versiones, para '
                .'que quede rastro de quién lo hizo y por qué.'
            )
            ->compact()
            ->columnSpanFull();
    }
}
