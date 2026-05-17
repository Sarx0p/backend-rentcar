<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancelacion extends Model
{
    protected $table = 'cancelaciones';

    protected $fillable = [
        'fecha_cancelacion',
        'motivo',
        'usuario_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha_cancelacion' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'cancelacion_id');
    }
}
