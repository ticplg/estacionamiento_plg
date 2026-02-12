<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estacionamiento Paseo La Galería</title>

  <link rel="stylesheet" href="/totem/css/estilos.css">

  <style>
    #loader {
      display: none;
    }

    body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden;
    }

    .wrapper{
      position: relative;
    }

    /* BOTÓN VOLVER – CTA AMARILLO */
    .btn-volver-teclado{
      position: absolute;
      top: 20px;
      left: 20px;

      background-color: #F9EC00;
      color: #0b1b5d;

      font-family: "Futura Extra Black";
      font-size: 18px;
      text-transform: uppercase;
      text-decoration: none;

      padding: 14px 22px;
      border-radius: 14px;

      display: inline-flex;
      align-items: center;
      gap: 10px;

      box-shadow: 0 6px 14px rgba(0,0,0,.25);
      z-index: 20;
    }

    .btn-volver-teclado:active{
      transform: scale(.96);
      box-shadow: 0 3px 8px rgba(0,0,0,.35);
    }
  </style>
</head>
<body>

  <!-- Loader -->
  <div id="loader" class="wrapper loader">
    <div class="cube">
      <div class="cube_item cube_x"></div>
      <div class="cube_item cube_y"></div>
      <div class="cube_item cube_y"></div>
      <div class="cube_item cube_x"></div>
    </div>
  </div>

  <div class="wrapper">

    <!-- VOLVER -->
    <a href="/totem/parking" class="btn-volver-teclado">
      ← Volver
    </a>

    <div id="titulo_titulo" class="titulo">
      Elegí un método de pago
    </div>

    <div class="metodos-de-pago" id="metodos-de-pago">
      <div class="metodo">
        <a href="/totem/pago-qr?email={{($email != 0) ? $email : 0}}&identificador={{$identificador}}&monto={{$monto}}" class="contenido">
          <div class="icono">
            <img src="/totem/images/pagar-QR.svg" alt="">
          </div>
          <div class="label">Pagar con QR</div>
        </a>
      </div>

      <div class="metodo">
        <a href="/totem/pago-tarjeta?email={{($email != 0) ? $email : 0}}&identificador={{$identificador}}&monto={{$monto}}" class="contenido">
          <div class="icono">
            <img src="/totem/images/pagar-tarjeta.svg" alt="">
          </div>
          <div class="label">Pagar con tarjeta</div>
        </a>
      </div>
    </div>

  </div><!-- wrapper -->

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const enlaces = document.querySelectorAll('.metodos-de-pago .contenido');

      enlaces.forEach(function (enlace) {
        enlace.addEventListener('click', function (e) {
          e.preventDefault();

          document.getElementById('metodos-de-pago').style.display = 'none';
          document.getElementById('titulo_titulo').style.display = 'none';
          document.getElementById('loader').style.display = 'flex';

          setTimeout(() => {
            window.location.href = this.href;
          }, 300);
        });
      });
    });

    setTimeout(() => {
      window.location.href = "/totem/parking";
    }, 20000);
  </script>

</body>
</html>
