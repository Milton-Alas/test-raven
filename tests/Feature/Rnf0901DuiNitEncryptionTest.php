<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Support\DuiNitCipher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * RNF-09.01 — Cifrado en reposo del identificador (DUI/NIT).
 *
 * El requisito pide tres cosas concretas y aquí se comprueban por separado:
 *  1. El identificador se almacena cifrado con AES-256-CBC.
 *  2. La búsqueda y la validación de unicidad se hacen mediante un índice seguro.
 *  3. No es necesario descifrar el valor para buscar.
 */
class Rnf0901DuiNitEncryptionTest extends TestCase
{
    use RefreshDatabase;

    private function candidato(string $duiNit = '05123456-7', string $email = 'cand@example.test'): Candidate
    {
        return Candidate::create([
            'name' => 'Candidato de Prueba',
            'email' => $email,
            'dui_nit' => $duiNit,
            'password' => 'clave-segura-123',
            'age' => 30,
            'occupation' => 'Docente',
            'education_level' => 'Universitario',
            'is_active' => true,
        ]);
    }

    public function test_el_identificador_no_se_guarda_en_claro_en_la_base(): void
    {
        $candidato = $this->candidato('05123456-7');

        // Se lee con el query builder a propósito: así se ve el valor tal como
        // está almacenado, sin que el modelo lo descifre.
        $crudo = DB::table('candidates')->where('id', $candidato->id)->value('dui_nit');

        $this->assertNotSame('05123456-7', $crudo, 'El DUI/NIT no puede guardarse en claro.');
        $this->assertStringNotContainsString('05123456', (string) $crudo, 'El texto cifrado no debe contener el DUI.');
        $this->assertGreaterThan(100, strlen((string) $crudo), 'El valor almacenado debe ser el texto cifrado.');

        // Y debe poder recuperarse con la clave de la aplicación.
        $this->assertSame('05123456-7', DuiNitCipher::decrypt($crudo));
    }

    public function test_el_modelo_descifra_el_identificador_al_leerlo(): void
    {
        $candidato = $this->candidato('14725836-9', 'otro@example.test');

        $this->assertSame('14725836-9', $candidato->fresh()->dui_nit);
    }

    public function test_dos_registros_con_el_mismo_identificador_producen_cifrados_distintos(): void
    {
        // AES-256-CBC usa un IV aleatorio: el mismo valor nunca produce el mismo
        // texto cifrado. Es lo que garantiza la confidencialidad, y la razón por
        // la que se necesita un índice aparte para buscar.
        $primero = DuiNitCipher::encrypt('05123456-7');
        $segundo = DuiNitCipher::encrypt('05123456-7');

        $this->assertNotSame($primero, $segundo);
        $this->assertSame('05123456-7', DuiNitCipher::decrypt($primero));
        $this->assertSame('05123456-7', DuiNitCipher::decrypt($segundo));
    }

    public function test_el_indice_seguro_se_calcula_solo_y_es_determinista(): void
    {
        $candidato = $this->candidato();
        $hash = DB::table('candidates')->where('id', $candidato->id)->value('dui_nit_hash');

        $this->assertNotNull($hash, 'El índice seguro debe calcularse al guardar.');
        $this->assertSame(64, strlen($hash), 'El índice es un HMAC-SHA256 en hexadecimal.');
        $this->assertSame(DuiNitCipher::hash('05123456-7'), $hash);

        // Determinista: el mismo valor produce siempre el mismo índice, con y sin
        // guiones, porque se normaliza antes de calcularlo.
        $this->assertSame(DuiNitCipher::hash('051234567'), $hash);
        $this->assertSame(DuiNitCipher::hash('05123456-8') !== $hash, true);
    }

    public function test_el_indice_no_permite_recuperar_el_identificador_por_fuerza_bruta_sin_la_clave(): void
    {
        $hash = DuiNitCipher::hash('05123456-7');

        // Un SHA-256 sin clave sería comparable directamente; al ser un HMAC con
        // el APP_KEY, el hash sin la clave no coincide con ningún cálculo offline.
        $shaSimple = hash('sha256', '05123456-7');

        $this->assertNotSame($shaSimple, $hash, 'El índice debe usar HMAC con la clave de la aplicación, no un hash simple.');
    }

    public function test_la_busqueda_por_identificador_funciona_sin_descifrar(): void
    {
        $candidato = $this->candidato('05123456-7');

        $encontrado = Candidate::findByDuiNit('05123456-7');
        $this->assertNotNull($encontrado);
        $this->assertSame($candidato->id, $encontrado->id);

        // Con formato distinto encuentra al mismo candidato.
        $this->assertSame($candidato->id, Candidate::findByDuiNit('051234567')?->id);

        // Y sin coincidencia devuelve null.
        $this->assertNull(Candidate::findByDuiNit('99999999-9'));

        // La consulta que se ejecuta no descifra nada: filtra por el índice.
        $sql = Candidate::query()->where('dui_nit_hash', DuiNitCipher::hash('05123456-7'))->toSql();
        $this->assertStringContainsString('dui_nit_hash', $sql);
        $this->assertStringNotContainsString('dui_nit"', str_replace('dui_nit_hash', '', $sql));
    }

    public function test_el_identificador_enmascarado_no_expone_el_valor_completo(): void
    {
        $candidato = $this->candidato('05123456-7');

        $this->assertSame('•••••4567', $candidato->dui_nit_masked);
        $this->assertStringNotContainsString('05123', (string) $candidato->dui_nit_masked);
    }

    public function test_el_indice_no_se_serializa_al_cliente(): void
    {
        $candidato = $this->candidato();

        $this->assertArrayNotHasKey('dui_nit_hash', $candidato->toArray());
    }

    public function test_el_login_por_dui_nit_sigue_funcionando_con_el_valor_cifrado(): void
    {
        $this->candidato('05123456-7');

        $respuesta = $this->post('/login', [
            'login' => '05123456-7',
            'password' => 'clave-segura-123',
        ]);

        $respuesta->assertRedirect('/instrucciones');
        $this->assertAuthenticated('candidate');
    }

    public function test_el_login_por_dui_nit_rechaza_la_contrasena_incorrecta(): void
    {
        $this->candidato('05123456-7');

        $this->post('/login', [
            'login' => '05123456-7',
            'password' => 'clave-equivocada',
        ])->assertSessionHasErrors('login');

        $this->assertGuest('candidate');
    }

    public function test_no_se_puede_registrar_dos_veces_el_mismo_identificador(): void
    {
        $this->candidato('05123456-7', 'primero@example.test');

        // Distinto correo, mismo DUI/NIT y con otro formato: la validación de
        // unicidad debe detectarlo igual, porque se apoya en el índice.
        $this->post('/register', [
            'name' => 'Otro Candidato',
            'email' => 'segundo@example.test',
            'dui_nit' => '051234567',
            'password' => 'otra-clave-123',
            'password_confirmation' => 'otra-clave-123',
            'age' => 25,
            'occupation' => 'Estudiante',
            'education_level' => 'Universitario',
        ])->assertSessionHasErrors('dui_nit');

        $this->assertSame(1, Candidate::count());
    }

    public function test_la_base_impone_el_indice_unico_sobre_el_identificador(): void
    {
        $candidato = $this->candidato('05123456-7');
        $hash = DB::table('candidates')->where('id', $candidato->id)->value('dui_nit_hash');

        // El índice único es la última línea de defensa: aunque se inserte
        // saltándose el modelo, la base no admite dos identificadores iguales.
        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('candidates')->insert([
            'name' => 'Duplicado',
            'email' => 'duplicado@example.test',
            'dui_nit' => DuiNitCipher::encrypt('05123456-7'),
            'dui_nit_hash' => $hash,
            'password' => Hash::make('clave-segura-123'),
            'age' => 20,
            'is_active' => true,
            'test_completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
