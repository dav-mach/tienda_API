<?php

namespace Tests\Unit;

use App\Models\Producto;
use Tests\TestCase;

/**
 * Pruebas unitarias de la regla de stock del modelo Producto (requisito 2).
 * hayStockDisponible() es lógica pura: no toca la base de datos, así que
 * se prueba con un modelo en memoria (new Producto([...])).
 */
class ProductoStockTest extends TestCase
{
    public function test_hay_stock_cuando_la_cantidad_pedida_es_menor_al_stock(): void
    {
        // Arrange: un producto con 10 de stock.
        $producto = new Producto(['stock' => 10]);

        // Act: consultar disponibilidad para 5 unidades.
        $disponible = $producto->hayStockDisponible(5);

        // Assert: hay stock.
        $this->assertTrue($disponible);
    }

    public function test_hay_stock_cuando_la_cantidad_es_exactamente_igual_al_stock(): void
    {
        // Arrange: producto con 10 de stock.
        $producto = new Producto(['stock' => 10]);

        // Act: pedir exactamente 10.
        $disponible = $producto->hayStockDisponible(10);

        // Assert: el borde exacto también es válido.
        $this->assertTrue($disponible);
    }

    public function test_no_hay_stock_cuando_la_cantidad_supera_el_stock(): void
    {
        // Arrange: producto con 10 de stock.
        $producto = new Producto(['stock' => 10]);

        // Act: pedir 11 (uno más que el disponible).
        $disponible = $producto->hayStockDisponible(11);

        // Assert: no hay stock.
        $this->assertFalse($disponible);
    }

    public function test_no_hay_stock_para_una_cantidad_de_cero_o_negativa(): void
    {
        // Arrange: producto con stock.
        $producto = new Producto(['stock' => 10]);

        // Act + Assert: pedir 0 o un negativo nunca es válido.
        $this->assertFalse($producto->hayStockDisponible(0));
        $this->assertFalse($producto->hayStockDisponible(-3));
    }

    public function test_un_producto_sin_stock_nunca_tiene_disponibilidad(): void
    {
        // Arrange: producto con stock 0.
        $producto = new Producto(['stock' => 0]);

        // Act: pedir 1 unidad.
        $disponible = $producto->hayStockDisponible(1);

        // Assert: no hay disponibilidad.
        $this->assertFalse($disponible);
    }
}
