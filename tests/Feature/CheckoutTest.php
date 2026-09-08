<?php

namespace Tests\Feature;

use App\Mail\PedidoConfirmadoMail;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Pruebas de integración del Checkout (requisitos 3 y 5).
 * El flujo completo (agregar -> registrar datos -> confirmar) y el
 * mocking del email de confirmación con Mail::fake(), para no depender
 * de un servidor de correo real.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Deja un usuario con un producto ya en su carrito, listo para el
     * checkout. Devuelve [usuario, header, producto].
     */
    private function prepararCarrito(): array
    {
        $user = User::factory()->create();
        $header = $this->authHeader($user);
        $producto = Producto::factory()->create(['precio' => 10000, 'stock' => 10]);

        $this->postJson('/api/v1/carrito/items', [
            'producto_id' => $producto->id,
            'cantidad' => 2,
        ], $header);

        return [$user, $header, $producto];
    }

    private function datosEnvio(): array
    {
        return [
            'nombre_cliente' => 'Ana Pérez',
            'email' => 'ana@mail.com',
            'direccion_envio' => 'Calle Falsa 123',
            'ciudad' => 'General Pico',
            'codigo_postal' => '6360',
            'metodo_pago' => 'tarjeta',
        ];
    }

    public function test_el_flujo_de_checkout_completo_confirma_la_compra_y_envia_el_email(): void
    {
        // Arrange: interceptar emails, y un carrito listo para comprar.
        Mail::fake();
        [$user, $header, $producto] = $this->prepararCarrito();

        // Act (paso 2): registrar los datos de envío/pago -> crea el pedido.
        $registrar = $this->postJson('/api/v1/checkout', $this->datosEnvio(), $header);
        $registrar->assertStatus(201);
        $pedidoId = $registrar->json('data.id');

        // Assert intermedio: todavía no se mandó ningún email.
        Mail::assertNothingSent();

        // Act (paso 3): confirmar la compra.
        $confirmar = $this->postJson("/api/v1/checkout/{$pedidoId}/confirmar", [], $header);

        // Assert: pedido confirmado, stock descontado (10 - 2 = 8) y el
        // email de confirmación "se envió" (mock).
        $confirmar->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseHas('pedidos', ['id' => $pedidoId, 'estado' => 'confirmado']);
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 8]);
        Mail::assertSent(PedidoConfirmadoMail::class, function ($mail) {
            return $mail->hasTo('ana@mail.com');
        });
    }

    public function test_no_se_puede_hacer_checkout_con_el_carrito_vacio(): void
    {
        // Arrange: un usuario autenticado, con el carrito vacío.
        Mail::fake();
        $user = User::factory()->create();

        // Act: intentar registrar el checkout sin productos.
        $response = $this->postJson('/api/v1/checkout', $this->datosEnvio(), $this->authHeader($user));

        // Assert: error de carrito vacío y ningún email enviado.
        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        Mail::assertNothingSent();
    }

    public function test_confirmar_un_pedido_dos_veces_devuelve_409(): void
    {
        // Arrange: un pedido ya registrado.
        Mail::fake();
        [$user, $header] = $this->prepararCarrito();
        $pedidoId = $this->postJson('/api/v1/checkout', $this->datosEnvio(), $header)->json('data.id');

        // Act: confirmar dos veces el mismo pedido.
        $primera = $this->postJson("/api/v1/checkout/{$pedidoId}/confirmar", [], $header);
        $segunda = $this->postJson("/api/v1/checkout/{$pedidoId}/confirmar", [], $header);

        // Assert: la primera OK, la segunda da conflicto.
        $primera->assertStatus(200);
        $segunda->assertStatus(409);
    }
}
