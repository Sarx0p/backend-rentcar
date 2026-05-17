<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incidencia extends Model
{
    protected $table = 'incidencias';

    protected $fillable = [
        'contrato_id',
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

  
    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class, 'contrato_id');
    }
}
