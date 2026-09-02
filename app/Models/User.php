<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;   // <- nuevo import, hace falta para el tipo HasMany
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rol'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function carritos(): HasMany           // <- método nuevo
    {
        return $this->hasMany(Carrito::class);
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    /**
     * Qué se guarda como "sub" (subject) dentro del JWT: el id del usuario.
     * Es lo mínimo que necesita el token para saber a quién representa.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Claims extra a incluir en el payload del JWT. Lo dejamos vacío a
     * propósito: NO metemos datos sensibles (email, rol, etc.) en el
     * token, porque el payload de un JWT viaja codificado pero NO
     * cifrado — cualquiera que lo intercepte puede leerlo.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
}

