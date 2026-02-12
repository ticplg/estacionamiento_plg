<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Estacionamiento Paseo La Galería</title>
  <link rel="stylesheet" href="/totem/css/estilos.css" />
  <style>
    .hidden {
      display: none;
    }
  </style>
</head>
<body>

        <div id="modal-error" class="modal-externo oculto">
        <div class="modal-contenido">
          <p id="modal-error-mensaje"></p>
          <button id="modal-error-boton">Aceptar</button>
        </div>
    </div>

    {{-- Loader visible inicialmente --}}
    <div id="loader" class="wrapper loader">
        <div class="cube">
        <div class="cube_item cube_x"></div>
        <div class="cube_item cube_y"></div>
        <div class="cube_item cube_y"></div>
        <div class="cube_item cube_x"></div>
        </div>
    </div>
    <input type="hidden" name="" id="identificador" value="{{$identificador}}">
    {{-- Contenido oculto inicialmente --}}
    <div id="contenido" class="wrapper hidden">
        <div class="titulo">Detalles de tu visita</div>
        <div class="datos-visita">
        <div class="dato">
            <div class="dato--titulo">Número de ticket</div>
            <div class="dato--dato" id="ticket"></div>
        </div>
        <div class="dato">
            <div class="dato--titulo">Hora de entrada</div>
            <div class="dato--dato" id="entrada"></div>
        </div>
        <div class="dato">
            <div class="dato--titulo">Hora de salida</div>
            <div class="dato--dato" id="salida"></div>
        </div>
        <div class="dato">
            <div class="dato--titulo">Duración de tu estadía</div>
            <div class="dato--dato" id="duracion"></div>
        </div>
        <div class="dato">
            <div class="dato--titulo">Monto a pagar</div>
            <div class="dato--dato" id="monto"></div>
        </div>
        </div>

        <div class="acciones">
            <a href="datos-factura.html" id="btn-siguiente"><button>Siguiente</button></a>
            <a href="detalles-visita-pago.html" id="btn-validar"><button>Validar</button></a>
        </div>
        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const identificador = document.getElementById('identificador').value;
        
            // Ocultar ambos botones al inicio
            document.querySelector('#btn-siguiente button').style.display = 'none';
            document.querySelector('#btn-validar button').style.display = 'none';
        
            // Temporizador de 30 segundos
            setTimeout(() => {
                window.location.href = '/parking/totem';
            }, 30000);
        
            fetch(`/totem/informacion/identificador/${identificador}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status == 200) {
                        const info = data.data;
        
                        document.getElementById('ticket').textContent = info.ticketNumber;
                        document.getElementById('entrada').textContent = info.entryDateTime;
                        document.getElementById('salida').textContent = info.finishDateTime;
                        document.getElementById('duracion').textContent = info.parkingDuration;
                        document.getElementById('monto').textContent = 'Gs. ' + info.price_format;
        
                        if (parseFloat(info.price) <= 0) {
                            // Mostrar botón "Validar"
                            document.querySelector('#btn-validar button').style.display = 'inline-block';
                            const btnValidar = document.getElementById('btn-validar');
                            btnValidar.href = `/totem/validar/pagocero?ticket=${info.ticketNumber}`;
                        } else {
                            // Mostrar botón "Siguiente"
                            document.querySelector('#btn-siguiente button').style.display = 'inline-block';
                            const btnSiguiente = document.getElementById('btn-siguiente');
                            btnSiguiente.href = `/totem/datos-factura?ticket=${info.ticketNumber}&monto=${info.price}`;
                        }
                    } else {
                        //alert('No se pudieron cargar los datos del ticket.');
                        mostrarModalError('No se pudieron cargar los datos del ticket.', "");
                    }
                    setTimeout(() => {
                        window.location.href = "/totem/parking";
                    }, 20000);
                    
                    document.getElementById('loader').classList.add('hidden');
                    document.getElementById('contenido').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    //alert('Error al cargar los datos.');
                    mostrarModalError('No se pudieron leer los datos.', "");
                    document.getElementById('loader').classList.add('hidden');

                    setTimeout(() => {
                        window.location.href = "/totem/parking";
                    }, 20000);
                });
        });

            function mostrarModalError(mensaje, detalle = '') 
            {
                const modal = document.getElementById('modal-error');
                const mensajeElemento = document.getElementById('modal-error-mensaje');
                mensajeElemento.textContent = `${mensaje}${detalle ? "\n\n" + detalle : ''}`;
                modal.classList.remove('oculto');
        
                // Click en botón "Aceptar"
                document.getElementById('modal-error-boton').addEventListener('click', () => {
                    window.location.href = "/totem/parking";
                });
        
                // Click fuera del modal
                modal.addEventListener('click', (e) => {
                    if (e.target === modal) {
                        window.location.href = "/totem/parking";
                    }
                });
            }
    </script>
    
</body>
</html>
