<?php

namespace Tests\Feature;

use App\Models\Candidate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Límite de intentos en el registro público de candidatos.
 *
 * Es la única ruta que crea cuentas sin autenticación, así que se protege con
 * un límite por IP (5 registros por hora) y otro por DUI/NIT (3 intentos por
 * hora) para frenar tanto la creación masiva de cuentas como la insistencia
 * sobre una misma identidad rotando direcciones.
 */
class CandidateRegistrationThrottleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Datos válidos de un candidato, con el DUI/NIT y el correo parametrizables.
     *
     * @return array<string, mixed>
     */
    private function datos(int $indice = 1, ?string $duiNit = null): array
    {
        return [
            'name' => "Candidato {$indice}",
            'email' => "candidato{$indice}@example.test",
            'dui_nit' => $duiNit ?? sprintf('%08d-9', $indice),
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
            'age' => 25,
            'occupation' => 'Estudiante',
            'education_level' => 'Universitario',
            '_token' => csrf_token(),
        ];
    }

    /**
     * Envía el formulario desde una IP concreta como visitante anónimo.
     *
     * Es importante cerrar la sesión del guard `candidate` en cada intento: el
     * registro autentica al candidato, y con una sesión abierta el middleware
     * `guest:candidate` redirigiría la petición sin llegar al controlador.
     */
    private function registrarDesde(string $ip, array $datos)
    {
        auth('candidate')->logout();

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/register')
            ->post('/register', $datos);
    }

    public function test_los_primeros_registros_desde_una_ip_funcionan(): void
    {
        // Con el límite de 5 por hora, los primeros 5 registros válidos entran.
        for ($i = 1; $i <= 5; $i++) {
            $this->registrarDesde('10.0.0.1', $this->datos($i))
                ->assertRedirect('/instrucciones');
        }

        $this->assertSame(5, Candidate::count());
    }

    public function test_el_sexto_registro_desde_la_misma_ip_se_bloquea(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->registrarDesde('10.0.0.2', $this->datos($i));
        }

        $respuesta = $this->registrarDesde('10.0.0.2', $this->datos(6));

        $respuesta->assertStatus(429);
        $respuesta->assertSessionHas('error');

        // El candidato 6 no debe haberse creado.
        $this->assertSame(5, Candidate::count());
        $this->assertDatabaseMissing('candidates', ['email' => 'candidato6@example.test']);
    }

    public function test_el_mensaje_de_bloqueo_explica_el_tiempo_de_espera(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->registrarDesde('10.0.0.3', $this->datos($i));
        }

        $respuesta = $this->registrarDesde('10.0.0.3', $this->datos(6));

        $respuesta->assertStatus(429);
        $this->assertStringContainsString(
            'límite de intentos',
            (string) session('error')
        );
        // El registro se bloquea por horas, así que el aviso lo expresa así.
        $this->assertStringContainsString('hora', (string) session('error'));
    }

    public function test_cambiar_la_ip_no_permite_abusar_del_mismo_dui(): void
    {
        $dui = '05012345-6';

        // Primer intento: candidato nuevo con ese DUI, entra.
        $this->registrarDesde('10.1.0.1', $this->datos(1, $dui))
            ->assertRedirect('/instrucciones');

        // Dos intentos más con el mismo DUI: el DUI es único en la base, así que
        // fallan por validación, pero igual consumen la cuota del limitador.
        foreach ([2, 3] as $indice) {
            $this->registrarDesde('10.1.0.1', $this->datos($indice, $dui))
                ->assertRedirect('/register');
        }

        $this->assertSame(1, Candidate::where('dui_nit', $dui)->count());

        // Cuarto intento con el mismo DUI: se bloquea. La IP solo lleva 4 de 5
        // intentos, así que el bloqueo lo produce el límite del DUI.
        $this->registrarDesde('10.1.0.1', $this->datos(4, $dui))
            ->assertStatus(429);

        // Y desde otra IP limpia el mismo DUI sigue bloqueado.
        $this->registrarDesde('10.1.0.99', $this->datos(5, $dui))
            ->assertStatus(429);

        $this->assertSame(1, Candidate::where('dui_nit', $dui)->count());
    }

    public function test_el_intento_bloqueado_conserva_los_datos_del_formulario(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->registrarDesde('10.0.0.4', $this->datos($i));
        }

        $this->registrarDesde('10.0.0.4', $this->datos(6))
            ->assertStatus(429)
            ->assertSessionHasInput('email', 'candidato6@example.test');
    }

    public function test_el_limite_se_aplica_antes_de_validar_el_formulario(): void
    {
        // Un bot que envía basura también consume su cuota: no puede usar el
        // registro como endpoint de sondeo indefinido.
        for ($i = 1; $i <= 5; $i++) {
            $this->registrarDesde('10.0.0.5', [
                'name' => '',
                'email' => 'no-es-un-email',
                'dui_nit' => '',
                'password' => 'x',
                '_token' => csrf_token(),
            ]);
        }

        $this->registrarDesde('10.0.0.5', ['email' => 'no-es-un-email', '_token' => csrf_token()])
            ->assertStatus(429);
    }

    public function test_otra_ip_no_se_ve_afectada_por_el_bloqueo(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->registrarDesde('10.2.0.1', $this->datos($i));
        }

        $this->registrarDesde('10.2.0.1', $this->datos(6))->assertStatus(429);

        // Un candidato legítimo desde otra red sigue pudiendo registrarse.
        $this->registrarDesde('10.2.0.2', $this->datos(7))
            ->assertRedirect('/instrucciones');
    }

    public function test_el_formulario_de_registro_muestra_el_error_de_bloqueo(): void
    {
        $ip = '10.0.0.6';

        // Se agota la cuota de 5 intentos por hora.
        for ($i = 1; $i <= 5; $i++) {
            $this->registrarDesde($ip, $this->datos($i));
        }

        // El siguiente intento vuelve al formulario con el mensaje de error.
        auth('candidate')->logout();

        $respuesta = $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/register', $this->datos(6));

        $respuesta->assertStatus(429);
        $respuesta->assertSessionHas('error');

        // La vista de registro renderiza el mensaje flash de error.
        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/register')
            ->assertOk()
            ->assertSee('límite de intentos');
    }
}
