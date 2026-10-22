<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Resultado Neto por Propietario</title>
    @include('contratos.CSS.CSS-de-todos-los-repostes')
</head>
<body>

    <div class="header">
        <div class="header-logo">
            <strong>El Guayabo</strong><br>
            <small>RENTA CAR</small>
        </div>
        <div class="header-title">
            <h1>RENTACARS "EL GUAYABO"</h1>
            <h2>TRANSPORTE Y RENTA DE VEHÍCULOS</h2>
            <p>Cel.: 6006-8390</p>
        </div>
    </div>

    <div class="reporte-titulo">Reporte de Resultado Neto por Propietario</div>
    <div class="reporte-rango">Del {{ $fechaInicio ?? 'N/A' }} al {{ $fechaFin ?? 'N/A' }}</div>

    <div class="cards-container">
        <div class="card">
            <div class="card-label">Total de ingresos</div>
            <div class="card-value">${{ number_format($totalIngresos ?? 0, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Total de gastos</div>
            <div class="card-value">${{ number_format($totalGastos ?? 0, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Diferencia por daños</div>
            <div class="card-value">${{ number_format($totalDiferenciaDanios ?? 0, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Administración ({{ number_format($porcentajeAdmin ?? 0, 2) }}%)</div>
            <div class="card-value">${{ number_format($totalAdministracion ?? 0, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Resultado neto</div>
            <div class="card-value">${{ number_format($totalNeto ?? 0, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Propietarios</div>
            <div class="card-value">{{ is_array($propietarios) ? count($propietarios) : ($propietarios->count() ?? 0) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Propietario</th>
                <th>Vehículos</th>
                <th class="right">Ingresos</th>
                <th class="right">Devuelto</th>
                <th class="right">Gasto Incidencias</th>
                <th class="right">Gasto Mantenimientos</th>
                <th class="right">Gasto Total</th>
                <th class="right">Diferencia por daños</th>
                <th class="right">Administración ({{ number_format($porcentajeAdmin ?? 0, 2) }}%)</th>
                <th class="right">Resultado Neto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($propietarios as $item)
                <tr>
                    <td>{{ $item['propietario']->nombre ?? 'N/A' }}</td>
                    <td>{{ $item['num_vehiculos'] }}</td>
                    <td class="right">${{ number_format($item['ingresos'], 2) }}</td>
                    <td class="right">${{ number_format($item['devoluciones'], 2) }}</td>
                    <td class="right">${{ number_format($item['gasto_incidencias'], 2) }}</td>
                    <td class="right">${{ number_format($item['gasto_mantenimientos'], 2) }}</td>
                    <td class="right">${{ number_format($item['gasto_total'], 2) }}</td>
                    <td class="right" style="{{ $item['diferencia_danios'] < 0 ? 'color:#b00000;' : '' }}">${{ number_format($item['diferencia_danios'], 2) }}</td>
                    <td class="right">
                        @if($item['aplica_administracion'])
                            ${{ number_format($item['monto_administracion'], 2) }}
                        @else
                            No aplica
                        @endif
                    </td>
                    <td class="right">${{ number_format($item['resultado_neto'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No hay propietarios registrados.</td>
                </tr>
            @endforelse

            <tr class="total-destacado">
                <td colspan="2">Total general</td>
                <td class="right">${{ number_format($totalIngresos ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalDevoluciones ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalIncidencias ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalMantenimientos ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalGastos ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalDiferenciaDanios ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalAdministracion ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalNeto ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="nota-final">La diferencia por daños es lo que se le cobró al cliente por daños menos lo que costó repararlos. Ya está incluida en los ingresos y los gastos; se muestra aparte para ver cuánto ganó o perdió el propietario por esos daños.</div>

    @foreach($propietarios as $item)
        @if(isset($item['incidencias_detalle']) && $item['incidencias_detalle']->isNotEmpty())
            <div class="seccion-titulo">
                Incidencias del negocio — {{ $item['propietario']->nombre ?? 'N/A' }}
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th class="right">Costo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item['incidencias_detalle'] as $inc)
                        <tr>
                            <td>{{ $inc->vehiculo_id }}</td>
                            <td>{{ $inc->tipo_incidencia }}</td>
                            <td>{{ $inc->descripcion }}</td>
                            <td>{{ \Carbon\Carbon::parse($inc->fecha)->format('d/m/Y') }}</td>
                            <td class="right">${{ number_format($inc->costo, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(isset($item['danios_detalle']) && $item['danios_detalle']->isNotEmpty())
            <div class="seccion-titulo">
                Daños cobrados al cliente — {{ $item['propietario']->nombre ?? 'N/A' }}
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Fecha</th>
                        <th>Descripción</th>
                        <th class="right">Cobrado al cliente</th>
                        <th class="right">Costo real</th>
                        <th class="right">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item['danios_detalle'] as $danio)
                        <tr>
                            <td>{{ $danio['placa'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($danio['fecha'])->format('d/m/Y') }}</td>
                            <td>{{ $danio['descripcion'] }}</td>
                            <td class="right">${{ number_format($danio['cobrado'], 2) }}</td>
                            @if($danio['pendiente'])
                                <td class="right" colspan="2">Pendiente de reparación</td>
                            @else
                                <td class="right">${{ number_format($danio['costo'], 2) }}</td>
                                <td class="right" style="{{ $danio['diferencia'] < 0 ? 'color:#b00000;' : '' }}">${{ number_format($danio['diferencia'], 2) }}</td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="total-destacado">
                        <td colspan="5">Diferencia por daños</td>
                        <td class="right">${{ number_format($item['diferencia_danios'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        @if(isset($item['mantenimientos_detalle']) && $item['mantenimientos_detalle']->isNotEmpty())
            <div class="seccion-titulo">
                Mantenimientos — {{ $item['propietario']->nombre ?? 'N/A' }}
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Lugar</th>
                        <th>Fecha</th>
                        <th class="right">Costo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item['mantenimientos_detalle'] as $mant)
                        <tr>
                            <td>{{ $mant->vehiculo_id }}</td>
                            <td>{{ $mant->tipo_mantenimiento }}</td>
                            <td>{{ $mant->descripcion }}</td>
                            <td>{{ $mant->lugar }}</td>
                            <td>{{ \Carbon\Carbon::parse($mant->fecha)->format('d/m/Y') }}</td>
                            <td class="right">${{ number_format($mant->costo, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
