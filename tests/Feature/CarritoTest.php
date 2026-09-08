<?php

namespace Tests\Feature;

use App\Models\CarritoItem;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas de integración del Carrito (requisito 3): agregar, quitar,
 * validación de stock, resumen, y protección por JWT.
 */
class CarritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_agregar_un_producto_al_carrito_con_token(): void
    {
        // Arrange: un usuario autenticado y un producto con stock.
        $user = User::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10]);

        // Act: agregar 2 unidades al carrito.
        $response = $this->postJson('/api/v1/carrito/items', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ], $this->authHeader($user));

        // Assert: 201 y la línea quedó en la base.
        $response->assertStatus(201)
            ->assertJson(['success' => true]);
        $this->assertDatabaseHas('carrito_items', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);
    }

    public function test_agregar_al_carrito_sin_token_devuelve_401(): void
    {
        // Arrange: un producto, pero sin usuario autenticado.
        $producto = Producto::factory()->create(['stock' => 10]);

        // Act: intentar agregar sin token.
        $response = $this->postJson('/api/v1/carrito/items', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ]);

        // Assert: acceso denegado.
        $response->assertStatus(401);
    }

    public function test_no_se_puede_agregar_mas_cantidad_que_el_stock(): void
    {
        // Arrange: un producto con solo 3 de stock.
        $user = User::factory()->create();
        $producto = Producto::factory()->create(['stock' => 3]);

        // Act: intentar agregar 10 unidades.
        $response = $this->postJson('/api/v1/carrito/items', [
            'producto_id' => $producto->id,
            'cantidad' => 10,
        ], $this->authHeader($user));

        // Assert: conflicto por stock insuficiente.
        $response->assertStatus(409)
            ->assertJson(['success' => false]);
    }

    public function test_quitar_un_producto_del_carrito(): void
    {
        // Arrange: un usuario con un producto ya agregado al carrito.
        $user = User::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10]);
        $this->postJson('/api/v1/carrito/items', [
            'producto_id' => $producto->id,
            'cantidad' => 1,
        ], $this->authHeader($user));
        $itemId = CarritoItem::first()->id;

        // Act: quitar ese item.
        $response = $this->deleteJson("/api/v1/carrito/items/{$itemId}", [], $this->authHeader($user));

        // Assert: 200 y la línea ya no está en la base.
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
        $this->assertDatabaseMissing('carrito_items', ['id' => $itemId]);
    }

    public function test_el_resumen_del_carrito_devuelve_los_totales(): void
    {
        // Arrange: un carrito con 2 unidades de un producto de $10.000.
        $user = User::factory()->create();
        $producto = Producto::factory()->create(['precio' => 10000, 'stock' => 10]);
        $this->postJson('/api/v1/carrito/items', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ], $this->authHeader($user));

        // Act: pedir el resumen.
        $response = $this->getJson('/api/v1/carrito/resumen', $this->authHeader($user));

        // Assert: subtotal e impuestos calculados.
        $response->assertStatus(200)
            ->assertJson(['data' => [
                'subtotal' => 20000,
                'impuestos' => 4200,
            ]]);
    }
}
