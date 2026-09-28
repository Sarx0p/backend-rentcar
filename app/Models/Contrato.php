<?php

namespace App\Models;

use App\Enums\CargoAdicionalEstadoEnum;
use App\Enums\EstadoPagoEnum;
use App\Enums\EstadoTransaccionEnum;
use App\Enums\IncidenciaEstadoEnum;
use App\Enums\IncidenciaTipoResponsableEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'numero_contrato',
        'cliente_id',
        'vehiculo_id',
        'info_registro',
        'reserva_id',
        'fecha_hora_entrega',
        'fecha_hora_devolucion',
        'dias_acordados',
        'precio_por_dia',
        'monto_descuento',
        'monto_total_renta',
        'nivel_combustible_entrega',
        'observaciones_entrega',
        'estado_contrato',
        'estado_pago',
        'observaciones',
        'usuario_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'fecha_hora_entrega'    => 'datetime',
        'fecha_hora_devolucion' => 'datetime',
        'dias_acordados'        => 'integer',
        'precio_por_dia'        => 'decimal:2',
        'monto_descuento'       => 'decimal:2',
        'monto_total_renta'     => 'decimal:2',
        'info_registro'         => 'array',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'reserva_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'contrato_id');
    }

    public function cargosAdicionales(): HasMany
    {
        return $this->hasMany(CargoAdicional::class, 'contrato_id');
    }

    public function cierreRenta(): HasOne
    {
        return $this->hasOne(CierreRenta::class, 'contrato_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(Incidencia::class, 'contrato_id');
    }

    /**
     * Suma de pagos confirmados del contrato.
     */
    public function montoPagado(): float
    {
        return (float) $this->pagos()
            ->where('estado_transaccion', EstadoTransaccionEnum::CONFIRMADO->value)
            ->sum('monto');
    }

    /**
     * Recalcula y guarda monto_total_renta y estado_pago desde cero:
     * renta base + cargos vigentes + incidencias cobradas al cliente.
     */
    public function recalcularTotalYEstadoPago(): void
    {
        $base = ((float) $this->precio_por_dia * (int) $this->dias_acordados)
              - (float) $this->monto_descuento;
        $base = max(0, $base);

        $cargos = (float) $this->cargosAdicionales()
            ->where('estado_cargo', '!=', CargoAdicionalEstadoEnum::ANULADO->value)
            ->sum('monto');

        $incidencias = (float) $this->incidencias()
            ->where('responsable_tipo', IncidenciaTipoResponsableEnum::CLIENTE->value)
            ->where('estado_incidencia', '!=', IncidenciaEstadoEnum::ANULADA->value)
            ->sum('costo');

        $total  = $base + $cargos + $incidencias;
        $pagado = $this->montoPagado();

        $estado = $total <= 0
            ? EstadoPagoEnum::PAGADO->value
            : ($pagado >= $total
                ? EstadoPagoEnum::PAGADO->value
                : ($pagado > 0
                    ? EstadoPagoEnum::PARCIAL->value
                    : EstadoPagoEnum::PENDIENTE->value));

        $this->update([
            'monto_total_renta' => $total,
            'estado_pago'       => $estado,
        ]);
    }
}
