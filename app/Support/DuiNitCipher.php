<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Cifrado e indexado del identificador sensible (DUI/NIT).
 *
 * RNF-09.01 — Cifrado en reposo:
 * el identificador se guarda cifrado con AES-256-CBC (el cifrado que la
 * aplicación tiene configurado en config/app.php) y la búsqueda y validación de
 * unicidad se hacen contra un índice seguro, sin descifrar el valor.
 *
 * Por qué se separan dos representaciones:
 *
 *  - El **cifrado** (AES-256-CBC) usa un IV aleatorio, así que el mismo DUI
 *    produce un texto cifrado distinto cada vez. Eso es correcto para
 *    confidencialidad, pero hace imposible buscar por igualdad.
 *  - El **índice** (`dui_nit_hash`) es un HMAC-SHA256 determinista: el mismo
 *    DUI produce siempre el mismo valor, de modo que se puede buscar, validar
 *    unicidad y agrupar sin descifrar nada.
 *
 * Por qué HMAC y no un hash simple: el DUI salvadoreño tiene del orden de 10^8
 * combinaciones válidas. Un SHA-256 sin clave se revierte con una tabla
 * precalculada en minutos, y eso anularía el cifrado. Con HMAC la clave es el
 * `APP_KEY`, así que el índice no es atacable sin ella.
 */
class DuiNitCipher
{
    /**
     * Algoritmo del índice. SHA-256 en hexadecimal = 64 caracteres.
     */
    private const HASH_ALGORITHM = 'sha256';

    /**
     * Cifra un identificador para almacenarlo con AES-256-CBC.
     */
    public static function encrypt(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        return Crypt::encryptString($valor);
    }

    /**
     * Descifra un identificador almacenado.
     *
     * Si el valor no se puede descifrar devuelve null en lugar de lanzar una
     * excepción: eso ocurre con datos heredados que aún estén en claro, y no debe
     * romper una pantalla del panel. La migración de datos los convierte.
     */
    public static function decrypt(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Crypt::decryptString($valor);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Índice determinista de búsqueda y unicidad (HMAC-SHA256).
     *
     * El valor se normaliza antes de calcularlo (sin guiones ni espacios, en
     * mayúsculas) para que "05123456-7" y "051234567" no creen dos identidades
     * distintas de la misma persona.
     */
    public static function hash(?string $valor): ?string
    {
        $normalizado = self::normalize($valor);

        if ($normalizado === null) {
            return null;
        }

        return hash_hmac(
            self::HASH_ALGORITHM,
            $normalizado,
            (string) config('app.key'),
        );
    }

    /**
     * Normaliza el identificador para comparaciones y para el índice.
     */
    public static function normalize(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $limpio = preg_replace('/[^A-Za-z0-9]/', '', $valor);

        if ($limpio === null || $limpio === '') {
            return null;
        }

        return strtoupper($limpio);
    }

    /**
     * Tipo de documento que corresponde al valor, según su formato.
     *
     * Tras la homologación (Decreto Legislativo 203 de 2021) el DUI sustituye al
     * NIT para las personas naturales salvadoreñas mayores de edad, de modo que
     * cada persona tiene un solo número de identificación vigente. Por eso el tipo
     * no se almacena en una columna aparte: se deduce de la longitud del valor
     * normalizado, y así un único campo sigue garantizando que nadie se registre
     * dos veces (una con DUI y otra con NIT).
     *
     *   - 9 dígitos  → 'dui'  (formato 00000000-0)
     *   - 14 dígitos → 'nit'  (formato 0000-000000-000-0)
     *
     * Devuelve null si el valor no corresponde a ninguno de los dos formatos.
     * La comprobación exige que el valor normalizado sean **solo dígitos**: sin
     * eso, una cadena de nueve letras pasaría como DUI, porque la normalización
     * conserva letras (y debe hacerlo, para los NIT de personas jurídicas, que
     * pueden incorporarlas).
     */
    public static function documentType(?string $valor): ?string
    {
        $normalizado = self::normalize($valor);

        if ($normalizado === null || ! ctype_digit($normalizado)) {
            return null;
        }

        return match (strlen($normalizado)) {
            9 => 'dui',
            14 => 'nit',
            default => null,
        };
    }

    /**
     * Etiqueta legible del tipo de documento, para mensajes e informes.
     */
    public static function documentTypeLabel(?string $valor): ?string
    {
        return match (self::documentType($valor)) {
            'dui' => 'DUI',
            'nit' => 'NIT',
            default => null,
        };
    }

    /**
     * Enmascara el identificador para mostrarlo en listados.
     *
     * El panel necesita poder identificar al candidato sin exponer el DUI
     * completo en pantalla, en un listado o en una captura de pantalla.
     */
    public static function mask(?string $valor): ?string
    {
        $normalizado = self::normalize($valor);

        if ($normalizado === null) {
            return null;
        }

        $longitud = strlen($normalizado);

        if ($longitud <= 4) {
            return str_repeat('•', $longitud);
        }

        return str_repeat('•', $longitud - 4).substr($normalizado, -4);
    }
}
