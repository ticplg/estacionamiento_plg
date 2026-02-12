<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estacionamiento Paseo La Galería</title>
    <link rel="stylesheet" href="/totem/css/estilos.css">
</head>
<body>

    <div id="modal-error" class="modal-externo oculto">
        <div class="modal-contenido">
          <p id="modal-error-mensaje"></p>
          <button id="modal-error-boton">Aceptar</button>
        </div>
    </div>


    <input type="hidden" name="identificador" id="identificador" value={{$identificador}}>
    <input type="hidden" name="email" id="email" value={{$email}}>
    <input type="hidden" name="monto" id="monto" value={{$monto}}>
    <a href="#" class="wrapper">
       <div class="titulo">
        Pago con tarjeta
       </div>
       <div class="sub-titulo">
        Acercá tu tarjeta al POS
       </div>
       <div class="ilustracion">
        <img src="/totem/images/grafico-pago-tarjeta.svg" alt="">
       </div>
    </a>

    <script>
        window.onload = async function () {
            const identificador = document.getElementById('identificador').value;
            const email = document.getElementById('email').value;
            const monto = document.getElementById('monto').value;
            const equipo = localStorage.getItem('equipo') || '';
        
            try {
                const response = await fetch('/totem/iniciar-proceso-pago/tarjeta', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        identificador,
                        email,
                        monto,
                        equipo
                    })
                });
        
                const data = await response.json();
        
                if (response.ok && data.code === 1) {
                    // Redirigir si fue exitoso
                    window.location.href = `/totem/resultado/exito?email=${email}&identificador=${identificador}&monto=${monto}&equipo=${equipo}&transaccion_tarjeta_id=${data.data.id}&transaccion_qr_id=0`; // o la ruta que uses
                } else {
                    mostrarModalError(data.message || 'Hubo un error al iniciar el pago.', data.error);
                    setTimeout(() => {
                        window.location.href = "/totem/parking";
                    }, 10000);

                }
            } catch (error) {
                console.error('Error en el proceso:', error);
                mostrarModalError('Error de conexión. Intentá de nuevo.', error.message);
                setTimeout(() => {
                    window.location.href = "/totem/parking";
                }, 10000);
            }
        
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