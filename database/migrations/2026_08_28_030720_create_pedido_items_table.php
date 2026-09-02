<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada línea de un pedido "congela" (snapshot) el nombre y precio
     * del producto en el momento de la compra: si el producto cambia
     * de precio después, el historial de pedidos no se ve afectado.
     */
    public function up(): void
    {
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->cascadeOnDelete();
            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->nullOnDelete();

            $table->string('nombre_producto');
            $table->decimal('precio_unitario', 12, 2);
            $table->unsignedInteger('cantidad');
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
