<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'fecha_solicitud',
        'fecha_inicio',
        'fecha_fin',
        'tipo_reserva',
        'estado',
        'cliente_id',
        'vehiculo_id',
        'usuario_id',
       
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_inicio'    => 'date',
        'fecha_fin'       => 'date',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cancelacion(): HasOne
    {
        return $this->hasOne(Cancelacion::class, 'reserva_id');
    }

    public function contrato(): HasOne
    {
        return $this->hasOne(Contrato::class, 'reserva_id');
    }
}
