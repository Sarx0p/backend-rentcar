<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'api';

    protected $fillable = [
        'nombre',
        'apellido',
        'correo',
        'password',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function historialesCliente(): HasMany
    {
        return $this->hasMany(HistorialCliente::class, 'usuario_id');
    }

    public function cancelaciones(): HasMany
    {
        return $this->hasMany(Cancelacion::class, 'usuario_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'usuario_id');
    }
}