<?php

namespace App\Models;

use App\Enums\SeguroEstadoEnum;
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

    public static function vencerVigentes(): int
    {
        return self::where('estado', SeguroEstadoEnum::VIGENTE->value)
            ->whereDate('fecha_vencimiento', '<=', now()->toDateString())
            ->update(['estado' => SeguroEstadoEnum::VENCIDO->value]);
    }

    public static function faltaCobertura(int $vehiculoId, string $inicio, string $fin): bool
    {
        self::vencerVigentes();

        $tieneSeguros = self::where('vehiculo_id', $vehiculoId)
            ->where('estado', '!=', SeguroEstadoEnum::CANCELADO->value)
            ->exists();

        if (!$tieneSeguros) {
            return false;
        }

        return !self::where('vehiculo_id', $vehiculoId)
            ->where('estado', SeguroEstadoEnum::VIGENTE->value)
            ->whereDate('fecha_inicio', '<=', $inicio)
            ->whereDate('fecha_vencimiento', '>', $fin)
            ->exists();
    }
}
