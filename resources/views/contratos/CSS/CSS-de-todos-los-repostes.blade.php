{{-- resources/views/contratos/CSS/reportes.blade.php --}}
<style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        font-size: 11px;
        color: #333;
        margin: 0;
        padding: 25px;
        background: #ffffff;
    }

    /* Cabecera */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid #2c3e50;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    .header-logo {
        text-align: left;
        line-height: 1.3;
    }

    .header-logo strong {
        font-size: 15px;
        color: #2c3e50;
        letter-spacing: 0.5px;
    }

    .header-logo small {
        font-size: 10px;
        letter-spacing: 1.5px;
        color: #7f8c8d;
    }

    .header-title {
        text-align: center;
        flex: 1;
        margin-left: 20px;
    }

    .header-title h1 {
        margin: 0;
        font-size: 20px;
        color: #2c3e50;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .header-title h2 {
        margin: 4px 0 2px;
        font-size: 11px;
        color: #555;
        font-weight: 500;
    }

    .header-title p {
        margin: 0;
        font-size: 10px;
        color: #7f8c8d;
    }

    .numero-contrato {
        font-weight: bold;
        font-size: 11px;
        color: #2c3e50;
        background: #eaf2f8;
        border: 1px solid #2c3e50;
        padding: 6px 12px;
        border-radius: 4px;
        white-space: nowrap;
    }

    /* Títulos de sección */
    .reporte-titulo {
        text-align: left;
        font-size: 16px;
        font-weight: bold;
        color: #2c3e50;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .reporte-rango {
        text-align: left;
        font-size: 11px;
        color: #555;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
        padding-left: 10px;
    }

    .seccion-titulo {
        font-size: 12px;
        font-weight: bold;
        color: #2c3e50;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 20px;
        margin-bottom: 8px;
    }

    /* Tarjetas resumen */
    .cards-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 25px;
    }

    .card {
        flex: 1 1 180px;
        background: #f8f9fa;
        border-left: 4px solid #3498db;
        border-radius: 5px;
        padding: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .card .card-label {
        font-size: 10px;
        text-transform: uppercase;
        font-weight: bold;
        color: #7f8c8d;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }

    .card .card-value {
        font-size: 22px;
        font-weight: bold;
        color: #2c3e50;
    }

    /* Tablas */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        margin-bottom: 20px;
    }

    th {
        background: #2c3e50;
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 10px;
        padding: 10px 8px;
        text-align: left;
        letter-spacing: 0.5px;
    }

    td {
        border-bottom: 1px solid #dee2e6;
        padding: 8px;
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
        font-size: 9px;
        color: #7f8c8d;
        margin-top: 30px;
        border-top: 1px solid #ccc;
        padding-top: 10px;
    }

    /* Ajustes para impresión */
    @media print {
        body {
            padding: 10px;
        }
        .card {
            box-shadow: none;
            border: 1px solid #ccc;
        }
    }
</style>
