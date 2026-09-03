<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pedido: la "foto" de un carrito al momento del checkout.
 * Arranca en estado pendiente_confirmacion y pasa a confirmado
 * cuando App\Services\CheckoutService valida y descuenta el stock.
 */
class Pedido extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE = 'pendiente_confirmacion';
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_CANCELADO = 'cancelado';

    protected $fillable = [
        'carrito_id',
        'usuario_id',
        'nombre_cliente',
        'email',
        'direccion_envio',
        'ciudad',
        'codigo_postal',
        'metodo_pago',
        'subtotal',
        'impuestos',
        'costo_envio',
        'total',
        'estado',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'impuestos' => 'decimal:2',
        'costo_envio' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function estaConfirmado(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADO;
    }
}
