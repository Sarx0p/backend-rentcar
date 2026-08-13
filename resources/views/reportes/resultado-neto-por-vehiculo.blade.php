<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
body{font-family:sans-serif;font-size:12px;}
h1{font-size:18px;}
table{width:100%;border-collapse:collapse;margin-top:15px;}
td,th{border:1px solid #333;padding:6px;text-align:left;}
.total{font-weight:bold;background:#f0f0f0;}
.negativo{color:#b00000;}
</style></head>
<body>
    <h1>Resultado Neto por Vehículo</h1>
    <p>Del {{ $fechaInicio }} al {{ $fechaFin }}</p>

    <table>
        <thead>
            <tr><th>Placa</th><th>Marca / Modelo</th><th>Propietario</th><th>Ingresos</th><th>Gastos</th><th>Resultado neto</th></tr>
        </thead>
        <tbody>
            @foreach($vehiculos as $item)
            <tr>
                <td>{{ $item['vehiculo']->placa }}</td>
                <td>{{ $item['vehiculo']->modelo->marca->nombre ?? '' }} {{ $item['vehiculo']->modelo->nombre ?? '' }}</td>
                <td>{{ $item['vehiculo']->propietario->nombre ?? '' }}</td>
                <td>${{ number_format($item['ingresos'], 2) }}</td>
                <td>${{ number_format($item['gastos'], 2) }}</td>
                <td class="{{ $item['resultado_neto'] < 0 ? 'negativo' : '' }}">${{ number_format($item['resultado_neto'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td colspan="3">Totales</td>
                <td>${{ number_format($totalIngresos, 2) }}</td>
                <td>${{ number_format($totalGastos, 2) }}</td>
                <td>${{ number_format($totalNeto, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
