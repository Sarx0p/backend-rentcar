<?php

namespace App\Enums;

enum CierreRentaEstadoEnum: string
{
    case FINALIZADO = 'FINALIZADO';
    case FINALIZADO_CON_DEUDA = 'FINALIZADO_CON_DEUDA';
    case ANULADO = 'ANULADO';
    //el enum de EN REVICION se quito  por que no le entontre una utilidad significativa

}
