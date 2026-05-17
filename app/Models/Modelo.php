<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modelo extends Model
{

    protected $table = 'modelos';

    protected $fillable = [
        'nombre',
        'marca_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];


    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }
    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class, 'modelo_id');
    }
}
