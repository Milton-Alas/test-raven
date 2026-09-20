<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Support\DuiNitCipher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Observación 5 — Validación por formato del identificador DUI/NIT.
 *
 * Se mantiene **un solo campo** (`dui_nit`), por decisión de diseño justificada en
 * el informe de observaciones: tras la homologación (D.L. 203/2021) cada persona
 * tiene un único número de identificación vigente, y un campo único con un único
 * índice garantiza que nadie se registre dos veces. El tipo de documento no se
 * almacena: se deduce del formato del valor.
 *
 * Estas pruebas fijan esa regla: 9 dígitos para DUI, 14 para NIT.
 */
class RnfDocumentFormatTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Token de sesión explícito.
     *
     * No se usa csrf_token(): según el orden de arranque del contenedor puede
     * devolver una cadena vacía y la petición falla con 419 sin llegar al
     * controlador, lo que daría una prueba que no verifica nada.
     */
    private const TOKEN = 'token-de-pruebas';

    /**
     * Envía el formulario de registro como visitante anónimo.
     */
    private function registrar(array $datos)
    {
        return $this->withSession(['_token' => self::TOKEN])
            ->from('/register')
            ->post('/register', array_merge(['_token' => self::TOKEN], $datos));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function identificadoresValidos(): array
    {
        return [
            'DUI con guion' => ['05123456-7', 'dui'],
            'DUI sin guion' => ['051234567', 'dui'],
            'NIT con guiones' => ['0614-120387-101-2', 'nit'],
            'NIT sin guiones' => ['06141203871012', 'nit'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function identificadoresInvalidos(): array
    {
        return [
            'muy corto' => ['12345'],
            'longitud intermedia' => ['1234567890123'],
            'muy largo' => ['123456789012345'],
            'solo letras de 9' => ['abcdefghi'],
            'letras y dígitos' => ['0512345A7'],
            'vacío' => [''],
            'solo guiones' => ['----'],
        ];
    }

    #[DataProvider('identificadoresValidos')]
    public function test_reconoce_los_formatos_validos(string $valor, string $tipoEsperado): void
    {
        $this->assertSame($tipoEsperado, DuiNitCipher::documentType($valor));
    }

    #[DataProvider('identificadoresInvalidos')]
    public function test_rechaza_los_formatos_invalidos(string $valor): void
    {
        $this->assertNull(DuiNitCipher::documentType($valor));
    }

    public function test_el_registro_acepta_un_dui_valido(): void
    {
        $this->registrar([
            'name' => 'Candidato DUI',
            'email' => 'dui@example.test',
            'dui_nit' => '05123456-7',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
            'age' => 30,
            'occupation' => 'Docente',
            'education_level' => 'Universitario',
        ])->assertRedirect('/instrucciones');

        $this->assertSame(1, Candidate::count());
    }

    public function test_el_registro_acepta_un_nit_valido(): void
    {
        $this->registrar([
            'name' => 'Candidato NIT',
            'email' => 'nit@example.test',
            'dui_nit' => '0614-120387-101-2',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
            'age' => 30,
            'occupation' => 'Docente',
            'education_level' => 'Universitario',
        ])->assertRedirect('/instrucciones');

        $this->assertSame(1, Candidate::count());
    }

    #[DataProvider('identificadoresInvalidos')]
    public function test_el_registro_rechaza_los_formatos_invalidos(string $valor): void
    {
        $this->registrar([
            'name' => 'Candidato Inválido',
            'email' => 'invalido@example.test',
            'dui_nit' => $valor,
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
            'age' => 30,
            'occupation' => 'Docente',
            'education_level' => 'Universitario',
        ])->assertSessionHasErrors('dui_nit');

        $this->assertSame(0, Candidate::count());
    }

    public function test_dos_formatos_del_mismo_dui_son_la_misma_identidad(): void
    {
        // La normalización es lo que permite que un único campo siga garantizando
        // la unicidad, con o sin guiones.
        $this->assertSame(
            DuiNitCipher::hash('05123456-7'),
            DuiNitCipher::hash('051234567'),
            'El mismo DUI con y sin guiones debe producir el mismo índice.'
        );
    }

    public function test_el_login_acepta_el_dui_con_o_sin_guiones(): void
    {
        Candidate::create([
            'name' => 'Candidato Login',
            'email' => 'login@example.test',
            'dui_nit' => '05123456-7',
            'password' => 'clave-segura-123',
            'age' => 30,
            'is_active' => true,
        ]);

        auth('candidate')->logout();

        $this->withSession(['_token' => self::TOKEN])
            ->from('/login')
            ->post('/login', [
                'login' => '051234567',
                'password' => 'clave-segura-123',
                '_token' => self::TOKEN,
            ])->assertRedirect('/instrucciones');

        $this->assertAuthenticated('candidate');
    }

    public function test_la_etiqueta_del_tipo_es_legible(): void
    {
        $this->assertSame('DUI', DuiNitCipher::documentTypeLabel('05123456-7'));
        $this->assertSame('NIT', DuiNitCipher::documentTypeLabel('0614-120387-101-2'));
        $this->assertNull(DuiNitCipher::documentTypeLabel('123'));
    }
}
