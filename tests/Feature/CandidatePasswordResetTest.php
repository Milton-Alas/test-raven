<?php

namespace Tests\Feature;

use App\Filament\Resources\Candidates\Actions\ResetCandidatePasswordAction;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Models\ActivityLog;
use App\Models\Candidate;
use App\Models\User;
use App\Services\CandidatePasswordResetService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Js;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Reseteo manual de la contraseña de un candidato por parte de un
 * administrador, con registro en activity_logs.
 *
 * Caso de negocio: el candidato no tiene recuperación de contraseña por correo
 * y solo tiene una oportunidad para rendir el test; si olvida la clave antes de
 * rendir, necesita que el administrador se la restablezca.
 */
class CandidatePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin de Prueba',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function reporter(): User
    {
        return User::create([
            'name' => 'Reportero de Prueba',
            'email' => 'reporter@example.test',
            'password' => 'password',
            'role' => User::ROLE_REPORTER,
            'is_active' => true,
        ]);
    }

    private function candidate(array $attributes = []): Candidate
    {
        return Candidate::create(array_merge([
            'name' => 'Candidato de Prueba',
            'email' => 'candidato@example.test',
            'dui_nit' => '12345678-9',
            'password' => 'clave-olvidada-123',
            'age' => 25,
            'occupation' => 'Estudiante',
            'education_level' => 'Universitario',
            'is_active' => true,
        ], $attributes));
    }

    public function test_el_servicio_reemplaza_la_contrasena_y_registra_el_evento(): void
    {
        $admin = $this->admin();
        $candidate = $this->candidate();
        $hashOriginal = $candidate->password;

        $temporal = app(CandidatePasswordResetService::class)->reset($admin, $candidate);

        $candidate->refresh();

        // La contraseña anterior deja de funcionar y la nueva sí funciona.
        $this->assertFalse(Hash::check('clave-olvidada-123', $candidate->password));
        $this->assertTrue(Hash::check($temporal, $candidate->password));
        $this->assertNotSame($hashOriginal, $candidate->password);

        // Contraseña legible pero robusta.
        $this->assertSame(CandidatePasswordResetService::PASSWORD_LENGTH, strlen($temporal));
        $this->assertSame(0, preg_match('/[0O1lI5S8B2Z]/', $temporal), 'La contraseña temporal no debe contener caracteres ambiguos.');

        // Auditoría: causer = administrador, subject = candidato.
        $this->assertDatabaseHas('activity_logs', [
            'event' => CandidatePasswordResetService::EVENT,
            'causer_type' => User::class,
            'causer_id' => $admin->id,
            'subject_type' => Candidate::class,
            'subject_id' => $candidate->id,
        ]);

        $log = ActivityLog::where('event', CandidatePasswordResetService::EVENT)->firstOrFail();
        $this->assertSame('generated', $log->properties['password_origin']);
        $this->assertTrue($log->properties['is_temporary']);
        $this->assertSame($candidate->email, $log->properties['candidate_email']);
        $this->assertSame($admin->name, $log->causer_name);
        $this->assertSame($candidate->name, $log->subject_name);
    }

    public function test_la_contrasena_en_claro_nunca_queda_registrada(): void
    {
        $admin = $this->admin();
        $candidate = $this->candidate();

        $temporal = app(CandidatePasswordResetService::class)->reset($admin, $candidate);

        $contenidoDeLosLogs = json_encode(
            ActivityLog::all()->map(fn (ActivityLog $log): array => [
                'description' => $log->description,
                'properties' => $log->properties,
                'changes' => $log->changes,
            ])->all()
        );

        $this->assertStringNotContainsString($temporal, $contenidoDeLosLogs);
        $this->assertStringNotContainsString($candidate->password, $contenidoDeLosLogs);
        $this->assertStringNotContainsString('clave-olvidada-123', $contenidoDeLosLogs);
    }

    public function test_el_administrador_puede_definir_la_contrasena_manualmente(): void
    {
        $admin = $this->admin();
        $candidate = $this->candidate();

        app(CandidatePasswordResetService::class)->reset($admin, $candidate, 'ClaveDefinida.2026');

        $this->assertTrue(Hash::check('ClaveDefinida.2026', $candidate->fresh()->password));
        $this->assertSame(
            'manual',
            ActivityLog::where('event', CandidatePasswordResetService::EVENT)->firstOrFail()->properties['password_origin']
        );
    }

    public function test_el_administrador_puede_restablecer_desde_el_panel(): void
    {
        $admin = $this->admin();
        $candidate = $this->candidate();

        $this->actingAs($admin);

        Livewire::test(ListCandidates::class)
            ->assertActionVisible(TestAction::make('resetPassword')->table($candidate))
            ->callAction(TestAction::make('resetPassword')->table($candidate))
            ->assertHasNoActionErrors();

        $candidate->refresh();

        // Ya no sirve la clave olvidada y se creó el registro de auditoría.
        $this->assertFalse(Hash::check('clave-olvidada-123', $candidate->password));
        $this->assertDatabaseHas('activity_logs', [
            'event' => CandidatePasswordResetService::EVENT,
            'causer_id' => $admin->id,
            'subject_id' => $candidate->id,
        ]);
    }

    public function test_el_reportero_no_ve_la_accion_de_restablecer(): void
    {
        $reporter = $this->reporter();
        $candidate = $this->candidate();

        $this->actingAs($reporter);

        Livewire::test(ListCandidates::class)
            ->assertActionHidden(TestAction::make('resetPassword')->table($candidate));

        $this->assertDatabaseMissing('activity_logs', [
            'event' => CandidatePasswordResetService::EVENT,
        ]);
        $this->assertTrue(Hash::check('clave-olvidada-123', $candidate->fresh()->password));
    }

    public function test_el_reseteo_no_habilita_un_segundo_intento_del_test(): void
    {
        $admin = $this->admin();
        $candidate = $this->candidate([
            'test_completed' => true,
            'test_completed_at' => now(),
        ]);

        app(CandidatePasswordResetService::class)->reset($admin, $candidate);

        // El reseteo es para entrar al sistema, no para repetir el test.
        $this->assertTrue($candidate->fresh()->test_completed);
        $this->assertSame(
            true,
            ActivityLog::where('event', CandidatePasswordResetService::EVENT)->firstOrFail()->properties['candidate_test_completed']
        );
    }

    public function test_la_accion_del_panel_esta_registrada_y_es_de_confirmacion(): void
    {
        $this->actingAs($this->admin());

        $action = ResetCandidatePasswordAction::make();

        $this->assertSame('resetPassword', $action->getName());
        $this->assertTrue($action->isConfirmationRequired());
    }

    /**
     * Comprueba el HTML que realmente sirve la ruta del panel: el botón de
     * restablecer contraseña debe estar presente para el administrador.
     */
    public function test_la_pagina_de_candidatos_renderiza_el_boton_de_restablecer(): void
    {
        $admin = $this->admin();
        $this->candidate();

        $response = $this->actingAs($admin)->get('/admin/candidates');

        $response->assertOk();
        $response->assertSee('Restablecer contraseña');
        $response->assertSee('resetPassword', false);
    }

    /**
     * La contraseña que el administrador ve en pantalla debe ser exactamente la
     * que quedó guardada: si no coinciden, el candidato no podría entrar.
     */
    public function test_la_contrasena_mostrada_al_administrador_es_la_que_queda_guardada(): void
    {
        $admin = $this->admin();
        $candidate = $this->candidate();

        $this->actingAs($admin);

        $component = Livewire::test(ListCandidates::class)
            ->callAction(TestAction::make('resetPassword')->table($candidate));

        // Se lee la sesión ANTES de assertNotified(), que consume las notificaciones.
        $notificaciones = collect(session()->get('filament.claimed_notifications', []));
        $component->assertNotified('Contraseña restablecida');

        $this->assertCount(1, $notificaciones, 'Debe enviarse una única notificación con la contraseña.');

        $notificacion = $notificaciones->first();

        // El cuerpo muestra la contraseña temporal dentro de un <code>, escapada como HTML.
        $this->assertSame(1, preg_match('/<code[^>]*>(.*?)<\/code>/s', $notificacion['body'], $coincidencias));
        $contrasenaMostrada = html_entity_decode($coincidencias[1], ENT_QUOTES | ENT_HTML5);

        $this->assertSame(CandidatePasswordResetService::PASSWORD_LENGTH, strlen($contrasenaMostrada));
        $this->assertTrue(
            Hash::check($contrasenaMostrada, $candidate->fresh()->password),
            'La contraseña mostrada en el panel debe ser la que quedó almacenada.'
        );

        // El botón "Copiar contraseña" debe copiar exactamente la misma contraseña.
        $botonCopiar = collect($notificacion['actions'])->firstWhere('name', 'copyPassword');
        $this->assertNotNull($botonCopiar, 'La notificación debe ofrecer el botón de copiar.');
        $this->assertStringContainsString(
            Js::from($contrasenaMostrada),
            $botonCopiar['alpineClickHandler'],
            'El botón debe copiar la contraseña que realmente quedó guardada.'
        );

        // La notificación queda visible hasta que el administrador la cierre.
        $this->assertSame('persistent', $notificacion['duration']);
    }
}
