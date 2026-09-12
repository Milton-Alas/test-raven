<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Reseteo manual de la contraseña de un candidato.
 *
 * Contexto del caso: los candidatos no tienen recuperación de contraseña por
 * correo y solo disponen de una oportunidad para rendir el test. Si un
 * candidato olvida su clave antes de rendir, no tiene ninguna vía de
 * autoservicio y queda bloqueado. Este servicio cubre ese caso: un
 * administrador genera una contraseña temporal y el candidato la usa para
 * entrar.
 *
 * Toda operación queda registrada en `activity_logs` con el administrador como
 * `causer` y el candidato como `subject`.
 */
class CandidatePasswordResetService
{
    /**
     * Longitud de la contraseña temporal generada.
     */
    public const PASSWORD_LENGTH = 12;

    /**
     * Caracteres ambiguos que se excluyen para que la contraseña sea fácil de
     * transcribir cuando el administrador la comunique de forma verbal.
     */
    private const AMBIGUOUS_CHARACTERS = '0O1lI5S8B2Z';

    public const EVENT = 'candidate_password_reset';

    public function __construct(private readonly ActivityLogService $activityLog) {}

    /**
     * Genera una contraseña temporal, la asigna al candidato y registra la
     * auditoría.
     *
     * @param  User  $admin  Administrador que ejecuta el reseteo.
     * @param  Candidate  $candidate  Candidato afectado.
     * @param  string|null  $plainPassword  Contraseña elegida por el administrador.
     *                                      Si es null se genera una aleatoria.
     * @return string La contraseña en claro, disponible una
     *                única vez para mostrarla en pantalla.
     */
    public function reset(User $admin, Candidate $candidate, ?string $plainPassword = null): string
    {
        $wasGenerated = $plainPassword === null;
        $plainPassword ??= $this->generateTemporaryPassword();

        // El modelo Candidate castea 'password' => 'hashed', por lo que Laravel
        // aplica el hash al asignar el valor en claro.
        $candidate->forceFill(['password' => $plainPassword])->save();

        $log = $this->log($admin, $candidate, $wasGenerated);

        if ($log) {
            // Permite que la interfaz enlace el reseteo con su registro de auditoría.
            $candidate->setAttribute('last_password_reset_log_id', $log->id);
        }

        return $plainPassword;
    }

    /**
     * Contraseña temporal legible: sin caracteres ambiguos y sin espacios.
     */
    public function generateTemporaryPassword(int $length = self::PASSWORD_LENGTH): string
    {
        do {
            $password = Str::password(
                length: $length,
                letters: true,
                numbers: true,
                symbols: true,
                spaces: false,
            );
        } while ($this->hasAmbiguousCharacters($password));

        return $password;
    }

    private function hasAmbiguousCharacters(string $password): bool
    {
        foreach (str_split(self::AMBIGUOUS_CHARACTERS) as $character) {
            if (str_contains($password, $character)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Registra el evento en `activity_logs`.
     *
     * Importante: la contraseña en claro NUNCA se persiste, ni siquiera en el
     * log. Solo se guardan metadatos de la operación y la fecha del cambio.
     */
    private function log(User $admin, Candidate $candidate, bool $wasGenerated): ?ActivityLog
    {
        $this->activityLog->log(
            causer: $admin,
            subject: $candidate,
            event: self::EVENT,
            description: $wasGenerated
                ? "Contraseña restablecida por el administrador {$admin->name} para el candidato {$candidate->name} (contraseña temporal generada)."
                : "Contraseña restablecida por el administrador {$admin->name} para el candidato {$candidate->name} (contraseña definida manualmente).",
            properties: [
                'candidate_id' => $candidate->id,
                'candidate_name' => $candidate->name,
                'candidate_email' => $candidate->email,
                'candidate_dui_nit' => $candidate->dui_nit,
                'password_origin' => $wasGenerated ? 'generated' : 'manual',
                'is_temporary' => true,
                'candidate_test_completed' => (bool) $candidate->test_completed,
                'had_started_test' => (bool) $candidate->test_started_at,
                'changed_at' => now()->toIso8601String(),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ],
            changes: [
                // Deliberadamente sin el hash ni la contraseña: solo el hecho del cambio.
                'password' => ['changed' => true],
            ],
        );

        return ActivityLog::query()
            ->where('event', self::EVENT)
            ->where('subject_type', Candidate::class)
            ->where('subject_id', $candidate->id)
            ->latest('id')
            ->first();
    }
}
