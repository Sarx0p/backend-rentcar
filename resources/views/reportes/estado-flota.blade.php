<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Estado de la Flota</title>
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

    <div class="reporte-titulo">Reporte de Estado de la Flota</div>
    <div class="reporte-rango">Fecha: {{ now()->format('d/m/Y') }}</div>

    <div class="cards-container">
        <div class="card">
            <div class="card-label">Total de Vehículos</div>
            <div class="card-value">{{ $totalVehiculos }}</div>
        </div>
    </div>

    <div class="seccion-titulo">Resumen por estado</div>
    <table>
        <thead>
            <tr>
                <th>Estado</th>
                <th class="right">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estados as $estado)
            <tr>
                <td>{{ $estado->estado }}</td>
                <td class="right">{{ $estado->total }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="seccion-titulo">Detalle de vehículos</div>
    <table>
        <thead>
            <tr>
                <th>Placa</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Color</th>
                <th>Año</th>
                <th>Categoría</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vehiculos as $vehiculo)
            <tr>
                <td>{{ $vehiculo->placa }}</td>
                <td>{{ $vehiculo->modelo->marca->nombre }}</td>
                <td>{{ $vehiculo->modelo->nombre }}</td>
                <td>{{ $vehiculo->color }}</td>
                <td>{{ $vehiculo->anio }}</td>
                <td>{{ $vehiculo->categoria->nombre }}</td>
                <td>{{ $vehiculo->estado }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
