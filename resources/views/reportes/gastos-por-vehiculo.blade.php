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
    <h1>Gastos por Vehículo</h1>
    <p>Del {{ $fechaInicio }} al {{ $fechaFin }}</p>

    <table>
        <thead>
            <tr><th>Placa</th><th>Marca / Modelo</th><th>Mantenimiento</th><th>Incidencias (negocio)</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($vehiculos as $item)
            <tr>
                <td>{{ $item['vehiculo']->placa }}</td>
                <td>{{ $item['vehiculo']->modelo->marca->nombre ?? '' }} {{ $item['vehiculo']->modelo->nombre ?? '' }}</td>
                <td>${{ number_format($item['gasto_mantenimiento'], 2) }}</td>
                <td>${{ number_format($item['gasto_incidencias_negocio'], 2) }}</td>
                <td>${{ number_format($item['gasto_total'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td colspan="4">Total general</td>
                <td>${{ number_format($totalGeneral, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
