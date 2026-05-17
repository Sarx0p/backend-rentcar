<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'numero_contrato',
        'fecha_hora_entrega',
        'fecha_hora_devolucion',
        'dias_acordados',
        'precio_por_dia',
        'monto_descuento',
        'monto_total_renta',
        'nivel_combustible_entrega',
        'observaciones_entrega',
        'estado_contrato',
        'estado_pago',
        'observaciones',
        'reserva_id',
        'usuario_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha_hora_entrega'    => 'datetime',
        'fecha_hora_devolucion' => 'datetime',
        'dias_acordados'        => 'integer',
        'precio_por_dia'        => 'decimal:2',
        'monto_descuento'       => 'decimal:2',
        'monto_total_renta'     => 'decimal:2',
    ];

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'contrato_id');
    }

    public function cargosAdicionales(): HasMany
    {
        return $this->hasMany(CargoAdicional::class, 'contrato_id');
    }

    public function cierreRenta(): HasOne
    {
        return $this->hasOne(CierreRenta::class, 'contrato_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class, 'contrato_id');
    }

    public function historiales(): HasMany
    {
        return $this->hasMany(HistorialCliente::class, 'contrato_id');
    }
}
