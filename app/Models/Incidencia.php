<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Incidencia extends Model
{
    protected $table = 'incidencias';

    protected $fillable = [
        'vehiculo_id',
        'contrato_id',
        'usuario_id',
        'tipo_incidencia',
        'descripcion',
        'costo',
        'fecha',
        'responsable_tipo',
        'estado_incidencia',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'costo' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cargoAdicional(): HasOne
    {
        return $this->hasOne(CargoAdicional::class, 'incidencia_id');
    }
}
