<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'precio_dia',
        'capacidad_minima',
        'capacidad_maxima',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'precio_dia'       => 'decimal:2',
        'capacidad_minima' => 'integer',
        'capacidad_maxima' => 'integer',
    ];

    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'categoria_id');
    }
}
