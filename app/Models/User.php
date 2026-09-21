<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\ActivityLogService;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_REPORTER = 'reporter';
    public const ROLE_EVALUADOR = 'evaluador';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_REPORTER,
            self::ROLE_EVALUADOR,
        ], true);
    }

    /**
     * Auditoría de las cuentas del panel: alta y cambio de rol.
     *
     * Vive en el modelo, y no en cada página o acción de Filament, porque el rol
     * decide a qué entra esa cuenta y hay varias vías para asignarlo (el alta y la
     * edición desde la lista, la página de edición, `artisan tinker`). Registrando
     * aquí, el evento queda escrito pase por donde pase. El registro lo escribe
     * `ActivityLogService`, con la misma estructura que el resto de eventos
     * auditados; la contraseña nunca se guarda.
     */
    protected static function booted(): void
    {
        static::created(function (self $user): void {
            app(ActivityLogService::class)->logUserCreated(Auth::user(), $user);
        });

        static::updated(function (self $user): void {
            if (! $user->wasChanged('role')) {
                return;
            }

            // En el evento `updated` el modelo ya sincronizó los cambios pero
            // todavía no el original, así que aquí `role` es el nuevo valor y
            // `getOriginal('role')` el anterior.
            app(ActivityLogService::class)->logUserRoleChange(
                Auth::user(),
                $user,
                (string) $user->getOriginal('role'),
                (string) $user->role,
            );
        });
    }

    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_REPORTER => 'Reporter',
            self::ROLE_EVALUADOR => 'Evaluador',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isReporter(): bool
    {
        return $this->role === self::ROLE_REPORTER;
    }

    public function isEvaluador(): bool
    {
        return $this->role === self::ROLE_EVALUADOR;
    }
}
