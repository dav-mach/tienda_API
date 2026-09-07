<?php

namespace Database\Factories;

use App\Models\Carrito;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carrito>
 */
class CarritoFactory extends Factory
{
    protected $model = Carrito::class;

    public function definition(): array
    {
        return [
            // Si no se pasa un usuario, se crea uno automáticamente.
            'usuario_id' => User::factory(),
        ];
    }
}
