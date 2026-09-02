<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD completo de Producto (requisito 2), expuesto en routes/api.php
 * con Route::apiResource.
 */
class ProductoController extends Controller
{
    /** Lista productos, con filtro opcional por categoria_id y paginación. */
    public function index(Request $request): JsonResponse
    {
        $productos = Producto::with('categoria')
            ->when(
                $request->filled('categoria_id'),
                fn ($query) => $query->where('categoria_id', $request->integer('categoria_id'))
            )
            ->latest()
            ->paginate($request->integer('por_pagina', 10));

        return $this->exito(ProductoResource::collection($productos));
    }

    /** Muestra el detalle de un producto puntual. */
    public function show(Producto $producto): JsonResponse
    {
        return $this->exito(new ProductoResource($producto->load('categoria')));
    }

    /** Crea un producto nuevo (datos ya validados por StoreProductoRequest). */
    public function store(StoreProductoRequest $request): JsonResponse
    {
        $producto = Producto::create($request->validated());

        return $this->exito(new ProductoResource($producto->load('categoria')), 'Producto creado.', 201);
    }

    /** Actualiza un producto existente (datos ya validados por UpdateProductoRequest). */
    public function update(UpdateProductoRequest $request, Producto $producto): JsonResponse
    {
        $producto->update($request->validated());

        return $this->exito(new ProductoResource($producto->load('categoria')), 'Producto actualizado.');
    }

    /** Elimina un producto. */
    public function destroy(Producto $producto): JsonResponse
    {
        $producto->delete();

        return $this->exitoDatos(null, 'Producto eliminado.');
    }
}
