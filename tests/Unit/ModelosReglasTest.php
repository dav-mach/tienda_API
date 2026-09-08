<?php

namespace Tests\Unit;

use App\Models\Carrito;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas unitarias de reglas de los modelos (requisito 2):
 * CarritoItem::subtotal() y Carrito::estaVacio().
 */
class ModelosReglasTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_subtotal_de_una_linea_es_precio_por_cantidad(): void
    {
        // Arrange: un item de 3 unidades de un producto de $2.500.
        $carrito = Carrito::factory()->create();
        $producto = Producto::factory()->create(['precio' => 2500, 'stock' => 10]);
        $item = $carrito->items()->create(['producto_id' => $producto->id, 'cantidad' => 3]);

        // Act: calcular el subtotal de la línea.
        $subtotal = $item->subtotal();

        // Assert: 2.500 x 3 = 7.500.
        $this->assertSame(7500.0, $subtotal);
    }

    public function test_un_carrito_recien_creado_esta_vacio(): void
    {
        // Arrange: un carrito sin items.
        $carrito = Carrito::factory()->create();

        // Act: consultar si está vacío.
        $vacio = $carrito->estaVacio();

        // Assert: está vacío.
        $this->assertTrue($vacio);
    }

    public function test_un_carrito_con_items_no_esta_vacio(): void
    {
        // Arrange: un carrito con un item.
        $carrito = Carrito::factory()->create();
        $producto = Producto::factory()->create(['stock' => 10]);
        $carrito->items()->create(['producto_id' => $producto->id, 'cantidad' => 1]);

        // Act: consultar si está vacío.
        $vacio = $carrito->estaVacio();

        // Assert: no está vacío.
        $this->assertFalse($vacio);
    }
}
