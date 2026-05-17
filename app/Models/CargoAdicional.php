<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargoAdicional extends Model
{
    protected $table = 'cargos_adicionales';

    protected $fillable = [
        'contrato_id',
        'tipo_cargo',
        'descripcion',
        'monto',
        'fecha_registro',
        'estado_cargo',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'monto'          => 'decimal:2',
        'fecha_registro' => 'datetime',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
