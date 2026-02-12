<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticket</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }

    .ticket {
      width: 80mm;
      padding: 10mm;
      margin: 0 auto;
      border: none;
      page-break-inside: avoid;
    }

    .centered {
      text-align: center;
    }

    .left-aligned {
      text-align: left;
    }

    .right-aligned {
      text-align: right;
    }

    .bold {
      font-weight: bold;
    }

    .small {
      font-size: 0.7em;
    }

    .line {
      border-top: 1px dashed #000;
      margin: 10px 0;
    }

    .item {
      margin-bottom: 5px;
    }

    .description {
      font-size: 1em;
      margin-bottom: 5px;
      font-weight: bold;
    }

    .details {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      column-gap: 10px;
      font-size: 0.9em;
    }

    .details span {
      text-align: center;
      padding: 5px 0;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    table th, table td {
      text-align: left;
      padding: 5px;
    }

    @media print {
      @page {
        size: 80mm auto;
        margin: 0;
      }
      body {
        margin: 0;
        padding: 0;
      }
      .ticket {
        page-break-inside: avoid;
      }
    }
  </style>
</head>
<body>
  <div class="ticket">
    <div class="centered">
      <p>DAYLIC SOCIEDAD ANÓNIMA<br>
        OTRAS ACTIVIDADES DE SERVICIOS DE APOYO A EMPRESAS N.C.P. <br>
      <p>
        SANTA TERESA ENTRE AVIADORES DEL CHACO Y HERMINIO MALDONADO, ASUNCION - PARAGUAY<br>
        RUC: 80128957-2
      </p>
    </div>
    <div class="centered line"></div>
    <p class="">
       Timbrado Nro.: 17600887<br>
       Fecha Inicio Vigencia: 01-11-2024<br>
       Fact Electronica Nro: {{$venta->numero_factura}}<br>
       Condicion de Venta: CONTADO<br>
       Fecha Factura: {{date('d-m-Y', strtotime($venta->fecha_emision))}}</p>
    <p>Cliente: {{strtoupper($venta->nombre_receptor)}}<br>
       RUC/CI: {{$venta->ruc_receptor}}</p>
    <div class="line"></div>
    <div class="left-aligned">
        <div class="item">
          <p class="description">COBRO DE ESTACIONAMIENTO</p>
          <div class="details">
            <span><strong>Cant:</strong> {{ number_format($venta->total / 10000, 2, ',', '.') }}</span>
            <span><strong>Precio:</strong> {{ number_format(10000, 0, '', '.') }}</span>
            <span><strong>Total:</strong> {{ number_format($venta->total, 0, '', '.') }}</span>
          </div>
        </div>
    </div>
    <div class="line"></div>
    <table>
      <tbody>
        <tr>
          <td>Exentas</td>
          <td style="text-align: right;">0</td>
        </tr>
        <tr>
          <td>Gravadas 10%</td>
          <td style="text-align: right;">{{number_format($venta->total - round($venta->total / 11), 0, '', '.')}}</td>
        </tr>
        <tr>
          <td>IVA 10%</td>
          <td style="text-align: right;">{{number_format(round($venta->total / 11), 0, '', '.')}}</td>
        </tr>
        <tr>
          <td>Gravadas 5%</td>
          <td style="text-align: right;">0</td>
        </tr>
        <tr>
          <td>IVA 5%</td>
          <td style="text-align: right;">0</td>
        </tr>
      </tbody>
    </table>
    <div class="line"></div>
    <p class="right-aligned bold">Total: {{number_format($venta->total, 0, '', '.')}}</p>
    <div class="centered line"></div>
    <div class="centered">
      <p>Gracias por su visita<br>
      <p>Consulte la validez de esta Factura Electronica con el numero de CDC impreso abajo en https://ekuatia.set.gov.py/consultas/<br>
      <p>CDC : {{$venta->cdc}}<br>
    </div>
  </div>
</body>
</html>
