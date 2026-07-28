<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    protected $table = 'municipios';

    protected $fillable = [
        'nombre',
        'departamento_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class, 'municipio_id');
    }
}
