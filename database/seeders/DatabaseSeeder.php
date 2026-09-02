<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga datos de ejemplo. Incluye un usuario con contraseña conocida
     * para poder probar el login en Postman apenas se levanta el proyecto.
     * Los carritos y pedidos NO se siembran: se crean solos al usar la API.
     */
    public function run(): void
    {
        // Usuario de prueba: login con ana.perez@mail.com / password123
        User::create([
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@mail.com',
            'password' => 'password123', // se hashea sola con bcrypt (cast 'hashed' del modelo)
            'rol' => 'admin',
        ]);

        $tecnologia = Categoria::create([
            'nombre' => 'Tecnología',
            'descripcion' => 'Productos electrónicos y de cómputo',
        ]);

        $accesorios = Categoria::create([
            'nombre' => 'Accesorios',
            'descripcion' => 'Complementos y periféricos',
        ]);

        Producto::create([
            'nombre' => 'Notebook',
            'precio' => 850000.00,
            'stock' => 8,
            'categoria_id' => $tecnologia->id,
        ]);

        Producto::create([
            'nombre' => 'Mouse inalámbrico',
            'precio' => 15000.00,
            'stock' => 70,
            'categoria_id' => $accesorios->id,
        ]);

        Producto::create([
            'nombre' => 'Teclado mecánico',
            'precio' => 32000.00,
            'stock' => 15,
            'categoria_id' => $accesorios->id,
        ]);

        Producto::create([
            'nombre' => 'Monitor 24"',
            'precio' => 210000.00,
            'stock' => 5,
            'categoria_id' => $tecnologia->id,
        ]);
    }
}
