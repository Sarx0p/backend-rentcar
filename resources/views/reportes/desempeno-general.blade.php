<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Desempeño General</title>
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

    <div class="reporte-titulo">Reporte de Desempeño General</div>
    <div class="reporte-rango">Del {{ $fechaInicio ?? 'N/A' }} al {{ $fechaFin ?? 'N/A' }}</div>

    <div class="cards-container">
        <div class="card">
            <div class="card-label">Total de ingresos</div>
            <div class="card-value">${{ number_format($totalIngresos ?? 0, 2) }}</div>
        </div>
        <div class="card">
            <div class="card-label">Contratos generados</div>
            <div class="card-value">{{ $totalContratos ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="card-label">Tasa de ocupación</div>
            <div class="card-value">{{ $tasaOcupacion ?? 0 }}%</div>
        </div>
        <div class="card">
            <div class="card-label">Vehículos rentados</div>
            <div class="card-value">{{ $vehiculosRentados ?? 0 }}</div>
        </div>
    </div>

    <table class="datos">
        <thead>
            <tr>
                <th>Indicador</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total de ingresos</td>
                <td>${{ number_format($totalIngresos ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Total de contratos generados</td>
                <td>{{ $totalContratos ?? 0 }}</td>
            </tr>
            <tr>
                <td>Total de vehículos en la flota</td>
                <td>{{ $totalVehiculos ?? 0 }}</td>
            </tr>
            <tr>
                <td>Vehículos actualmente rentados</td>
                <td>{{ $vehiculosRentados ?? 0 }}</td>
            </tr>
            <tr>
                <td>Tasa de ocupación</td>
                <td>{{ $tasaOcupacion ?? 0 }}%</td>
            </tr>
            <tr>
                <td>Clientes nuevos registrados</td>
                <td>{{ $totalClientesNuevos ?? 0 }}</td>
            </tr>
            <tr class="total-destacado">
                <td>Gastos en mantenimiento</td>
                <td>${{ number_format($totalGastosMantenimiento ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
