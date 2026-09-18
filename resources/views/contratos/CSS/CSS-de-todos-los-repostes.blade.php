{{-- resources/views/contratos/CSS/reportes.blade.php --}}
<style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 10.5px;
        color: #333;
        margin: 0;
        padding: 15px;
        background: #ffffff;
    }

    /* Cabecera con título centrado al 100% de la página */
    .header {
        position: relative;
        width: 100%;
        border-bottom: 3px solid #2c3e50;
        padding-bottom: 8px;
        margin-bottom: 12px;
        min-height: 48px;
    }

    .header-logo {
        position: absolute;
        left: 0;
        top: 0;
        text-align: left;
        line-height: 1.2;
    }

    .header-logo strong {
        font-size: 14px;
        color: #2c3e50;
        letter-spacing: 0.5px;
    }

    .header-logo small {
        font-size: 9px;
        letter-spacing: 1.5px;
        color: #7f8c8d;
    }

    .header-title {
        width: 100%;
        text-align: center;
    }

    .header-title h1 {
        margin: 0;
        font-size: 18px;
        color: #2c3e50;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .header-title h2 {
        margin: 2px 0 1px;
        font-size: 10px;
        color: #555;
        font-weight: 500;
    }

    .header-title p {
        margin: 0;
        font-size: 9px;
        color: #7f8c8d;
    }

    .numero-contrato {
        font-weight: bold;
        font-size: 10px;
        color: #2c3e50;
        background: #eaf2f8;
        border: 1px solid #2c3e50;
        padding: 4px 8px;
        border-radius: 4px;
        white-space: nowrap;
    }

    /* Títulos de sección */
    .reporte-titulo {
        text-align: left;
        font-size: 15px;
        font-weight: bold;
        color: #2c3e50;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    .reporte-rango {
        text-align: left;
        font-size: 10px;
        color: #555;
        margin-bottom: 12px;
        border-left: 4px solid #3498db;
        padding-left: 8px;
    }

    .seccion-titulo {
        font-size: 11px;
        font-weight: bold;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 14px;
        margin-bottom: 6px;
        clear: both;
    }

    /* Tarjetas resumen */
    .cards-container {
        width: 100%;
        margin-bottom: 15px;
        clear: both;
    }

    /* SOLUCIÓN: Usamos ::after para limpiar los flotantes sin que DomPDF oculte las tarjetas */
    .cards-container::after {
        content: "";
        display: table;
        clear: both;
    }

    .card {
        float: left;
        width: 23.5%;
        margin-right: 2%;
        background: #f8f9fa;
        border-left: 4px solid #3498db;
        border-radius: 4px;
        padding: 8px 10px;
    }

    .card:last-child {
        margin-right: 0;
    }

    .card .card-label {
        font-size: 9px;
        text-transform: uppercase;
        font-weight: bold;
        color: #7f8c8d;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }

    .card .card-value {
        font-size: 18px;
        font-weight: bold;
        color: #2c3e50;
    }

    /* Tablas */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        margin-bottom: 12px;
        clear: both;
    }

    thead {
        display: table-row-group;
    }

    tr {
        page-break-inside: avoid;
    }

    th {
        background: #2c3e50;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 9.5px;
        padding: 6px;
        text-align: left;
        letter-spacing: 0.5px;
    }

    td {
        border-bottom: 1px solid #dee2e6;
        padding: 5px 6px;
        color: #333;
    }

    tbody tr:nth-child(even) {
        background: #f8f9fa;
    }

    .right {
        text-align: right;
    }

    .total-destacado {
        background: #eaf2f8 !important;
        font-weight: bold;
        border-top: 2px solid #2c3e50;
    }

    /* Nota final */
    .nota-final {
        text-align: center;
        font-size: 8.5px;
        color: #7f8c8d;
        margin-top: 20px;
        border-top: 1px solid #ccc;
        padding-top: 8px;
        clear: both;
    }

    /* Ajustes para impresión */
    @media print {
        body {
            padding: 10px;
        }
        .card {
            border: 1px solid #ccc;
        }
    }
</style>
