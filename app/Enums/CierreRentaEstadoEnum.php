<?php

namespace App\Enums;

enum CierreRentaEstadoEnum: string
{
    case FINALIZADO = 'FINALIZADO';
    case ANULADO = 'ANULADO';
    //el enum de EN REVICION se quito  por que no le entontre una utilidad significativa
    
}
