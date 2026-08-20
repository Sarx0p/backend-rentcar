<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    color: #000;
    background-color: #fff;
    margin: 20px 30px; /* Margen lateral adecuado para que no pegue al borde */
}

.header {
    display: table;
    width: 100%;
    border-bottom: 2px solid #000;
    padding-bottom: 6px;
    margin-bottom: 8px;
}

.header-logo {
    display: table-cell;
    width: 15%;
    vertical-align: middle;
    text-align: center;
}

.header-title {
    display: table-cell;
    width: 70%;
    text-align: center;
    vertical-align: middle;
}

.header-title h1 {
    font-size: 20px;
    color: #000;
    font-weight: bold;
    margin: 0;
}

.header-title h2 {
    font-size: 12px;
    color: #000;
    margin: 0;
}

.header-title p {
    font-size: 11px;
    margin: 0;
}

.numero-contrato {
    display: table-cell;
    width: 15%;
    vertical-align: middle;
    text-align: right;
    font-weight: bold;
    font-size: 13px;
    color: #000;
}

.campo-row {
    display: table;
    width: 100%;
    margin-bottom: 4px;
}

.campo {
    display: table-cell;
    padding-right: 8px;
    white-space: nowrap;
}

.campo:last-child {
    padding-right: 0;
}

.campo-label {
    font-weight: bold;
}

.campo-valor {
    border-bottom: 1px solid #000;
    min-width: 100px;
    display: inline-block;
    padding: 0 4px;
}

.seccion-titulo {
    background-color: #fff;
    color: #000;
    border: 1px solid #000;
    text-align: center;
    font-weight: bold;
    padding: 4px;
    margin: 8px 0 4px 0;
    font-size: 12px;
}

.dibujos-combustible {
    display: table;
    width: 100%;
    margin: 8px 0;
}

.dibujos {
    display: table-cell;
    width: 50%;
    text-align: center;
}

.combustible {
    display: table-cell;
    width: 50%;
    text-align: center;
    vertical-align: middle;
}

.clausulas {
    margin-top: 15px;
    width: 100%;
}

.clausulas-cols {
    display: table;
    width: 100%;
    table-layout: fixed; /* Evita que las columnas sobrepasen el ancho de la hoja */
}

.clausula-col {
    display: table-cell;
    width: 50%;
    padding-right: 15px;
    vertical-align: top;
    font-size: 12px;
    line-height: 1.55; /* Mantiene la altura sin desbordar los lados */
    text-align: justify;
}

.clausula-col:last-child {
    padding-right: 0;
    padding-left: 15px;
}

.clausula {
    margin-bottom: 18px;
}

.firmas {
    display: table;
    width: 100%;
    table-layout: fixed;
    margin-top: 65px;
}

.firma-col {
    display: table-cell;
    width: 50%;
    text-align: center;
    padding: 0 15px;
    vertical-align: top;
}

.firma-linea {
    border-top: 1px solid #000;
    margin-top: 30px;
    padding-top: 4px;
    font-weight: bold;
}

.nota-final {
    text-align: center;
    font-weight: bold;
    font-size: 10.5px;
    margin-top: 50px;
    border-top: 1px solid #000;
    padding-top: 6px;
}
