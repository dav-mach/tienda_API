<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un Pedido es la "foto" de un carrito en el momento del checkout:
     * datos de envío/pago + los totales ya calculados. Arranca en
     * estado 'pendiente_confirmacion' y pasa a 'confirmado' recién
     * cuando se valida y descuenta el stock definitivamente.
     *
     * Entrega 4: el pedido guarda el carrito_id y el usuario_id (ya no un
     * token anónimo), porque ahora toda compra la hace un usuario logueado.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')
                ->nullable()
                ->constrained('carritos')
                ->nullOnDelete();
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('nombre_cliente');
            $table->string('email');
            $table->string('direccion_envio');
            $table->string('ciudad');
            $table->string('codigo_postal');
            $table->string('metodo_pago'); // tarjeta | efectivo | transferencia

            $table->decimal('subtotal', 12, 2);
            $table->decimal('impuestos', 12, 2);
            $table->decimal('costo_envio', 12, 2);
            $table->decimal('total', 12, 2);

            $table->string('estado')->default('pendiente_confirmacion');
            // pendiente_confirmacion | confirmado | cancelado

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
