<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->words(2, true),
            'precio' => fake()->randomFloat(2, 1000, 500000),
            'stock' => fake()->numberBetween(1, 100),
            // Si no se pasa una categoría, se crea una automáticamente.
            'categoria_id' => Categoria::factory(),
        ];
    }

    /**
     * Estado "sin stock": útil para probar las validaciones de inventario.
     */
    public function sinStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
