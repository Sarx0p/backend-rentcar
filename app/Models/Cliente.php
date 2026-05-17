<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'dui',
        'nacimiento_dui',
        'numero_licencia',
        'vencimiento_licencia',
        'telefono',
        'departamento',
        'municipio',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'nacimiento_dui'       => 'date',
        'vencimiento_licencia' => 'date',
    ];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'cliente_id');
    }
    public function historiales(): HasMany
    {
        return $this->hasMany(HistorialCliente::class, 'cliente_id');
    }
}
