<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estacionamiento Paseo La Galería</title>
    <link rel="stylesheet" href="/totem/css/estilos.css">

    <style>
    .tiempo-restante {
        font-size: 24px;
        color: white;
        margin-top: 15px;
        font-family: "Futura Extra Black", sans-serif;
        text-align: center;
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

    <input type="hidden" id="hookAlias" value="{{$pago->hook_alias}}">
    <input type="hidden" id="identificador" value="{{$identificador}}">
    <input type="hidden" id="email" value="{{$email}}">
    <input type="hidden" id="monto" value="{{$monto}}">
    <input type="hidden" id="transaccion_qr_id" value="{{$pago->id}}">
    <a href="#" class="wrapper">

       <div class="titulo">
        Pago con QR
       </div>

       <div class="sub-titulo">
       Escaneá el código QR
       </div>

       <div class="codigo">
       <img src="{{$pago->qr_url}}" alt="">
       </div>

        <div class="tiempo-restante" id="tiempo-restante">
        <div style="margin-bottom: 10px;">
            Tiempo restante: <span id="countdown">05:00</span>
        </div>
        <button id="btn-cancelar" style="
            padding: 10px 25px;
            font-size: 25px;
            background-color: #ccc;
            color: #333;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        ">Cancelar</button>
        </div>

       <div class="ilustracion-qr">
        <img src="images/grafico-pago-QR.svg" alt="">
       </div>
       
    </a><!--Wrapper-->

    <script>
        document.getElementById('btn-cancelar').addEventListener('click', function () {
            window.location.href = "/totem/parking";
        });

        window.onload = function () {
            const hookAlias = document.getElementById('hookAlias').value;
            const identificador = document.getElementById('identificador').value;
            const email = document.getElementById('email').value;
            const monto = document.getElementById('monto').value;
            const transaccion_qr_id = document.getElementById('transaccion_qr_id').value;
            const equipo = localStorage.getItem('equipo') || '';

            async function verificarPagoQr() {
                try {
                    const response = await fetch('/api/bancard/qr/check/payment', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ hook_alias: hookAlias })
                    });
    
                    const result = await response.json();
    
                    if (result.data) {
                        if(result.data.status == 'confirmed')
                        {
                            window.location.href = `/totem/resultado/exito?email=${email}&identificador=${identificador}&monto=${monto}&equipo=${equipo}&transaccion_tarjeta_id=0&transaccion_qr_id=${transaccion_qr_id}`;
                        }
                        if(result.data.status == 'failed')
                        {
                            mostrarModalError('No se puedo procesar el pago.', result.data.response_description);
                            setTimeout(() => {
                                window.location.href = "/totem/parking";
                            }, 10000);
                        }
                    } else {
                        console.log('Aún sin pago...');
                    }
                } catch (error) {
                    console.error('Error al verificar el pago QR:', error);
                    mostrarModalError('Error al verificar el pago QR:', error);
                    setTimeout(() => {
                        window.location.href = "/totem/parking";
                    }, 10000);
                }
            }
    
            // Ejecutar cada 5 segundos
            let tiempoRestante = 120;// 5 minutos en segundos

            function actualizarContador() {
                const countdownElement = document.getElementById('countdown');
                const minutos = Math.floor(tiempoRestante / 60).toString().padStart(2, '0');
                const segundos = (tiempoRestante % 60).toString().padStart(2, '0');
                countdownElement.textContent = `${minutos}:${segundos}`;

                if (tiempoRestante <= 0) {
                    window.location.href = "/totem/parking";
                }

                tiempoRestante--;
            }

            // Inicia el contador inmediatamente
            actualizarContador();
            setInterval(actualizarContador, 1000);

            setInterval(verificarPagoQr, 5000);

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

        };
    </script>    
</body>
</html>