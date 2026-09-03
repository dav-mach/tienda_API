<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entrega 4: el carrito pertenece siempre a un usuario autenticado.
     * Ya no se identifica con un token anónimo, sino por su usuario_id
     * (que sale del JWT en cada petición). Por eso usuario_id es
     * obligatorio y no hay columna "token".
     */
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carritos');
    }
};
