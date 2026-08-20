<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos</title>
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

    <div class="reporte-titulo">Reporte de Ingresos</div>
    <div class="reporte-rango">Período: {{ $fechaInicio }} al {{ $fechaFin }}</div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Nº Contrato</th>
                <th>Método</th>
                <th class="right">Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pagos as $pago)
            <tr>
                <td>{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                <td>{{ $pago->contrato->numero_contrato }}</td>
                <td>{{ $pago->metodo_pago }}</td>
                <td class="right">${{ number_format($pago->monto, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-destacado">
                <td colspan="3">Total General</td>
                <td class="right">${{ number_format($totalIngresos, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
