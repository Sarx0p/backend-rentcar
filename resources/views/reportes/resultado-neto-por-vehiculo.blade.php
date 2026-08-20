<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado Neto por Vehículo</title>
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

    <div class="reporte-titulo">Resultado Neto por Vehículo</div>
    <div class="reporte-rango">Del {{ $fechaInicio }} al {{ $fechaFin }}</div>

    <table>
        <thead>
            <tr>
                <th>Placa</th>
                <th>Marca / Modelo</th>
                <th>Propietario</th>
                <th class="right">Ingresos</th>
                <th class="right">Gastos</th>
                <th class="right">Resultado Neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehiculos as $item)
            <tr>
                <td>{{ $item['vehiculo']->placa }}</td>
                <td>
                    {{ optional($item['vehiculo']->modelo->marca)->nombre }}
                    {{ optional($item['vehiculo']->modelo)->nombre }}
                </td>
                <td>
                    {{ optional($item['vehiculo']->propietario)->nombre }}
                    {{ optional($item['vehiculo']->propietario)->apellido }}
                </td>
                <td class="right">${{ number_format($item['ingresos'], 2) }}</td>
                <td class="right">${{ number_format($item['gastos'], 2) }}</td>
                <td class="right" style="{{ $item['resultado_neto'] < 0 ? 'color:#b00000;' : '' }}">
                    ${{ number_format($item['resultado_neto'], 2) }}
                </td>
            </tr>
            @endforeach

            <tr class="total-destacado">
                <td colspan="3">Totales</td>
                <td class="right">${{ number_format($totalIngresos, 2) }}</td>
                <td class="right">${{ number_format($totalGastos, 2) }}</td>
                <td class="right" style="{{ $totalNeto < 0 ? 'color:#b00000;' : '' }}">
                    ${{ number_format($totalNeto, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
