<!DOCTYPE html>
<html>
<head>
   <meta charset="UTF-8">
    @include('contratos.CSS.estilos-reporte')
</head>
<body>
    <h2>Reporte de reservas canceladas</h2>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th>Motivo</th>
                <th>Cancelado por</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cancelaciones as $c)
            <tr>
                <td>{{ $c->fecha_cancelacion->format('d/m/Y') }}</td>
                <td>{{ $c->reserva->cliente->nombre }}</td>
                <td>{{ $c->reserva->vehiculo->placa }}</td>
                <td>{{ $c->motivo }}</td>
                <td>{{ $c->user->nombre }} {{ $c->user->apellido }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
