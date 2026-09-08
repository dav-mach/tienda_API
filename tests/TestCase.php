<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Devuelve el header Authorization con un JWT válido para el usuario
     * dado. Lo usan los Feature tests para pegarle a rutas protegidas.
     *
     * @return array<string, string>
     */
    protected function authHeader(User $user): array
    {
        $token = auth('api')->login($user);

        return ['Authorization' => "Bearer {$token}"];
    }
}
