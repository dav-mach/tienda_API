<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de integración de Productos (requisito 3): alta con token,
 * lectura pública, creación bloqueada sin token, y validación de datos.
 */
class ProductoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_listado_de_productos_es_publico(): void
    {
        // Arrange: algunos productos en la base.
        Producto::factory()->count(3)->create();

        // Act: pedir el listado sin autenticación.
        $response = $this->getJson('/api/v1/productos');

        // Assert: responde OK igual (es ruta pública).
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_crear_un_producto_con_token_funciona_y_lo_guarda(): void
    {
        // Arrange: un usuario autenticado y una categoría existente.
        $user = User::factory()->create();
        $categoria = Categoria::factory()->create();

        // Act: crear el producto con el token.
        $response = $this->postJson('/api/v1/productos', [
            'nombre' => 'Teclado mecánico',
            'precio' => 32000.00,
            'stock' => 15,
            'categoria_id' => $categoria->id,
        ], $this->authHeader($user));

        // Assert: 201 y quedó guardado en la base.
        $response->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['nombre' => 'Teclado mecánico']]);
        $this->assertDatabaseHas('productos', ['nombre' => 'Teclado mecánico', 'stock' => 15]);
    }

    public function test_crear_un_producto_sin_token_devuelve_401(): void
    {
        // Arrange: una categoría, pero sin usuario autenticado.
        $categoria = Categoria::factory()->create();

        // Act: intentar crear un producto sin token.
        $response = $this->postJson('/api/v1/productos', [
            'nombre' => 'Producto sin auth',
            'precio' => 1000,
            'stock' => 5,
            'categoria_id' => $categoria->id,
        ]);

        // Assert: acceso denegado y no se guardó nada.
        $response->assertStatus(401);
        $this->assertDatabaseMissing('productos', ['nombre' => 'Producto sin auth']);
    }

    public function test_crear_un_producto_con_datos_invalidos_devuelve_422(): void
    {
        // Arrange: un usuario autenticado y datos inválidos (falta nombre,
        // precio negativo).
        $user = User::factory()->create();

        // Act: intentar crear el producto inválido.
        $response = $this->postJson('/api/v1/productos', [
            'precio' => -50,
            'stock' => 5,
        ], $this->authHeader($user));

        // Assert: error de validación.
        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }
}
