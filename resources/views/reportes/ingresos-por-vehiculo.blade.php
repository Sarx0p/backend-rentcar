<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingresos por Vehículo</title>
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

    <div class="reporte-titulo">Ingresos por Vehículo</div>
    <div class="reporte-rango">
        Del {{ $fechaInicio }} al {{ $fechaFin }}

        @if(!empty($propietario))
            <br>Propietario: <strong>{{ $propietario->nombre }}</strong>
        @elseif(!empty(trim($propietarioBusqueda ?? '')))
            <br>Filtro propietario: <strong>{{ $propietarioBusqueda }}</strong>
        @else
            <br>Todos los propietarios
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Placa</th>
                <th>Propietario</th>
                <th>Marca / Modelo</th>
                <th class="right">N° Rentas</th>
                <th class="right">Ingresos</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vehiculos as $item)
            <tr>
                <td>{{ $item['vehiculo']->placa }}</td>
                <td>
                    {{ optional($item['vehiculo']->propietario)->nombre }}
                </td>
                <td>
                    {{ optional(optional($item['vehiculo']->modelo)->marca)->nombre }}
                    {{ optional($item['vehiculo']->modelo)->nombre }}
                </td>
                <td class="right">{{ $item['num_rentas'] }}</td>
                <td class="right">${{ number_format($item['ingresos'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center;">No se encontraron resultados para este filtro.</td>
            </tr>
            @endforelse

            @if($vehiculos->isNotEmpty())
            <tr class="total-destacado">
                <td colspan="4">Total general</td>
                <td class="right">${{ number_format($totalGeneral, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="nota-final">Reporte generado automáticamente por el sistema.</div>

</body>
</html>
