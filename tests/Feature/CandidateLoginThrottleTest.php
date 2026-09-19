<?php

namespace Tests\Feature;

use App\Models\Candidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Límite de intentos en el login de candidatos.
 *
 * Sin límite, `POST /login` permite probar contraseñas sin fin contra una cuenta
 * (fuerza bruta). Se aplican dos límites complementarios: por IP (ataque masivo
 * desde un origen) y por identificador (ataque distribuido contra una cuenta
 * concreta, rotando direcciones).
 *
 * El middleware solo cuenta los intentos FALLIDOS: un login correcto no consume
 * cuota, así que un candidato legítimo nunca se ve afectado.
 */
class CandidateLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Token de sesión explícito.
     *
     * No se usa csrf_token(): tras cerrar la sesión del guard, el helper puede
     * devolver una cadena vacía según el orden de arranque del contenedor, y la
     * petición falla con 419 sin llegar al controlador.
     */
    private const TOKEN = 'token-de-pruebas';

    protected function setUp(): void
    {
        parent::setUp();

        // Cada prueba debe partir de una cuota limpia: el contador vive en la
        // caché, que en los tests es el store "array".
        cache()->clear();
    }

    private function candidato(string $email = 'candidato@example.test', string $password = 'clave-correcta-123'): Candidate
    {
        return Candidate::create([
            'name' => 'Candidato Login',
            'email' => $email,
            'dui_nit' => '12345678-9',
            'password' => $password,
            'age' => 25,
            'occupation' => 'Estudiante',
            'education_level' => 'Universitario',
            'is_active' => true,
        ]);
    }

    /**
     * Intento de login anónimo desde una IP concreta.
     */
    private function intentar(string $ip, string $login, string $password)
    {
        auth('candidate')->logout();

        return $this->withSession(['_token' => self::TOKEN])
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/login')
            ->post('/login', [
                'login' => $login,
                'password' => $password,
                '_token' => self::TOKEN,
            ]);
    }

    public function test_los_intentos_fallidos_dentro_del_limite_no_bloquean(): void
    {
        $this->candidato();

        // 5 intentos fallidos por minuto están permitidos: el candidato ve el
        // error de credenciales, no un bloqueo.
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('10.10.0.1', 'candidato@example.test', 'clave-mala')
                ->assertSessionHasErrors('login');
        }
    }

    public function test_el_sexto_intento_fallido_desde_la_misma_ip_se_bloquea(): void
    {
        $this->candidato();

        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('10.10.0.2', 'candidato@example.test', 'clave-mala');
        }

        $respuesta = $this->intentar('10.10.0.2', 'candidato@example.test', 'clave-mala');

        $respuesta->assertStatus(429);
        $respuesta->assertSessionHas('error');
        $this->assertStringContainsString('límite de intentos', (string) session('error'));
    }

    public function test_el_bloqueo_no_depende_de_que_la_clave_sea_correcta(): void
    {
        $this->candidato();

        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('10.10.0.3', 'candidato@example.test', 'clave-mala');
        }

        // Incluso con la contraseña correcta, el bloqueo sigue vigente mientras
        // dure la ventana: es lo que impide usar el formulario como oráculo.
        $this->intentar('10.10.0.3', 'candidato@example.test', 'clave-correcta-123')
            ->assertStatus(429);

        $this->assertGuest('candidate');
    }

    public function test_un_login_correcto_no_consume_cuota(): void
    {
        $this->candidato();

        // Tres fallos previos (por debajo del límite de 5).
        for ($i = 1; $i <= 3; $i++) {
            $this->intentar('10.10.0.4', 'candidato@example.test', 'clave-mala');
        }

        // El login correcto entra y no agota la cuota.
        $this->intentar('10.10.0.4', 'candidato@example.test', 'clave-correcta-123')
            ->assertRedirect('/instrucciones');

        $this->assertAuthenticatedAs(
            Candidate::where('email', 'candidato@example.test')->first(),
            'candidate'
        );

        // Y tras salir, otro intento fallido sigue siendo un error de
        // credenciales y no un bloqueo: los fallos previos eran 3 + 0.
        $this->intentar('10.10.0.4', 'candidato@example.test', 'clave-mala')
            ->assertSessionHasErrors('login');
    }

    public function test_el_limite_por_identificador_frena_la_fuerza_bruta_distribuida(): void
    {
        $this->candidato();

        // 5 fallos sobre la misma cuenta desde 5 IP distintas: ninguna IP llega
        // a su propio límite, así que solo puede frenarlo el límite por
        // identificador.
        foreach (['10.11.0.1', '10.11.0.2', '10.11.0.3', '10.11.0.4', '10.11.0.5'] as $ip) {
            $this->intentar($ip, 'candidato@example.test', 'clave-mala')
                ->assertSessionHasErrors('login');
        }

        // El sexto intento, desde una IP limpia, ya está bloqueado.
        $this->intentar('10.11.0.99', 'candidato@example.test', 'clave-mala')
            ->assertStatus(429);
    }

    public function test_el_limite_por_identificador_es_por_cuenta_y_no_global(): void
    {
        $this->candidato();
        Candidate::create([
            'name' => 'Otro Candidato',
            'email' => 'otro@example.test',
            'dui_nit' => '87654321-0',
            'password' => 'clave-correcta-456',
            'age' => 30,
            'is_active' => true,
        ]);

        // Se agota la cuota del identificador de la primera cuenta (5 fallos).
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('10.12.0.1', 'candidato@example.test', 'clave-mala');
        }
        $this->intentar('10.12.0.1', 'candidato@example.test', 'clave-mala')->assertStatus(429);

        // Otra cuenta sí puede entrar: el bloqueo está atado al identificador,
        // no a la IP ni al endpoint en general. Se usa una IP limpia para
        // aislar el límite por identificador del límite por IP.
        $this->intentar('10.12.0.2', 'otro@example.test', 'clave-correcta-456')
            ->assertRedirect('/instrucciones');
    }

    public function test_el_login_por_dui_nit_tambien_esta_limitado(): void
    {
        $this->candidato();

        // El campo acepta email o DUI/NIT; se comprueba la variante DUI.
        for ($i = 1; $i <= 5; $i++) {
            $this->intentar('10.13.0.1', '12345678-9', 'clave-mala')
                ->assertSessionHasErrors('login');
        }

        $this->intentar('10.13.0.1', '12345678-9', 'clave-mala')->assertStatus(429);
    }

    public function test_el_formulario_de_login_muestra_el_aviso_de_bloqueo(): void
    {
        $this->candidato();
        $ip = '10.14.0.1';

        for ($i = 1; $i <= 5; $i++) {
            $this->intentar($ip, 'candidato@example.test', 'clave-mala');
        }

        $respuesta = $this->intentar($ip, 'candidato@example.test', 'clave-mala');
        $respuesta->assertStatus(429);

        // La vista de login renderiza el mensaje flash de error.
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/login')
            ->assertOk()
            ->assertSee('límite de intentos');
    }
}
