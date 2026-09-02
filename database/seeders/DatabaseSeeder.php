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
     * Carga datos de ejemplo para poder probar el CRUD y la API apenas
     * se levanta el proyecto. Los carritos y pedidos NO se siembran acá:
     * se crean solos al usar la API (ver IdentificarCarrito).
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Ana Pérez',
            'email' => 'ana.perez@mail.com',
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
