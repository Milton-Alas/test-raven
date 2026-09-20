<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Respalda la clave de cifrado de la aplicación.
 *
 * Por qué existe como comando y no como "copiar el .env":
 *
 *  - El `APP_KEY` cifra el DUI/NIT de los candidatos (RNF-09.01). Si se pierde,
 *    los identificadores almacenados son irrecuperables y los índices de búsqueda
 *    dejan de coincidir: nadie podría iniciar sesión por DUI.
 *  - Un respaldo de la base de datos SIN la clave es un respaldo inservible.
 *    Este comando permite que el respaldo de la base y el de la clave se generen
 *    juntos y con permisos restringidos.
 *  - El `.env` contiene además credenciales de base de datos y correo. Extraer
 *    solo la clave evita que el respaldo de la clave sea también un respaldo de
 *    secretos que no hacen falta para recuperar los datos cifrados.
 */
class BackupEncryptionKeyCommand extends Command
{
    protected $signature = 'app:backup-encryption-key
                            {--path= : Carpeta destino (por defecto storage/backups)}
                            {--force : Sobrescribe el archivo si ya existe una copia del mismo día}';

    protected $description = 'Guarda la APP_KEY (y las claves previas) en un archivo con permisos restringidos';

    public function handle(): int
    {
        $key = (string) config('app.key');
        $clavesPrevias = config('app.previous_keys', []);

        if ($key === '') {
            $this->error('No hay APP_KEY configurada. Sin ella no hay nada que respaldar.');

            return self::FAILURE;
        }

        $directorio = $this->option('path') ?: storage_path('backups');

        if (! File::isDirectory($directorio)) {
            File::makeDirectory($directorio, 0700, true);
        }

        $archivo = $directorio.'/app-key-'.now()->format('Y-m-d').'.txt';

        if (File::exists($archivo) && ! $this->option('force')) {
            $this->warn("Ya existe un respaldo de la clave para hoy: {$archivo}");
            $this->line('Usa --force para sobrescribirlo.');

            return self::SUCCESS;
        }

        $contenido = $this->componerContenido($key, $clavesPrevias);

        File::put($archivo, $contenido);

        // El archivo contiene el secreto que hace legible toda la base: solo el
        // propietario debe poder leerlo.
        @chmod($archivo, 0600);

        $this->info("Clave respaldada en: {$archivo}");
        $this->line('Permisos: '.decoct(fileperms($archivo) & 0777));

        if ($clavesPrevias !== []) {
            $this->line('Incluye '.count($clavesPrevias).' clave(s) previa(s) para rotación.');
        }

        $this->newLine();
        $this->warn('Este archivo permite descifrar los DUI/NIT. Guárdalo separado del respaldo de la base');
        $this->warn('y con acceso restringido: juntos son equivalentes a los datos en claro.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $clavesPrevias
     */
    private function componerContenido(string $key, array $clavesPrevias): string
    {
        $lineas = [
            '# Respaldo de la clave de cifrado — Test de Raven (UES)',
            '#',
            '# Generado: '.now()->toDateTimeString(),
            '# Entorno:  '.app()->environment(),
            '#',
            '# RESTAURACIÓN: copia el valor de APP_KEY en el .env del entorno destino.',
            '# Sin esta clave, los dui_nit cifrados son irrecuperables y los índices',
            '# dui_nit_hash dejan de coincidir (nadie podría entrar por DUI).',
            '#',
            '# ROTACIÓN: si agregas una clave nueva, mueve la anterior a APP_PREVIOUS_KEYS',
            '# (separadas por coma) para poder seguir leyendo los datos ya cifrados.',
            '#',
            '',
            'APP_KEY='.$key,
        ];

        if ($clavesPrevias !== []) {
            $lineas[] = '';
            $lineas[] = '# Claves previas (solo lectura de datos antiguos), en APP_PREVIOUS_KEYS:';
            $lineas[] = 'APP_PREVIOUS_KEYS='.implode(',', $clavesPrevias);
        }

        return implode(PHP_EOL, $lineas).PHP_EOL;
    }
}
