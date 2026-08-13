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
    <h1>Saldos Pendientes</h1>

    <table>
        <thead>
            <tr><th>N° Contrato</th><th>Cliente</th><th>Vehículo</th><th>Monto total</th><th>Pagado</th><th>Saldo</th></tr>
        </thead>
        <tbody>
            @foreach($contratos as $item)
            <tr>
                <td>{{ $item['contrato']->numero_contrato }}</td>
                <td>{{ $item['contrato']->cliente->nombre ?? '' }}</td>
                <td>{{ $item['contrato']->vehiculo->placa ?? '' }}</td>
                <td>${{ number_format($item['contrato']->monto_total_renta, 2) }}</td>
                <td>${{ number_format($item['total_pagado'], 2) }}</td>
                <td>${{ number_format($item['saldo'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td colspan="5">Total saldos pendientes</td>
                <td>${{ number_format($totalSaldos, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
