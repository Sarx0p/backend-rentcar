<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
body{font-family:sans-serif;font-size:12px;}
h1{font-size:18px;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
td,th{border:1px solid #333;padding:6px;text-align:left;}
.total{font-weight:bold;background:#f0f0f0;}
</style></head>
<body>
    <h1>Reporte de Desempeño General</h1>
    <p>Del {{ $fechaInicio }} al {{ $fechaFin }}</p>

    <table>
        <tr><td>Total de ingresos</td><td>${{ number_format($totalIngresos, 2) }}</td></tr>
        <tr><td>Total de contratos generados</td><td>{{ $totalContratos }}</td></tr>
        <tr><td>Total de vehículos en la flota</td><td>{{ $totalVehiculos }}</td></tr>
        <tr><td>Vehículos actualmente rentados</td><td>{{ $vehiculosRentados }}</td></tr>
        <tr><td>Tasa de ocupación</td><td>{{ $tasaOcupacion }}%</td></tr>
        <tr><td>Clientes nuevos registrados</td><td>{{ $totalClientesNuevos }}</td></tr>
        <tr><td>Gastos en mantenimiento</td><td>${{ number_format($totalGastosMantenimiento, 2) }}</td></tr>
    </table>
</body>
</html>
