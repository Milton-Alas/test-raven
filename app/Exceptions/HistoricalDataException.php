<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Se lanza cuando se intenta destruir o alterar contenido de un registro que
 * forma parte de la evidencia histórica del test (series, preguntas, opciones y
 * tablas normativas).
 *
 * Motivo: los puntajes y percentiles ya calculados dependen de ese contenido, y
 * las respuestas de los candidatos tienen borrado en cascada desde
 * `test_questions`. Borrar o reescribir contenido invalidaría resultados ya
 * emitidos, así que el sistema lo impide y ofrece la desactivación.
 */
class HistoricalDataException extends RuntimeException
{
    public static function deletionBlocked(string $modelo, string $sugerencia = ''): self
    {
        $mensaje = "No se puede eliminar este registro de {$modelo}: forma parte de la evidencia histórica del test "
            .'y su borrado invalidaría resultados ya calculados (las respuestas de los candidatos se eliminan en cascada).';

        if ($sugerencia !== '') {
            $mensaje .= ' '.$sugerencia;
        }

        return new self($mensaje);
    }

    /**
     * @param  array<int, string>  $atributos
     */
    public static function updateBlocked(string $modelo, array $atributos, string $sugerencia = ''): self
    {
        $lista = implode(', ', $atributos);

        $mensaje = "No se puede modificar {$lista} en este registro de {$modelo}: ese contenido determina el puntaje, "
            .'el percentil o el diagnóstico de tests ya rendidos.';

        if ($sugerencia !== '') {
            $mensaje .= ' '.$sugerencia;
        }

        return new self($mensaje);
    }

    public static function creationBlocked(string $modelo): self
    {
        return new self(
            "No se puede crear un registro de {$modelo} mientras hay un test en curso: "
            .'los candidatos que están respondiendo verían un instrumento distinto a mitad de la prueba. '
            .'Espera a que no haya sesiones activas.'
        );
    }
}
