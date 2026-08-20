<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Licencias por Vencer</title>
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

    <div class="reporte-titulo">Clientes con Licencia Próxima a Vencer</div>
    <div class="reporte-rango">
        Próximos {{ $dias }} días
        <br>Total de clientes: <strong>{{ count($clientes) }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>DUI</th>
                <th>Licencia</th>
                <th>Vencimiento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientes as $cliente)
            <tr>
                <td>{{ $cliente->nombre }}</td>
                <td>{{ $cliente->dui }}</td>
                <td>{{ $cliente->numero_licencia }}</td>
                <td>{{ $cliente->vencimiento_licencia->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
