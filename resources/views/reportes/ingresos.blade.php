<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @include('contratos.CSS.estilos-reporte')
</head>
<body>
    <div class="header">
        <div class="header-logo">
            <strong style="font-size:13px;">El Guayabo</strong><br>
            <small>RENT CAR</small>
        </div>
        <div class="header-title">
            <h1>RENTACARS "EL GUAYABO"</h1>
            <h2>TRANSPORTE Y RENTA DE VEHÍCULOS</h2>
            <p>Cel.: 6006-8390</p>
        </div>
        <div class="numero-contrato">
            REV-01
        </div>
    </div>

    <div class="seccion-titulo">REPORTE DE INGRESOS</div>
    <p style="margin-bottom:8px;">Período: {{ $fechaInicio }} al {{ $fechaFin }}</p>

    <div class="seccion-titulo">Detalle de Transacciones</div>
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
        </tbody>
    </table>
    <p class="total">Total General: ${{ number_format($totalIngresos, 2) }}</p>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>
</body>
</html>
