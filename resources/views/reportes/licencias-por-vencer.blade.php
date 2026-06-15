<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    @include('contratos.CSS.estilos-reporte')
</head>
<body>
    <h2>Clientes con licencia próxima a vencer</h2>
    <p>Próximos {{ $dias }} días</p>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>DUI</th>
                <th>Licencia</th>
                <th>Vencimiento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientes as $cliente)
            <tr>
                <td>{{ $cliente->nombre }}</td>
                <td>{{ $cliente->dui }}</td>
                <td>{{ $cliente->numero_licencia }}</td>
                <td>{{ $cliente->vencimiento_licencia->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
