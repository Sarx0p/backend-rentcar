<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    @include('contratos.CSS.colores-contrato')
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
            N° {{ $contrato->numero_contrato }}
        </div>
    </div>


    <div class="campo-row">
        <div class="campo" style="width:100%">
            <span class="campo-label">Nombre: </span>
            <span class="campo-valor" style="min-width:400px">{{ $contrato->cliente->nombre }}</span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="width:40%">
            <span class="campo-label">Número de DUI: </span>
            <span class="campo-valor">{{ $contrato->cliente->dui }}</span>
        </div>
        <div class="campo" style="width:60%">
            <span class="campo-label">Número de licencia: </span>
            <span class="campo-valor">{{ $contrato->cliente->numero_licencia }}</span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="width:40%">
            <span class="campo-label">Departamento: </span>
            <span class="campo-valor">{{ $contrato->cliente->municipio->departamento->nombre }}</span>
        </div>
        <div class="campo" style="width:60%">
            <span class="campo-label">Municipio: </span>
            <span class="campo-valor">{{ $contrato->cliente->municipio->nombre }}</span>
        </div>
    </div>


    <div class="seccion-titulo">DATOS DEL VEHÍCULO</div>

    <div class="campo-row">
        <div class="campo" style="width:33%">
            <span class="campo-label">Marca: </span>
            <span class="campo-valor">{{ $contrato->vehiculo->modelo->marca->nombre }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Tipo: </span>
            <span class="campo-valor">{{ $contrato->vehiculo->categoria->nombre }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Placa: </span>
            <span class="campo-valor">{{ $contrato->vehiculo->placa }}</span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="width:33%">
            <span class="campo-label">Color: </span>
            <span class="campo-valor">{{ $contrato->vehiculo->color }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Modelo: </span>
            <span class="campo-valor">{{ $contrato->vehiculo->modelo->nombre }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Año: </span>
            <span class="campo-valor">{{ $contrato->vehiculo->anio }}</span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="width:33%">
            <span class="campo-label">Fecha de entrega: </span>
            <span class="campo-valor">{{ \Carbon\Carbon::parse($contrato->fecha_hora_entrega)->format('d/m/Y') }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Hora de entrega: </span>
            <span class="campo-valor">{{ \Carbon\Carbon::parse($contrato->fecha_hora_entrega)->format('h:i A') }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Precio por día: </span>
            <span class="campo-valor">${{ number_format($contrato->precio_por_dia, 2) }}</span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="width:33%">
            <span class="campo-label">Fecha de recibimiento: </span>
            <span class="campo-valor">{{ \Carbon\Carbon::parse($contrato->fecha_hora_devolucion)->format('d/m/Y') }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Hora de recibimiento: </span>
            <span class="campo-valor">{{ \Carbon\Carbon::parse($contrato->fecha_hora_devolucion)->format('h:i A') }}</span>
        </div>
        <div class="campo" style="width:33%">
            <span class="campo-label">Monto total: </span>
            <span class="campo-valor">${{ number_format($contrato->monto_total_renta, 2) }}</span>
        </div>
    </div>

    <div class="campo-row">
        <div class="campo" style="width:100%">
            <span class="campo-label">Observaciones del vehículo: </span>
            <span class="campo-valor" style="min-width:400px">{{ $contrato->observaciones_entrega ?? '' }}</span>
        </div>
    </div>


    <div class="dibujos-combustible">
        <div class="dibujos">
            &nbsp;
        </div>
        <div class="combustible">
            <strong>Nivel de combustible:</strong><br><br>
            <table style="margin: 0 auto; border-collapse: collapse;">
                <tr>
                    @foreach(['E', '1/4', '1/2', '3/4', 'F'] as $nivel)
                    <td style="padding: 2px 8px; border: 1px solid #000; text-align:center;">
                        {{ $nivel }}
                    </td>
                    @endforeach
                </tr>
                <tr>
                    @foreach(['E', '1/4', '1/2', '3/4', 'F'] as $nivel)
                    <td style="padding: 4px 8px; border: 1px solid #000; text-align:center;">
                        @if($contrato->nivel_combustible_entrega === $nivel) x @endif
                    </td>
                    @endforeach
                </tr>
            </table>
        </div>
    </div>

    <div class="clausulas">
        <div class="clausulas-cols">
            <div class="clausula-col">
                <div class="clausula"><strong>1-</strong> El presente contrato se dará por terminado automáticamente por cualquier violación que cometa el arrendatario al contenido de este contrato y responder económicamente por daños ocasionados al vehículo mientras se encuentra en poder físico o jurídico, así como por los daños a personas que viajen con el arrendario como cualquier daño a terceros.</div>
                <div class="clausula"><strong>2-</strong> De ocurrir algún desperfecto al vehículo que amerite la internación a un taller o algún siniestro el arrendatario tendrá que dar aviso al propietario de lo contrario el propietario no se hará cargo de ningún gasto efectuado por el arrendatario así como también no tendrá ninguna responsabilidad hacia el arrendatario.</div>
                <div class="clausula"><strong>3-</strong> El arrendante no se hará responsable por la pérdida o daño de cualquier objeto que el arrendatario o cualquier otra persona de olvide, almacene o transporte en el vehículo ya sea antes o después de la entrega del vehículo con motivo de ello el arrendatario conviene en liberar al arrendante de toda responsabilidad.</div>
            </div>
            <div class="clausula-col">
                <div class="clausula"><strong>4-</strong> El arrendatario es responsable por los robos parciales de partes y accesorios del vehículo objeto de este contrato.</div>
                <div class="clausula"><strong>5-</strong> El arrendatario se hará responsable civil y penalmente de todos los daños que ocasione en el vehículo objeto de este contrato a terceros por consecuencia de accidentes de tránsito en que intervenga dicho vehículo.</div>
                <div class="clausula"><strong>6-</strong> Cualquier violación a las cláusulas de este contrato será causa de terminación del mismo y hará responsable al arrendatario de todo daño que por ello se ocasione aunque hubiere solicitado y obtenido cobertura total.</div>
            </div>
        </div>
    </div>


   <div class="firmas">
    <div class="firma-col">
        <div style="text-align:center; margin-bottom: 8px;">NOMBRE DEL CLIENTE</div>
        <div style="text-align:center; font-weight: bold; margin-bottom: 4px;">
            {{ $contrato->cliente->nombre }}
        </div>
        <div style="border-top: 1px solid #000;"></div>

        <div style="text-align:center; margin-top: 8px; margin-bottom: 40px;">FIRMA DEL CLIENTE</div>
        <div style="border-top: 1px solid #000;"></div>
    </div>

    <div class="firma-col">
        <div style="text-align:center; margin-bottom: 8px;">NOMBRE DEL ARRENDANTE</div>
        <div style="text-align:center; font-weight: bold; margin-bottom: 4px;">
            {{ $contrato->user->nombre }} {{ $contrato->user->apellido }}
        </div>
        <div style="border-top: 1px solid #000;"></div>

        <div style="text-align:center; margin-top: 8px; margin-bottom: 40px;">FIRMA DEL ARRENDANTE</div>
        <div style="border-top: 1px solid #000;"></div>
    </div>
</div>

    <div class="nota-final">
        NOTA: EL CONDUCTOR EN ESTADO DE EBRIEDAD SE HACE RESPONSABLE POR DAÑOS AL VEHÍCULO
    </div>

</body>
</html>
