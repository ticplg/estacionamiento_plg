<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estacionamiento Paseo La Galería</title>
    <link rel="stylesheet" href="/totem/css/estilos.css">
    <link rel="stylesheet" href="/totem/css/splide-core.min.css">
</head>
<body>
    
    <a href="/totem/validar/ticket/30461395190119800038416" class="wrapper">
       <div class="mensaje">
        Pasá tu ticket por el escáner.
       </div>
       {{--<div class="carousel">
        <div class="splide" role="group">
            <div class="splide__track">
                  <ul class="splide__list">
                      <li class="splide__slide"><div class="splide__slide__container"><img src="images/carousel/1.jpg" alt=""></div></li>
                      <li class="splide__slide"><div class="splide__slide__container"><img src="images/carousel/2.jpg" alt=""></div></li>
                      <li class="splide__slide"><div class="splide__slide__container"><img src="images/carousel/3.jpg" alt=""></div></li>
                      <li class="splide__slide"><div class="splide__slide__container"><img src="images/carousel/4.jpg" alt=""></div></li>
                      <li class="splide__slide"><div class="splide__slide__container"><img src="images/carousel/5.jpg" alt=""></div></li>
                  </ul>
            </div>
          </div>
       </div>--}}
    </a>
</body>
<script src="/totem/js/splide.min.js"></script>
  <script>
    document.addEventListener( 'DOMContentLoaded', function () {
      new Splide( '.splide', {
            fixedHeight: '1100px',
        autoplay: true,
            arrows: false,
            pagination: false,
      } ).mount();
    } );
  </script>
</html>