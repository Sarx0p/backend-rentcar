<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    protected $table = 'departamentos';

    protected $fillable = [
        'nombre',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class, 'departamento_id');
    }
}
