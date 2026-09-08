<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de integración del flujo de autenticación con JWT (requisito 3).
 * Cubren registro, login (exitoso e inválido) y el acceso a una ruta
 * protegida con y sin token.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_se_puede_registrar_y_recibe_un_token(): void
    {
        // Arrange: los datos de un usuario nuevo.
        $datos = [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@mail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // Act: registrarse.
        $response = $this->postJson('/api/v1/auth/register', $datos);

        // Assert: 201, viene un token, y el usuario quedó en la base.
        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['usuario' => ['id', 'name', 'email'], 'token']]);
        $this->assertDatabaseHas('users', ['email' => 'nuevo@mail.com']);
    }

    public function test_login_exitoso_devuelve_un_token(): void
    {
        // Arrange: un usuario existente con contraseña conocida.
        User::factory()->create([
            'email' => 'ana@mail.com',
            'password' => 'password123', // se hashea sola (cast 'hashed')
        ]);

        // Act: loguearse con las credenciales correctas.
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ana@mail.com',
            'password' => 'password123',
        ]);

        // Assert: 200 y devuelve el token con su metadata.
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expira_en']]);
    }

    public function test_login_con_contrasena_incorrecta_devuelve_401(): void
    {
        // Arrange: un usuario existente.
        User::factory()->create([
            'email' => 'ana@mail.com',
            'password' => 'password123',
        ]);

        // Act: loguearse con la contraseña equivocada.
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ana@mail.com',
            'password' => 'contrasena-incorrecta',
        ]);

        // Assert: acceso denegado.
        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_acceso_a_me_sin_token_devuelve_401(): void
    {
        // Arrange: no hay token (nada que preparar).

        // Act: pedir el usuario actual sin autenticación.
        $response = $this->getJson('/api/v1/auth/me');

        // Assert: acceso denegado.
        $response->assertStatus(401);
    }

    public function test_acceso_a_me_con_token_devuelve_el_usuario(): void
    {
        // Arrange: un usuario autenticado.
        $user = User::factory()->create();

        // Act: pedir el usuario actual con el token válido.
        $response = $this->getJson('/api/v1/auth/me', $this->authHeader($user));

        // Assert: devuelve los datos de ese usuario.
        $response->assertStatus(200)
            ->assertJson(['data' => ['email' => $user->email]]);
    }
}
