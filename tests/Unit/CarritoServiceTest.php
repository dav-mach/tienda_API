<?php

namespace Tests\Unit;

use App\Models\Carrito;
use App\Models\Producto;
use App\Services\CarritoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas unitarias de CarritoService (requisito 2: lógica de negocio).
 * Verifican el cálculo del resumen de compra: subtotal, impuestos (21%),
 * costo de envío (gratis desde $100.000) y total.
 *
 * Cada test sigue el patrón Triple A (Arrange, Act, Assert).
 */
class CarritoServiceTest extends TestCase
{
    use RefreshDatabase;

    private CarritoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CarritoService();
    }

    public function test_el_resumen_de_un_carrito_vacio_da_todo_en_cero(): void
    {
        // Arrange: un carrito sin ningún producto.
        $carrito = Carrito::factory()->create();

        // Act: calcular su resumen.
        $resumen = $this->service->calcularResumen($carrito);

        // Assert: todos los totales en cero.
        $this->assertSame(0, $resumen->cantidadItems);
        $this->assertSame(0.0, $resumen->subtotal);
        $this->assertSame(0.0, $resumen->impuestos);
        $this->assertSame(0.0, $resumen->costoEnvio);
        $this->assertSame(0.0, $resumen->total);
    }

    public function test_calcula_subtotal_iva_y_envio_sobre_un_subtotal_bajo(): void
    {
        // Arrange: 2 unidades de un producto de $10.000 => subtotal $20.000
        // (por debajo del umbral de envío gratis).
        $carrito = Carrito::factory()->create();
        $producto = Producto::factory()->create(['precio' => 10000, 'stock' => 50]);
        $carrito->items()->create(['producto_id' => $producto->id, 'cantidad' => 2]);

        // Act: calcular el resumen.
        $resumen = $this->service->calcularResumen($carrito);

        // Assert: subtotal, IVA 21%, envío fijo y total.
        $this->assertSame(2, $resumen->cantidadItems);
        $this->assertSame(20000.0, $resumen->subtotal);
        $this->assertSame(4200.0, $resumen->impuestos);   // 21% de 20.000
        $this->assertSame(5000.0, $resumen->costoEnvio);  // subtotal < 100.000
        $this->assertSame(29200.0, $resumen->total);      // 20.000 + 4.200 + 5.000
    }

    public function test_el_envio_es_gratis_cuando_el_subtotal_supera_los_100000(): void
    {
        // Arrange: 1 producto de $150.000 => subtotal por encima del umbral.
        $carrito = Carrito::factory()->create();
        $producto = Producto::factory()->create(['precio' => 150000, 'stock' => 10]);
        $carrito->items()->create(['producto_id' => $producto->id, 'cantidad' => 1]);

        // Act: calcular el resumen.
        $resumen = $this->service->calcularResumen($carrito);

        // Assert: el envío es gratis.
        $this->assertSame(150000.0, $resumen->subtotal);
        $this->assertSame(31500.0, $resumen->impuestos); // 21% de 150.000
        $this->assertSame(0.0, $resumen->costoEnvio);    // envío gratis
        $this->assertSame(181500.0, $resumen->total);
    }

    public function test_suma_correctamente_varias_lineas_de_distintos_productos(): void
    {
        // Arrange: dos productos distintos, con cantidades distintas.
        $carrito = Carrito::factory()->create();
        $notebook = Producto::factory()->create(['precio' => 30000, 'stock' => 10]);
        $mouse = Producto::factory()->create(['precio' => 5000, 'stock' => 10]);
        $carrito->items()->create(['producto_id' => $notebook->id, 'cantidad' => 1]); // 30.000
        $carrito->items()->create(['producto_id' => $mouse->id, 'cantidad' => 2]);    // 10.000

        // Act: calcular el resumen.
        $resumen = $this->service->calcularResumen($carrito);

        // Assert: cuenta 3 unidades y suma $40.000 de subtotal.
        $this->assertSame(3, $resumen->cantidadItems);
        $this->assertSame(40000.0, $resumen->subtotal);
    }
}
