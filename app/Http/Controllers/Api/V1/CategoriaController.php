<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;

/**
 * CRUD completo de Categoria (requisito 2), expuesto en routes/api.php
 * con Route::apiResource.
 */
class CategoriaController extends Controller
{
    /** Lista todas las categorías, con la cantidad de productos de cada una. */
    public function index(): JsonResponse
    {
        $categorias = Categoria::withCount('productos')->orderBy('nombre')->get();

        return $this->exito(CategoriaResource::collection($categorias));
    }

    /** Muestra el detalle de una categoría puntual. */
    public function show(Categoria $categoria): JsonResponse
    {
        return $this->exito(new CategoriaResource($categoria->loadCount('productos')));
    }

    /** Crea una categoría nueva (datos ya validados por StoreCategoriaRequest). */
    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $categoria = Categoria::create($request->validated());

        return $this->exito(new CategoriaResource($categoria), 'Categoría creada.', 201);
    }

    /** Actualiza una categoría existente. */
    public function update(UpdateCategoriaRequest $request, Categoria $categoria): JsonResponse
    {
        $categoria->update($request->validated());

        return $this->exito(new CategoriaResource($categoria), 'Categoría actualizada.');
    }

    /** Elimina una categoría, salvo que todavía tenga productos asociados. */
    public function destroy(Categoria $categoria): JsonResponse
    {
        if ($categoria->productos()->exists()) {
            return $this->error('No se puede eliminar: la categoría tiene productos asociados.', [], 409);
        }

        $categoria->delete();

        return $this->exitoDatos(null, 'Categoría eliminada.');
    }
}
