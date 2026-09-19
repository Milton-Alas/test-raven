<?php

namespace App\Filament\Support;

use App\Models\TestSession;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Section;

/**
 * Reglas de interfaz para el contenido histórico del test.
 *
 * El contenido del test (series, preguntas, opciones y tablas normativas) está
 * protegido en los modelos por `PreservesHistoricalData`: no se puede borrar ni
 * reescribir. Este helper traduce esa regla a la interfaz para que el
 * administrador lo vea antes de intentarlo, en lugar de encontrarse con una
 * excepción al guardar.
 *
 * Criterio de la interfaz:
 *
 *  - **Editar**: los campos de contenido se muestran deshabilitados (se pueden
 *    consultar, pero no cambiar). Solo `is_active` queda editable, para retirar
 *    un ítem sin destruir la historia.
 *  - **Crear**: los campos están habilitados, porque cargar reactivos nuevos es
 *    una tarea legítima mientras no haya un test en curso (eso lo controla el
 *    modelo).
 */
class HistoricalContent
{
    /**
     * Deshabilita un campo únicamente en la edición.
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
            ->helperText('El contenido del test no se puede modificar: determina el puntaje y el diagnóstico de tests ya rendidos. Para retirarlo, usa «Activo».');
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
     * Aviso que se muestra al inicio de un formulario de edición.
     */
    public static function notice(string $recurso): Section
    {
        return Section::make('Registro histórico')
            ->description(
                "Este {$recurso} forma parte del instrumento con el que se calcularon resultados ya emitidos, "
                .'así que sus datos se muestran en modo consulta. Puedes activarlo o desactivarlo, pero no borrarlo '
                .'ni reescribir su contenido: eso invalidaría los puntajes y percentiles existentes.'
            )
            ->compact()
            ->columnSpanFull();
    }

    /**
     * ¿Está bloqueada la creación de contenido nuevo?
     *
     * Se bloquea mientras haya una sesión de test en curso: un candidato que está
     * respondiendo vería un instrumento distinto a mitad de la prueba.
     */
    public static function creationLocked(): bool
    {
        return TestSession::hayTestEnCurso();
    }

    /**
     * Explicación del bloqueo de creación, para el aviso de la interfaz.
     */
    public static function creationLockedNotice(): string
    {
        return 'Hay un test en curso, así que no se puede cargar contenido nuevo en este momento. '
            .'Espera a que no queden sesiones activas: si el instrumento cambiara ahora, los candidatos que están '
            .'respondiendo verían un test distinto a mitad de la prueba.';
    }
}
