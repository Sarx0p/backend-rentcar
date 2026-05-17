<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCliente extends Model
{
    protected $table = 'historial_clientes';

    protected $fillable = [
        'usuario_id', 
        'tipo_registro',
        'descripcion',
        'monto_pendiente',
        'fecha_registro',
        'estado',
        'cliente_id',
        'contrato_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'monto_pendiente' => 'decimal:2',
        'fecha_registro'  => 'datetime',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
