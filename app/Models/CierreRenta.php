<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreRenta extends Model
{
    protected $table = 'cierres_renta';

    protected $fillable = [
        'fecha_hora_recepcion',
        'nivel_combustible_recepcion',
        'estado_vehiculo_recepcion',
        'observaciones',
        'horas_retraso',
        'monto_extras',
        'estado',
        'contrato_id',
        'usuario_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha_hora_recepcion' => 'datetime',
        'horas_retraso'        => 'integer',
        'monto_extras'         => 'decimal:2',
    ];


    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
