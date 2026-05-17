<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seguro extends Model
{
    protected $table = 'seguros';

    protected $fillable = [
        'aseguradora',
        'numero_poliza',
        'fecha_inicio',
        'fecha_vencimiento',
        'cobertura',
        'estado',
        'vehiculo_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha_inicio'      => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }
}
