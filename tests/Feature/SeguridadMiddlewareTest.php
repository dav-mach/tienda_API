<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pruebas de los middlewares de seguridad (requisito 6): las rutas
 * protegidas deben devolver 401 sin un JWT válido.
 *
 * Se usa un data provider (rutasProtegidas) para correr el mismo test
 * una vez por cada ruta, dejando documentado de un vistazo qué rutas
 * están protegidas.
 */
class SeguridadMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function rutasProtegidas(): array
    {
        return [
            'ver carrito' => ['get', '/api/v1/carrito'],
            'resumen del carrito' => ['get', '/api/v1/carrito/resumen'],
            'agregar al carrito' => ['post', '/api/v1/carrito/items'],
            'vaciar carrito' => ['delete', '/api/v1/carrito'],
            'checkout' => ['post', '/api/v1/checkout'],
            'ver usuario actual' => ['get', '/api/v1/auth/me'],
            'crear producto' => ['post', '/api/v1/productos'],
            'crear categoría' => ['post', '/api/v1/categorias'],
        ];
    }

    #[DataProvider('rutasProtegidas')]
    public function test_sin_token_la_ruta_protegida_devuelve_401(string $metodo, string $url): void
    {
        // Arrange: sin token (nada que preparar).

        // Act: pegarle a la ruta protegida sin autenticación.
        $response = $this->json($metodo, $url);

        // Assert: acceso denegado.
        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    #[DataProvider('rutasProtegidas')]
    public function test_con_token_invalido_la_ruta_protegida_devuelve_401(string $metodo, string $url): void
    {
        // Arrange: un token roto.
        $headers = ['Authorization' => 'Bearer token.completamente.invalido'];

        // Act: pegarle a la ruta con el token inválido.
        $response = $this->json($metodo, $url, [], $headers);

        // Assert: acceso denegado.
        $response->assertStatus(401);
    }

    public function test_con_token_valido_se_pasa_el_control_del_middleware(): void
    {
        // Arrange: un usuario autenticado.
        $user = User::factory()->create();

        // Act: pegarle a una ruta protegida con el token válido.
        $response = $this->getJson('/api/v1/auth/me', $this->authHeader($user));

        // Assert: el middleware deja pasar.
        $response->assertStatus(200);
    }
}
