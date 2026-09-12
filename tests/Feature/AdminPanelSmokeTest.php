<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El panel de administración debe responder sin errores.
 *
 * Motivo de esta prueba: Filament 4 usa Illuminate\Support\Number::format(),
 * que exige la extensión intl de PHP. Sin ella, las páginas del panel fallan
 * con:
 *   RuntimeException: The "intl" PHP extension is required to use the [format] method.
 *
 * Si el PHP que ejecuta los tests no tiene intl, la prueba falla con un mensaje
 * que indica cómo resolverlo.
 */
class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('intl')) {
            $this->fail(
                'La extensión intl de PHP es obligatoria: Filament usa Number::format(). '
                .'Instálala con "sudo apt-get install -y php8.4-intl" o ejecuta los tests '
                .'con "./bin/php test", que usa el PHP que ya la incluye.'
            );
        }
    }

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

    public function test_las_paginas_del_panel_resuelven_sin_el_error_de_intl(): void
    {
        $admin = $this->admin();

        // Candidatos y resultados son las páginas donde se reportó el fallo.
        foreach (['/admin/candidates', '/admin/test-results'] as $ruta) {
            $respuesta = $this->actingAs($admin)->get($ruta);

            $respuesta->assertOk();
            // Si intl faltara, el mensaje de la excepción aparecería en la respuesta.
            $respuesta->assertDontSee('PHP extension is required', false);
        }
    }

    public function test_el_acceso_al_panel_sigue_restringido_por_rol(): void
    {
        $reportador = User::create([
            'name' => 'Reportero',
            'email' => 'reporter@example.test',
            'password' => 'password',
            'role' => User::ROLE_REPORTER,
            'is_active' => true,
        ]);

        // Recursos administrativos: el reportero no entra.
        $this->actingAs($reportador)->get('/admin/users')->assertForbidden();

        // Recursos de consulta: sí entra.
        $this->actingAs($reportador)->get('/admin/candidates')->assertOk();
    }
}
