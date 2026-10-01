<?php

namespace App\Models;

use App\Enums\EstadoReservaEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reserva extends Model
{
    protected $table = 'reservas';

    protected $fillable = [
        'fecha_solicitud',
        'fecha_inicio',
        'fecha_fin',
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

    // pendientes que ya pasaron su dia de inicio sin contrato, ya no se pueden usar
    public static function vencerPendientes(int $usuarioId): int
    {
        $vencidas = self::where('estado', EstadoReservaEnum::PENDIENTE->value)
            ->whereDate('fecha_inicio', '<', now()->toDateString())
            ->whereDoesntHave('contrato')
            ->whereDoesntHave('cancelacion')
            ->get();

        foreach ($vencidas as $reserva) {
            DB::transaction(function () use ($reserva, $usuarioId) {
                Cancelacion::create([
                    'fecha_cancelacion' => now(),
                    'motivo'            => 'Vencida: el cliente no se presento el dia de inicio',
                    'usuario_id'        => $usuarioId,
                    'reserva_id'        => $reserva->id,
                ]);

                $reserva->update(['estado' => EstadoReservaEnum::CANCELADA->value]);
            });
        }

        return $vencidas->count();
    }
}
