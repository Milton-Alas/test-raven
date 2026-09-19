<?php

use App\Support\DuiNitCipher;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RNF-09.01 — Cifrado en reposo del identificador (DUI/NIT).
 *
 * Antes: `dui_nit` se guardaba en claro con un índice `unique`.
 * Después: `dui_nit` guarda el texto cifrado (AES-256-CBC) y `dui_nit_hash`
 * guarda un HMAC-SHA256 determinista con índice único, que es lo que se usa para
 * buscar y para validar unicidad sin descifrar.
 *
 * La migración también convierte los registros existentes: si quedaran en claro,
 * el modelo intentaría descifrarlos y fallaría, y nadie podría iniciar sesión
 * con su DUI.
 */
return new class extends Migration
{
    /**
     * Índice único anterior sobre la columna en claro.
     */
    private const INDICE_UNICO_ANTERIOR = 'candidates_dui_nit_unique';

    private const INDICE_UNICO_NUEVO = 'candidates_dui_nit_hash_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('candidates', 'dui_nit_hash')) {
            Schema::table('candidates', function (Blueprint $table) {
                // 64 caracteres del HMAC-SHA256 en hexadecimal.
                $table->char('dui_nit_hash', 64)->nullable()->after('dui_nit');
            });
        }

        // El índice único anterior debe irse ANTES de cambiar el tipo de columna:
        // MySQL no permite convertir a TEXT una columna que participa en un índice
        // sin longitud de clave.
        $this->dropUniqueIfExists('candidates', self::INDICE_UNICO_ANTERIOR);

        $this->ampliarColumnaIdentificador();

        $this->cifrarRegistrosExistentes();

        // El índice único se crea al final, cuando todos los registros ya tienen
        // su hash. Si hubiera duplicados, es mejor que falle aquí que dejar la
        // base en un estado a medias.
        $this->addUniqueIfMissing('candidates', 'dui_nit_hash', self::INDICE_UNICO_NUEVO);
    }

    public function down(): void
    {
        $this->dropUniqueIfExists('candidates', self::INDICE_UNICO_NUEVO);

        // Se devuelve el identificador a texto plano para poder restaurar el
        // índice único original.
        $this->descifrarRegistrosExistentes();

        $this->reducirColumnaIdentificador();

        $this->addUniqueIfMissing('candidates', 'dui_nit', self::INDICE_UNICO_ANTERIOR);

        if (Schema::hasColumn('candidates', 'dui_nit_hash')) {
            Schema::table('candidates', function (Blueprint $table) {
                $table->dropColumn('dui_nit_hash');
            });
        }
    }

    /**
     * Amplía la columna del identificador para que quepa el texto cifrado y
     * permita vaciarla al disociar.
     *
     * El cifrado de un DUI ronda los 200 caracteres y crece con el valor, así que
     * varchar(255) queda demasiado justo. Además, la política de retención
     * (RNF-09.04) necesita poder eliminar el identificador al disociar, así que la
     * columna deja de ser obligatoria.
     *
     * Se usa SQL específico de MySQL porque es el motor de la aplicación; SQLite
     * no necesita el cambio (su tipado es dinámico y ya admite nulos) y no soporta
     * la instrucción.
     */
    private function ampliarColumnaIdentificador(): void
    {
        // El texto cifrado de un DUI ronda los 200 caracteres y crece con el
        // valor, así que varchar(255) se queda corto. Además, la política de
        // retención (RNF-09.04) necesita poder eliminar el identificador al
        // disociar, así que la columna deja de ser obligatoria.
        //
        // Se usa ->change() para que Laravel genere la instrucción que
        // corresponda a cada motor (MySQL modifica la columna; SQLite reconstruye
        // la tabla), en lugar de SQL específico de MySQL.
        Schema::table('candidates', function (Blueprint $table) {
            $table->text('dui_nit')->nullable()->change();
        });
    }

    /**
     * Cifra cada identificador en claro y calcula su índice.
     *
     * Se usa el query builder (no Eloquent) a propósito: así se lee el valor tal
     * como está en la base, sin que ningún cast intente descifrarlo.
     */
    private function cifrarRegistrosExistentes(): void
    {
        $filas = DB::table('candidates')
            ->select('id', 'dui_nit')
            ->whereNotNull('dui_nit')
            ->get();

        $hashesVistos = [];

        foreach ($filas as $fila) {
            $enClaro = $fila->dui_nit;

            // Si ya estuviera cifrado (migración reejecutada), se descifra para
            // recalcular el índice sin volver a cifrarlo.
            $descifrado = DuiNitCipher::decrypt($enClaro);
            $valorPlano = $descifrado ?? $enClaro;

            $hash = DuiNitCipher::hash($valorPlano);

            if ($hash === null) {
                continue;
            }

            if (isset($hashesVistos[$hash])) {
                throw new RuntimeException(
                    "Identificador duplicado en candidates: los registros {$hashesVistos[$hash]} y {$fila->id} "
                    .'comparten el mismo DUI/NIT. Corrige el duplicado antes de aplicar el cifrado.'
                );
            }

            $hashesVistos[$hash] = $fila->id;

            DB::table('candidates')
                ->where('id', $fila->id)
                ->update([
                    'dui_nit' => DuiNitCipher::encrypt($valorPlano),
                    'dui_nit_hash' => $hash,
                ]);
        }
    }

    /**
     * Revierte al texto plano para poder restaurar el índice único original.
     */
    private function descifrarRegistrosExistentes(): void
    {
        $filas = DB::table('candidates')
            ->select('id', 'dui_nit')
            ->whereNotNull('dui_nit')
            ->get();

        foreach ($filas as $fila) {
            $valorPlano = DuiNitCipher::decrypt($fila->dui_nit) ?? $fila->dui_nit;

            DB::table('candidates')
                ->where('id', $fila->id)
                ->update(['dui_nit' => $valorPlano]);
        }
    }

    /**
     * Revierte el tipo de la columna al original (solo MySQL, ver arriba).
     */
    private function reducirColumnaIdentificador(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('dui_nit', 255)->nullable()->change();
        });
    }

    private function dropUniqueIfExists(string $tabla, string $indice): void
    {
        if (! $this->indexExists($tabla, $indice)) {
            return;
        }

        Schema::table($tabla, function (Blueprint $table) use ($indice) {
            $table->dropUnique($indice);
        });
    }

    private function addUniqueIfMissing(string $tabla, string $columna, string $indice): void
    {
        if ($this->indexExists($tabla, $indice)) {
            return;
        }

        Schema::table($tabla, function (Blueprint $table) use ($columna, $indice) {
            $table->unique($columna, $indice);
        });
    }

    private function indexExists(string $tabla, string $indice): bool
    {
        $conexion = Schema::getConnection();

        if ($conexion->getDriverName() === 'sqlite') {
            $resultado = $conexion->select(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$indice]
            );

            return $resultado !== [];
        }

        return collect(Schema::getIndexes($tabla))
            ->contains(fn (array $i): bool => $i['name'] === $indice);
    }
};
