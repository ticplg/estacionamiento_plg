<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estacionamiento Paseo La Galería</title>
    <link rel="stylesheet" href="/totem/css/estilos.css">

    <style>

      .teclado-regular .linea4 {
        display: flex;
        justify-content: center;
      }

      .tecla.espacio {
        background-color: #E0E7EC;
        color: #333;
        border-radius: 8px;
        font-size: 36px;
        font-family: "Futura Extra Black";
        text-transform: uppercase;
        padding: 24px;
        width: 80%;
        max-width: 700px;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .tecla.espacio:hover,
      .tecla.espacio:active {
        background-color: #F9EC00;
      }

    .wrapper{
      position: relative;
    }

    /* BOTÓN VOLVER – IZQUIERDA, VISIBLE, CTA */
    .btn-volver-teclado{
      position: absolute;
      top: 20px;
      left: 20px;

      background-color: #F9EC00; /* amarillo CTA */
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
      border: none;

      z-index: 20;
    }

    /* feedback táctil */
    .btn-volver-teclado:active{
      transform: scale(.96);
      box-shadow: 0 3px 8px rgba(0,0,0,.35);
    }
    </style>
</head>
<body>
    <div class="wrapper">

        <a href="/totem/parking" class="btn-volver-teclado">
          ← Volver al inicio
        </a>
        <br>
       <div class="titulo">
          Datos para tu factura
       </div>
       <input type="hidden" id="identificador" value="{{$ticket}}">
       <input type="hidden" id="monto" value="{{$monto}}">
       <div class="formulario" autocomplete="off">
          <div class="form-field">
            <label for="ruc">RUC / Documento de Identidad</label>
            <input autocomplete="off" type="text" id="ruc" placeholder="Ingresá tu RUC o Documento de Identidad">
          </div>
          <div class="form-field">
            <label for="ruc">Razon Social</label>
            <input autocomplete="off" type="text" id="razon-social" placeholder="Razon Social">
            <div id="razon-loader" class="loader-inline" style="display:none;"></div>
          </div>
          <div class="form-field">
            <label for="ruc">Email</label>
            <input autocomplete="off" type="text" id="email" placeholder="Mail a donde enviar tu factura">
            <div id="email-loader" class="loader-inline" style="display:none;"></div>
          </div>
       </div>

       <div class="oculto teclado-numerico">
          <div class="linea1">
            <a class="tecla uno">1</a>
            <a class="tecla dos">2</a>
            <a class="tecla tres">3</a>
          </div>
          <div class="linea2">
            <a class="tecla cuatro">4</a>
            <a class="tecla cinco">5</a>
            <a class="tecla seis">6</a>
          </div>
          <div class="linea3">
            <a class="tecla siete">7</a>
            <a class="tecla ocho">8</a>
            <a class="tecla nueve">9</a>
          </div>
          <div class="linea4">
            <a class="tecla guion">-</a>
            <a class="tecla cero">0</a>
            <a class="tecla borrar">
              <svg
                baseProfile="tiny"
                id="backspace"
                version="1.2"
                viewBox="0 0 24 24"
                xml:space="preserve"
                xmlns="http://www.w3.org/2000/svg"
                xmlns:xlink="http://www.w3.org/1999/xlink">
                <path
                  d="M19.5,5h-10C8.234,5,6.666,5.807,5.93,6.837L3.32,10.49c-0.642,0.898-1.182,1.654-1.199,1.679  C2,12.344,1.999,12.661,2.124,12.833c0.023,0.033,0.555,0.777,1.188,1.664l2.619,3.667C6.666,19.193,8.233,20,9.5,20h10  c1.379,0,2.5-1.122,2.5-2.5v-10C22,6.122,20.879,5,19.5,5z M17.207,14.793c0.391,0.391,0.391,1.023,0,1.414  C17.012,16.402,16.756,16.5,16.5,16.5s-0.512-0.098-0.707-0.293L13.5,13.914l-2.293,2.293C11.012,16.402,10.756,16.5,10.5,16.5  s-0.512-0.098-0.707-0.293c-0.391-0.391-0.391-1.023,0-1.414l2.293-2.293l-2.293-2.293c-0.391-0.391-0.391-1.023,0-1.414  s1.023-0.391,1.414,0l2.293,2.293l2.293-2.293c0.391-0.391,1.023-0.391,1.414,0s0.391,1.023,0,1.414L14.914,12.5L17.207,14.793z"/>
              </svg>
          </div>
        </div>


        <div class="teclado-regular oculto">
          <div class="numeros">
            <a class="tecla numero1">1</a>
            <a class="tecla numero2">2</a>
            <a class="tecla numero3">3</a>
            <a class="tecla numero4">4</a>
            <a class="tecla numero5">5</a>
            <a class="tecla numero6">6</a>
            <a class="tecla numero7">7</a>
            <a class="tecla numero8">8</a>
            <a class="tecla numero9">9</a>
            <a class="tecla numero0">0</a>
            <a class="tecla borrar"><svg
              baseProfile="tiny"
              id="backspace"
              version="1.2"
              viewBox="0 0 24 24"
              xml:space="preserve"
              xmlns="http://www.w3.org/2000/svg"
              xmlns:xlink="http://www.w3.org/1999/xlink">
              <path
                d="M19.5,5h-10C8.234,5,6.666,5.807,5.93,6.837L3.32,10.49c-0.642,0.898-1.182,1.654-1.199,1.679  C2,12.344,1.999,12.661,2.124,12.833c0.023,0.033,0.555,0.777,1.188,1.664l2.619,3.667C6.666,19.193,8.233,20,9.5,20h10  c1.379,0,2.5-1.122,2.5-2.5v-10C22,6.122,20.879,5,19.5,5z M17.207,14.793c0.391,0.391,0.391,1.023,0,1.414  C17.012,16.402,16.756,16.5,16.5,16.5s-0.512-0.098-0.707-0.293L13.5,13.914l-2.293,2.293C11.012,16.402,10.756,16.5,10.5,16.5  s-0.512-0.098-0.707-0.293c-0.391-0.391-0.391-1.023,0-1.414l2.293-2.293l-2.293-2.293c-0.391-0.391-0.391-1.023,0-1.414  s1.023-0.391,1.414,0l2.293,2.293l2.293-2.293c0.391-0.391,1.023-0.391,1.414,0s0.391,1.023,0,1.414L14.914,12.5L17.207,14.793z"/>
            </svg></a>
          </div>
          <div class="linea1">
            <a class="tecla letraq">q</a>
            <a class="tecla letraw">w</a>
            <a class="tecla letrae">e</a>
            <a class="tecla letrar">r</a>
            <a class="tecla letrat">t</a>
            <a class="tecla letray">y</a>
            <a class="tecla letrau">u</a>
            <a class="tecla letrai">i</a>
            <a class="tecla letrao">o</a>
            <a class="tecla letrap">p</a>
            <a class="tecla mas">+</a>
          </div>
          <div class="linea2">
            <a class="tecla letraa">a</a>
            <a class="tecla letras">s</a>
            <a class="tecla letrad">d</a>
            <a class="tecla letraf">f</a>
            <a class="tecla letrag">g</a>
            <a class="tecla letrah">h</a>
            <a class="tecla letraj">j</a>
            <a class="tecla letrak">k</a>
            <a class="tecla letral">l</a>
            <a class="tecla guionbajo">_</a>
            <a class="tecla guion">-</a>
          </div>
          <div class="linea3">
            <a class="tecla letraz">z</a>
            <a class="tecla letrax">x</a>
            <a class="tecla letrac">c</a>
            <a class="tecla letrav">v</a>
            <a class="tecla letrab">b</a>
            <a class="tecla letran">n</a>
            <a class="tecla letram">m</a>
            <a class="tecla punto">.</a>
            <a class="tecla arroba">@</a>
            <a class="tecla enter">Ok</a>
          </div>

          <div class="linea4">
            <a class="tecla espacio" style="width: 60%; text-align:center;">Espacio</a>
          </div>
        </div>

        <div class="acciones botones-verticales">
          <a id="btn-siguiente" href="forma-de-pago.html"><button>Siguiente</button></a>
          <a id="btn-extranjero" href="forma-de-pago.html"><button style="background-color: #E0E7EC">Factura Sin Nombre</button></a>
      </div>
    </div><!--Wrapper-->

    <script>
      document.addEventListener('DOMContentLoaded', function () {
          const rucInput = document.getElementById('ruc');
          const razonSocialInput = document.getElementById('razon-social');
          const emailInput = document.getElementById('email');
          const tecladoNumerico = document.querySelector('.teclado-numerico');
          const tecladoRegular = document.querySelector('.teclado-regular');
          const emailLoader = document.getElementById('email-loader');
          const identificadorInput = document.getElementById('identificador');
          const montoInput = document.getElementById('monto');
          const btnSiguiente = document.getElementById('btn-siguiente');
          const btnExtranjero = document.getElementById('btn-extranjero');
    
          let campoActivo = rucInput;
    
          // Mostrar teclado numérico al iniciar
          setTimeout(() => {
              rucInput.focus();
              tecladoNumerico.classList.remove('oculto');
              tecladoRegular.classList.add('oculto');
          }, 1000);
    
          rucInput.addEventListener('focus', () => {
            campoActivo = rucInput;
            tecladoNumerico.classList.remove('oculto');
            tecladoRegular.classList.add('oculto');
            emailInput.value = '';
            razonSocialInput.value = '';
          });
    
          razonSocialInput.addEventListener('focus', async () => {
              campoActivo = razonSocialInput;
              tecladoNumerico.classList.add('oculto');
              tecladoRegular.classList.add('oculto');

              const razonLoader = document.getElementById('razon-loader');
              razonLoader.style.display = 'inline-block';

              const ruc = rucInput.value.trim();

              if (ruc.length > 3) {
                  try {
                      const response = await fetch(`/totem/verificar-email?ruc=${encodeURIComponent(ruc)}`);
                      const data = await response.json();
                      console.log(data.cliente);

                      if (data?.cliente && data.cliente.trim() !== "") {
                          razonSocialInput.value = data.cliente;
                          emailInput.value = data.email;
                          rucInput.value = data.documento;
                      } else {
                          mostrarMensajeError('Número de documento ingresado no valido.');
                          rucInput.focus(); // vuelve a enfocar en RUC
                          razonLoader.style.display = 'none';
                          return; // corta la ejecución
                      }
                  } catch (error) {
                      console.error('Error al verificar razón social:', error);
                      rucInput.focus(); // también enfoca en caso de error
                  }
              }

              razonLoader.style.display = 'none';
              tecladoRegular.classList.remove('oculto');
          });
    
          emailInput.addEventListener('focus', async () => {
              campoActivo = emailInput;
              tecladoNumerico.classList.add('oculto');
              tecladoRegular.classList.add('oculto');
              emailLoader.style.display = 'inline-block';
    
              const ruc = rucInput.value.trim();
    
              if (ruc.length > 3) {
                  try {
                      const response = await fetch(`/totem/verificar-email?ruc=${encodeURIComponent(ruc)}`);
                      const data = await response.json();
                      if (data?.email) {
                          razonSocialInput.value = data.cliente;
                          emailInput.value = data.email;
                          rucInput.value = data.documento;
                      }
                  } catch (error) {
                      console.error('Error al verificar email:', error);
                  }
              }
    
              emailLoader.style.display = 'none';
              tecladoRegular.classList.remove('oculto');
          });
    
          // Teclado numérico
          document.querySelectorAll('.teclado-numerico .tecla').forEach(tecla => {
              tecla.addEventListener('click', () => {
                  const valor = tecla.textContent.trim();
                  if (valor === '' || valor === '←') {
                      campoActivo.value = campoActivo.value.slice(0, -1);
                  } else {
                      campoActivo.value += valor;
                  }
              });
          });
    
          // Teclado regular
          document.querySelectorAll('.teclado-regular .tecla').forEach(tecla => {
              tecla.addEventListener('click', () => {
                  const valor = tecla.textContent.trim();
                  if (valor.toLowerCase() === 'ok') {
                      btnSiguiente.click(); // Ejecutar botón "Siguiente"
                      return;
                  }

                  if (valor.toLowerCase() === 'espacio') {
                      campoActivo.value += ' ';
                      return;
                  }
                  if (valor === '' || valor === '←') {
                      campoActivo.value = campoActivo.value.slice(0, -1);
                  } else {
                      campoActivo.value += valor;
                  }
              });
          });
    
          // Validar antes de avanzar
          btnSiguiente.addEventListener('click', function (e) {
              e.preventDefault();
              const ruc = rucInput.value.trim();
              const razon = razonSocialInput.value.trim();
              const email = emailInput.value.trim();
    
              if (ruc === '' || razon === '' || email === '') {
                  mostrarMensajeError('Completá todos los campos antes de continuar.');
                  return;
              }
    
              if (!esEmailValido(email)) {
                  mostrarMensajeError('El email ingresado no es válido.');
                  return;
              }
    
              const url = `/totem/seleccionar/forma/pago?ruc=${encodeURIComponent(ruc)}&email=${encodeURIComponent(email)}&razon_social=${encodeURIComponent(razon)}&identificador=${identificadorInput.value}&monto=${montoInput.value}`;
              window.location.href = url;
          });
    
          // Extranjero
          btnExtranjero.addEventListener('click', function (e) {
              e.preventDefault();
              const url = `/totem/seleccionar/forma/pago?ruc=&email=&razon_social=&identificador=${identificadorInput.value}&monto=${montoInput.value}`;
              window.location.href = url;
          });
      });
    
      function esEmailValido(email) {
          const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          return regex.test(email);
      }
    
      function mostrarMensajeError(texto) {
          const contenedor = document.getElementById('mensaje-error');
          const textoElemento = document.getElementById('mensaje-error-texto');
          textoElemento.textContent = texto;
          contenedor.classList.remove('oculto');
    
          setTimeout(() => {
              contenedor.classList.add('oculto');
          }, 3000);
      }


      setTimeout(() => {
         window.location.href = "/totem/parking";
      }, 300000);
    </script>
    

    <div id="mensaje-error" class="mensaje-alerta oculto">
      <p id="mensaje-error-texto"></p>
    </div>
      
</body>
</html>