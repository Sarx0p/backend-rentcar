<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'dui',
        'vencimiento_dui',
        'numero_licencia',
        'vencimiento_licencia',
        'telefono',
        'municipio_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'vencimiento_dui'      => 'date',
        'vencimiento_licencia' => 'date',
    ];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'cliente_id');
    }
    
    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class, 'cliente_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }
    //se quito con redundancia
}
