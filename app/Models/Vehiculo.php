<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    protected $fillable = [
        'anio',
        'color',
        'placa',
        'capacidad_pasajeros',
        'estado',
        'observaciones',
        'propietario_id',
        'categoria_id',
        'modelo_id',
        'seguro_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'anio'                => 'integer',
        'capacidad_pasajeros' => 'integer',
    ];

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'propietario_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class, 'modelo_id');
    }

    public function seguros(): HasMany
    {
        return $this->hasMany(Seguro::class, 'vehiculo_id');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'vehiculo_id');
    }

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'vehiculo_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'vehiculo_id');
    }
}
