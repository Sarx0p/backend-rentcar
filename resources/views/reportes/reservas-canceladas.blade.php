<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Reservas Canceladas</title>
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

    <div class="reporte-titulo">Reporte de Reservas Canceladas</div>
    <div class="reporte-rango">
        Total de cancelaciones: <strong>{{ count($cancelaciones) }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th>Motivo</th>
                <th>Cancelado por</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cancelaciones as $c)
            <tr>
                <td>{{ $c->fecha_cancelacion->format('d/m/Y') }}</td>
                <td>{{ optional($c->reserva->cliente)->nombre }}</td>
                <td>{{ optional($c->reserva->vehiculo)->placa }}</td>
                <td>{{ $c->motivo }}</td>
                <td>{{ optional($c->user)->nombre }} {{ optional($c->user)->apellido }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
