<?php

namespace App\Filament\Resources\Candidates\Actions;

use App\Models\Candidate;
use App\Models\User;
use App\Services\CandidatePasswordResetService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Js;

/**
 * Acción de panel: un administrador restablece manualmente la contraseña de un
 * candidato.
 *
 * Caso que cubre: el candidato no tiene recuperación de contraseña por correo y
 * solo dispone de una oportunidad para rendir el test. Si olvida su clave antes
 * de rendir, queda bloqueado sin vía de autoservicio; esta acción es el
 * procedimiento de reseteo manual, y deja constancia en `activity_logs`.
 *
 * La contraseña generada se muestra UNA sola vez en una notificación
 * persistente: no se envía por correo y no puede recuperarse después, porque
 * solo se almacena su hash.
 */
class ResetCandidatePasswordAction
{
    public static function make(): Action
    {
        return Action::make('resetPassword')
            ->label('Restablecer contraseña')
            ->icon(Heroicon::OutlinedKey)
            ->color('warning')
            ->visible(fn (): bool => Auth::user()?->isAdmin() ?? false)
            ->modalHeading(fn (Candidate $record): string => "Restablecer contraseña de {$record->name}")
            ->modalDescription(fn (Candidate $record): string => static::modalDescriptionFor($record))
            ->modalSubmitActionLabel('Generar contraseña temporal')
            ->modalIcon(Heroicon::OutlinedKey)
            ->requiresConfirmation()
            ->action(function (Candidate $record): void {
                /** @var User|null $admin */
                $admin = Auth::user();

                if (! $admin) {
                    Notification::make()
                        ->title('No se pudo restablecer la contraseña')
                        ->body('La sesión del administrador no está disponible.')
                        ->danger()
                        ->send();

                    return;
                }

                $temporaryPassword = app(CandidatePasswordResetService::class)
                    ->reset($admin, $record);

                // La contraseña vive solo en esta respuesta: nunca se persiste
                // en claro ni se envía por correo.
                Notification::make()
                    ->title('Contraseña restablecida')
                    ->body(
                        'Contraseña temporal para <strong>'.e($record->name).'</strong>:'
                        .'<br><br><code style="font-size: 1.05rem; letter-spacing: .04em;">'
                        .e($temporaryPassword)
                        .'</code><br><br>'
                        .'Cópiala ahora y comunícala al candidato por un canal seguro. '
                        .'No podrá volver a consultarse: solo se guarda su hash.'
                    )
                    ->warning()
                    ->persistent()
                    ->actions([
                        Action::make('copyPassword')
                            ->label('Copiar contraseña')
                            ->icon(Heroicon::OutlinedClipboard)
                            ->alpineClickHandler(
                                'navigator.clipboard.writeText('.Js::from($temporaryPassword).')'
                            ),
                        Action::make('closeNotification')
                            ->label('Entendido')
                            ->color('gray')
                            ->close(),
                    ])
                    ->send();
            });
    }

    /**
     * Texto de contexto del modal. Es deliberadamente explícito sobre las
     * consecuencias, porque la operación invalida la contraseña anterior.
     */
    private static function modalDescriptionFor(Candidate $record): string
    {
        $lines = [
            'Se generará una contraseña temporal para '.$record->name.' ('.$record->email.').',
            'La contraseña actual dejará de funcionar de inmediato.',
            'La contraseña temporal se mostrará una única vez y no se envía por correo: deberá comunicarse al candidato por un canal seguro.',
            'La operación queda registrada en el historial de actividad con su usuario administrador.',
        ];

        if ($record->test_completed) {
            $lines[] = 'ATENCIÓN: este candidato ya finalizó su test. El reseteo le permitirá entrar al sistema, pero no habilitará un nuevo intento.';
        } elseif ($record->test_started_at) {
            $lines[] = 'ATENCIÓN: este candidato tiene un test en curso. Al entrar de nuevo se reanudará su sesión existente, respetando el tiempo restante.';
        }

        return implode("\n\n", $lines);
    }
}
