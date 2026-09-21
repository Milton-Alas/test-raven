<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Auditoría de la gestión de cuentas del panel.
 *
 * El rol decide a qué entra una cuenta (`admin` administra; `reporter` y
 * `evaluador` solo consultan), así que su asignación y su cambio se registran en
 * `activity_logs` con el mismo servicio y la misma estructura que el resto de
 * acciones auditadas (reseteo de contraseña de candidatos y exportaciones).
 */
class UserAccountAuditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin Auditor',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function usuario(string $rol, string $email): User
    {
        return User::create([
            'name' => 'Cuenta '.$rol,
            'email' => $email,
            'password' => 'password',
            'role' => $rol,
            'is_active' => true,
        ]);
    }

    private function evento(string $evento): ActivityLog
    {
        return ActivityLog::where('event', $evento)->firstOrFail();
    }

    // ----------------------------------------------------- alta desde el panel

    public function test_el_alta_de_una_cuenta_desde_el_panel_queda_auditada_con_su_rol(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('create'), [
                'name' => 'Evaluador del Panel',
                'email' => 'evaluador.panel@example.test',
                'role' => User::ROLE_EVALUADOR,
                'is_active' => true,
                'password' => 'clave-segura-123',
                'passwordConfirmation' => 'clave-segura-123',
            ])
            ->assertHasNoActionErrors();

        $creado = User::where('email', 'evaluador.panel@example.test')->firstOrFail();
        $log = ActivityLog::where('event', ActivityLogService::EVENT_USER_CREATED)
            ->where('subject_id', $creado->id)
            ->firstOrFail();

        $this->assertSame(User::class, $log->causer_type);
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame($creado->id, $log->subject_id);

        $this->assertSame(User::ROLE_EVALUADOR, $log->properties['role']);
        $this->assertSame($admin->id, $log->properties['changed_by_id']);
        $this->assertSame($admin->name, $log->properties['changed_by_name']);
        $this->assertSame(User::ROLE_ADMIN, $log->properties['changed_by_role']);
        $this->assertSame($creado->id, $log->properties['user_id']);
        $this->assertSame('Evaluador del Panel', $log->properties['user_name']);
        $this->assertSame('evaluador.panel@example.test', $log->properties['user_email']);
        $this->assertNotNull($log->properties['changed_at']);

        // El rol asignado también queda en `changes`, con la misma forma que un
        // cambio: antes no había rol, después es `evaluador`.
        $this->assertSame(['from' => null, 'to' => User::ROLE_EVALUADOR], $log->changes['role']);

        // Y con el contexto de la petición, igual que el resto de eventos.
        $this->assertNotNull($log->ip_address);
        $this->assertStringContainsString('Cuenta del panel creada por Admin Auditor', $log->description);
    }

    // --------------------------------------------------- cambio de rol

    public function test_el_cambio_de_rol_desde_el_panel_queda_auditado(): void
    {
        $admin = $this->admin();
        $cuenta = $this->usuario(User::ROLE_REPORTER, 'cuenta@example.test');

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('edit')->table($cuenta), [
                'role' => User::ROLE_EVALUADOR,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(User::ROLE_EVALUADOR, $cuenta->fresh()->role);

        $log = $this->evento(ActivityLogService::EVENT_USER_ROLE_CHANGED);

        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame($cuenta->id, $log->subject_id);

        $this->assertSame(User::ROLE_REPORTER, $log->properties['role_from']);
        $this->assertSame(User::ROLE_EVALUADOR, $log->properties['role_to']);
        $this->assertSame(['from' => User::ROLE_REPORTER, 'to' => User::ROLE_EVALUADOR], $log->changes['role']);

        $this->assertSame($admin->id, $log->properties['changed_by_id']);
        $this->assertSame(User::ROLE_ADMIN, $log->properties['changed_by_role']);
        $this->assertSame($cuenta->email, $log->properties['user_email']);
        $this->assertNotNull($log->ip_address);
        $this->assertStringContainsString('pasa de «reporter» a «evaluador»', $log->description);
    }

    public function test_el_cambio_a_cualquier_rol_queda_auditado_no_solo_al_evaluador(): void
    {
        $admin = $this->admin();
        $cuenta = $this->usuario(User::ROLE_REPORTER, 'promovida@example.test');

        $this->actingAs($admin);

        $cuenta->update(['role' => User::ROLE_ADMIN]);

        $log = $this->evento(ActivityLogService::EVENT_USER_ROLE_CHANGED);

        $this->assertSame(User::ROLE_REPORTER, $log->properties['role_from']);
        $this->assertSame(User::ROLE_ADMIN, $log->properties['role_to']);
    }

    public function test_guardar_una_cuenta_sin_cambiar_el_rol_no_genera_evento(): void
    {
        $cuenta = $this->usuario(User::ROLE_EVALUADOR, 'sin-cambio@example.test');

        $this->actingAs($this->admin());

        $cuenta->update(['name' => 'Nombre Nuevo']);
        $cuenta->update(['is_active' => false]);

        $this->assertSame(
            0,
            ActivityLog::where('event', ActivityLogService::EVENT_USER_ROLE_CHANGED)->count(),
            'Solo el cambio de rol debe auditarse; el resto de campos no.'
        );
    }

    // -------------------------------------------------------- fuera del panel

    public function test_el_alta_fuera_del_panel_no_atribuye_la_accion_a_ningun_usuario(): void
    {
        // Sin sesión: creación por consola, seeder o tarea programada.
        $this->usuario(User::ROLE_EVALUADOR, 'consola@example.test');

        $log = $this->evento(ActivityLogService::EVENT_USER_CREATED);

        $this->assertNull($log->causer_type);
        $this->assertNull($log->causer_id);
        $this->assertNull($log->properties['changed_by_id']);
        $this->assertSame(User::ROLE_EVALUADOR, $log->properties['role']);
        $this->assertStringContainsString('fuera del panel', $log->description);
    }

    // ------------------------------------------------------------ credenciales

    public function test_el_evento_no_registra_la_contrasena_de_la_cuenta(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('create'), [
                'name' => 'Cuenta Secreta',
                'email' => 'secreta@example.test',
                'role' => User::ROLE_EVALUADOR,
                'is_active' => true,
                'password' => 'ClaveSecreta.2026',
                'passwordConfirmation' => 'ClaveSecreta.2026',
            ]);

        $cuenta = User::where('email', 'secreta@example.test')->firstOrFail();

        $contenido = json_encode(
            ActivityLog::whereIn('event', [
                ActivityLogService::EVENT_USER_CREATED,
                ActivityLogService::EVENT_USER_ROLE_CHANGED,
            ])->get()->map(fn (ActivityLog $log): array => [
                'description' => $log->description,
                'properties' => $log->properties,
                'changes' => $log->changes,
            ])->all()
        );

        $this->assertStringNotContainsString('ClaveSecreta.2026', $contenido);
        $this->assertStringNotContainsString($cuenta->password, $contenido);
    }
}
