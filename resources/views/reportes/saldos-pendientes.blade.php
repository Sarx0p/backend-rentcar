<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Saldos Pendientes</title>
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

    <div class="reporte-titulo">Saldos Pendientes</div>

    <table>
        <thead>
            <tr>
                <th>N° Contrato</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th class="right">Monto Total</th>
                <th class="right">Pagado</th>
                <th class="right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contratos as $item)
            <tr>
                <td>{{ $item['contrato']->numero_contrato }}</td>
                <td>{{ optional($item['contrato']->cliente)->nombre }}</td>
                <td>{{ optional($item['contrato']->vehiculo)->placa }}</td>
                <td class="right">${{ number_format($item['contrato']->monto_total_renta, 2) }}</td>
                <td class="right">${{ number_format($item['total_pagado'], 2) }}</td>
                <td class="right">${{ number_format($item['saldo'], 2) }}</td>
            </tr>
            @endforeach

            <tr class="total-destacado">
                <td colspan="5">Total saldos pendientes</td>
                <td class="right">${{ number_format($totalSaldos, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
