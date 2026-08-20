<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gastos por Vehículo</title>
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

    <div class="reporte-titulo">Gastos por Vehículo</div>
    <div class="reporte-rango">Del {{ $fechaInicio }} al {{ $fechaFin }}</div>

    <table>
        <thead>
            <tr>
                <th>Placa</th>
                <th>Marca / Modelo</th>
                <th class="right">Mantenimiento</th>
                <th class="right">Incidencias (negocio)</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehiculos as $item)
            <tr>
                <td>{{ $item['vehiculo']->placa }}</td>
                <td>{{ $item['vehiculo']->modelo->marca->nombre ?? '' }} {{ $item['vehiculo']->modelo->nombre ?? '' }}</td>
                <td class="right">${{ number_format($item['gasto_mantenimiento'], 2) }}</td>
                <td class="right">${{ number_format($item['gasto_incidencias_negocio'], 2) }}</td>
                <td class="right">${{ number_format($item['gasto_total'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-destacado">
                <td colspan="4">Total general</td>
                <td class="right">${{ number_format($totalGeneral, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
