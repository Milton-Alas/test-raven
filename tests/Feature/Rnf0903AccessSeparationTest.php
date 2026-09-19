<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\TestResult;
use App\Models\TestSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RNF-09.03 — Separación de acceso entre candidato y administración.
 *
 * El requisito pide dos cosas:
 *  1. El candidato se limita a su propia sesión de test.
 *  2. Los resultados, datos psicométricos y demás información sensible quedan
 *     restringidos al panel administrativo mediante autorización por roles.
 *
 * Las dos se comprueban aquí de forma explícita, porque es el sub-requisito que
 * protege la confidencialidad de los datos psicométricos una vez calculados.
 */
class Rnf0903AccessSeparationTest extends TestCase
{
    use RefreshDatabase;

    private function candidato(string $email, string $duiNit): Candidate
    {
        return Candidate::create([
            'name' => 'Candidato '.$email,
            'email' => $email,
            'dui_nit' => $duiNit,
            'password' => 'clave-segura-123',
            'age' => 30,
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    private function reporter(): User
    {
        return User::create([
            'name' => 'Reportero',
            'email' => 'reporter@example.test',
            'password' => 'password',
            'role' => User::ROLE_REPORTER,
            'is_active' => true,
        ]);
    }

    /**
     * Resultado psicométrico completo asociado a un candidato.
     */
    private function resultadoDe(Candidate $candidato): TestResult
    {
        $sesion = TestSession::create([
            'candidate_id' => $candidato->id,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
            'elapsed_time' => 1800,
            'ip_address' => '190.85.1.20',
            'user_agent' => 'Mozilla/5.0 (prueba)',
        ]);

        return TestResult::create([
            'test_session_id' => $sesion->id,
            'candidate_id' => $candidato->id,
            'series_a_score' => 10,
            'series_b_score' => 9,
            'series_c_score' => 8,
            'series_d_score' => 7,
            'series_e_score' => 6,
            'total_score' => 40,
            'percentile' => 80,
            'diagnostic_range' => 2,
            'diagnostic_label' => 'Superior al Término Medio',
            'is_valid' => true,
            'total_time_seconds' => 1800,
            'calculated_at' => now(),
        ]);
    }

    // ------------------------------------------- el candidato y su sesión

    public function test_sin_sesion_no_se_accede_al_flujo_del_test(): void
    {
        foreach (['/instrucciones', '/test/question', '/test/timer', '/test/completed'] as $ruta) {
            $this->get($ruta)->assertRedirect();
        }
    }

    public function test_el_candidato_solo_ve_su_propia_sesion_de_test(): void
    {
        $propio = $this->candidato('propio@example.test', '05123456-7');
        $ajeno = $this->candidato('ajeno@example.test', '14725836-9');

        $sesionPropia = TestSession::create([
            'candidate_id' => $propio->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $sesionAjena = TestSession::create([
            'candidate_id' => $ajeno->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->actingAs($propio, 'candidate');

        // Las rutas del test resuelven siempre la sesión del candidato
        // autenticado: no existe ninguna que acepte el identificador de otra.
        $this->get("/test/question?session_id={$sesionAjena->id}")
            ->assertRedirect(); // Cae a la bienvenida: no hay sesión propia iniciada.

        // Y el endpoint de respuestas no puede escribir en la sesión ajena.
        $respuesta = $this->post('/test/answer', [
            'question_id' => 1,
            'answer' => 1,
            'session_id' => $sesionAjena->id,
        ]);

        $this->assertNotSame(200, $respuesta->getStatusCode());
        $this->assertDatabaseCount('test_answers', 0);
        $this->assertSame($propio->id, $sesionPropia->candidate_id);
    }

    public function test_un_candidato_no_puede_entrar_al_panel_administrativo(): void
    {
        $candidato = $this->candidato('cand@example.test', '05123456-7');
        $this->actingAs($candidato, 'candidate');

        // El panel usa el guard `web`; la sesión de candidato no sirve para él.
        $this->get('/admin')->assertRedirect();
        $this->get('/admin/test-results')->assertRedirect();
        $this->get('/admin/candidates')->assertRedirect();
        $this->assertGuest('web');
    }

    // ---------------------------------- el panel restringe los datos psicométricos

    public function test_los_resultados_psicometricos_solo_son_accesibles_con_rol_autorizado(): void
    {
        $candidato = $this->candidato('cand@example.test', '05123456-7');
        $this->resultadoDe($candidato);

        // Sin autenticar: fuera.
        $this->get('/admin/test-results')->assertRedirect();

        // Con un usuario desactivado: fuera (403, no solo redirección).
        $inactivo = User::create([
            'name' => 'Usuario Inactivo',
            'email' => 'inactivo@example.test',
            'password' => 'password',
            'role' => User::ROLE_REPORTER,
            'is_active' => false,
        ]);

        $this->actingAs($inactivo)->get('/admin/test-results')->assertForbidden();
    }

    public function test_el_reportero_accede_solo_a_los_recursos_de_consulta(): void
    {
        $candidato = $this->candidato('cand@example.test', '05123456-7');
        $this->resultadoDe($candidato);

        $this->actingAs($this->reporter());

        // Consulta: permitido.
        $this->get('/admin/test-results')->assertOk();
        $this->get('/admin/candidates')->assertOk();

        // Administración del sistema y del instrumento: denegado.
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/test-questions')->assertForbidden();
        $this->get('/admin/test-series')->assertForbidden();
        $this->get('/admin/percentile-tables')->assertForbidden();
        $this->get('/admin/diagnostic-ranges')->assertForbidden();
    }

    public function test_el_reportero_no_puede_modificar_ni_borrar_datos_psicometricos(): void
    {
        $candidato = $this->candidato('cand@example.test', '05123456-7');
        $resultado = $this->resultadoDe($candidato);

        $this->actingAs($this->reporter());

        // Las páginas de edición del panel están reservadas al administrador.
        $this->get("/admin/test-results/{$resultado->id}/edit")->assertForbidden();
        $this->get("/admin/candidates/{$candidato->id}/edit")->assertForbidden();
    }

    public function test_el_administrador_si_accede_a_los_datos_psicometricos(): void
    {
        $candidato = $this->candidato('cand@example.test', '05123456-7');
        $this->resultadoDe($candidato);

        $this->actingAs($this->admin())
            ->get('/admin/test-results')
            ->assertOk();
    }

    public function test_el_candidato_no_recibe_los_datos_psicometricos_por_api(): void
    {
        $candidato = $this->candidato('cand@example.test', '05123456-7');
        $this->resultadoDe($candidato);

        $this->actingAs($candidato, 'candidate');

        // Las rutas del test devuelven preguntas, no resultados ni percentiles.
        $respuesta = $this->get('/test/timer');

        $respuesta->assertStatus(404); // No hay sesión activa: no expone nada.
        $this->assertStringNotContainsString('percentile', $respuesta->getContent());
        $this->assertStringNotContainsString('diagnostic', $respuesta->getContent());
    }
}
