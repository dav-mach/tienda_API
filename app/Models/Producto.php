<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'precio',
        'stock',
        'categoria_id',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function carritoItems(): HasMany
    {
        return $this->hasMany(CarritoItem::class);
    }

    /**
     * Consulta el estado: ¿hay stock suficiente para la cantidad pedida?
     */
    public function hayStockDisponible(int $cantidadSolicitada): bool
    {
        return $cantidadSolicitada > 0 && $cantidadSolicitada <= $this->stock;
    }
}
