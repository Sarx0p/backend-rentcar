<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Propietario extends Model
{
    protected $table = 'propietarios';

    protected $fillable = [
        'nombre',
        'telefono',
        'tipo_propietario',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'propietario_id');
    }
}
