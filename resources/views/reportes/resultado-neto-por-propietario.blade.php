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
            <small>RENT CAR</small>
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
                <th class="right">Gasto Incidencias</th>
                <th class="right">Gasto Total</th>
                <th class="right">Resultado Neto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($propietarios as $item)
                <tr>
                    <td>{{ $item['propietario']->nombre ?? 'N/A' }}</td>
                    <td>{{ $item['num_vehiculos'] }}</td>
                    <td class="right">${{ number_format($item['ingresos'], 2) }}</td>
                    <td class="right">${{ number_format($item['gasto_incidencias'], 2) }}</td>
                    <td class="right">${{ number_format($item['gasto_total'], 2) }}</td>
                    <td class="right">${{ number_format($item['resultado_neto'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay propietarios registrados.</td>
                </tr>
            @endforelse

            <tr class="total-destacado">
                <td colspan="2">Total general</td>
                <td class="right">${{ number_format($totalIngresos ?? 0, 2) }}</td>
                <td></td>
                <td class="right">${{ number_format($totalGastos ?? 0, 2) }}</td>
                <td class="right">${{ number_format($totalNeto ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

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
    @endforeach

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
