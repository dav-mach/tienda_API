<?php

namespace Tests\Unit;

use App\DTOs\DatosCheckoutDTO;
use App\DTOs\ResumenCompraDTO;
use App\Models\Carrito;
use App\Models\Producto;
use App\Models\User;
use App\Services\CarritoService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

/**
 * Prueba unitaria con Mockery (requisito 5: mocking).
 *
 * CheckoutService depende de CarritoService. Acá reemplazamos ese
 * CarritoService por un "mock": un objeto falso al que le decimos de
 * antemano qué devolver, sin ejecutar su lógica real. Así este test no
 * depende de que el cálculo del resumen esté bien (eso ya se prueba en
 * CarritoServiceTest).
 */
class CheckoutMockeryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_registrar_datos_usa_el_resumen_que_le_da_el_carrito_service_mockeado(): void
    {
        // Arrange: un carrito real con un item, y un mock de CarritoService
        // que devuelve un resumen fijo cuando le piden calcularResumen().
        Mail::fake();

        $user = User::factory()->create();
        $carrito = Carrito::factory()->create(['usuario_id' => $user->id]);
        $producto = Producto::factory()->create(['precio' => 10000, 'stock' => 10]);
        $carrito->items()->create(['producto_id' => $producto->id, 'cantidad' => 2]);

        $carritoServiceMock = Mockery::mock(CarritoService::class);
        $carritoServiceMock->shouldReceive('calcularResumen')
            ->once()
            ->andReturn(new ResumenCompraDTO(
                cantidadItems: 2,
                subtotal: 20000.0,
                impuestos: 4200.0,
                costoEnvio: 5000.0,
                total: 29200.0,
            ));

        $checkout = new CheckoutService($carritoServiceMock);

        $datos = new DatosCheckoutDTO(
            nombreCliente: 'Ana Pérez',
            email: 'ana@mail.com',
            direccionEnvio: 'Calle Falsa 123',
            ciudad: 'General Pico',
            codigoPostal: '6360',
            metodoPago: 'tarjeta',
        );

        // Act: registrar los datos del checkout.
        $pedido = $checkout->registrarDatos($carrito, $datos);

        // Assert: el pedido tomó los totales que devolvió el mock.
        $this->assertSame(29200.0, (float) $pedido->total);
        $this->assertSame(4200.0, (float) $pedido->impuestos);
    }
}
