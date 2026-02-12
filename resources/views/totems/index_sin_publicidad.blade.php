<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Estacionamiento Paseo La Galería</title>
  <link rel="stylesheet" href="/totem/css/estilos.css" />
  <link rel="stylesheet" href="/totem/css/splide-core.min.css" />
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow: hidden;
      background-color: #0b1b5d;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: "Futura Extra Black", sans-serif;
    }

    .wrapper {
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      text-decoration: none;
      text-align: center;
    }

    .mensaje {
      font-size: 150px;
      color: white;
      text-shadow: 0px 442px 124px rgba(0, 0, 0, 0.01),
                   0px 283px 113px rgba(0, 0, 0, 0.05),
                   0px 159px 96px rgba(0, 0, 0, 0.16),
                   0px 71px 71px rgba(0, 0, 0, 0.27),
                   0px 18px 39px rgba(0, 0, 0, 0.31);
      opacity: 0;
      animation: fadeIn 2s ease-out forwards, pulse 2s ease-in-out infinite 2s;
    }

    .mensaje strong {
      color: #00ffff;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }

    .flecha {
      margin-top: 40px;
      font-size: 150px;
      color: white;
      animation: bounce 1.5s infinite;
    }

    @keyframes bounce {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(15px);
      }
    }

    .qr-ejemplo img {
      width: 180px;
      margin-top: 20px;
      animation: fadeIn 2s ease-out forwards;
      filter: drop-shadow(0 0 10px #00ffff);
    }

    .nota {
      color: #ccc;
      font-size: 20px;
      margin-top: 10px;
    }

    .logo-dharma {
      position: absolute;
      bottom: 20px;
      right: 20px;
      max-width: 200px;
    }

    .logo-dharma img {
      width: 100%;
    }

    .titulo-principal {
      position: absolute;
      top: 80px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 100px;
      color: #ffffff;
      margin: 0;
      text-shadow: 0px 4px 20px rgba(0, 0, 0, 0.5);
      letter-spacing: 5px;
      text-align: center;
    
    }

  </style>
</head>
<body>
  <div class="wrapper">
<h1 class="titulo-principal">ESTACIONAMIENTO</h1>
    <div class="mensaje">
      Escaneá el <strong><br>código QR<br></strong> de tu <strong>ticket.</strong>
    </div>
    <div class="flecha">⬇️</div>
  </div>

  <div class="logo-dharma">
    <img src="/totem/images/logo-dharma.jpeg" alt="Logo Dharma">
  </div>

<script>
  document.addEventListener("DOMContentLoaded", function () {

    let equipo = localStorage.getItem("equipo");
    enviarPing();
    
    if (!equipo) {
      const nuevoEquipo = prompt("Ingrese el nombre o código del equipo:");
      if (nuevoEquipo) {
        localStorage.setItem("equipo", nuevoEquipo);
        alert("Equipo guardado exitosamente.");
        location.reload();
        return;
      } else {
        alert("Debe ingresar un valor para continuar.");
        return;
      }
    }

    function enviarPing() {
      fetch("/api/ping-equipo", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ equipo }),
      })
      .then(res => {
        if (!res.ok) {
          console.error("Error al enviar ping");
        }
      })
      .catch(err => console.error("Fallo conexión:", err));
    }

    // ⏱ PING cada 5 minutos (300000 ms)
    setInterval(() => {
      enviarPing();
    }, 300000); // 5 minutos

    // 🧾 Captura de código del lector de tickets
    let buffer = "";
    document.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        const codigoTicket = buffer.trim();
        if (codigoTicket !== "") {
          window.location.href = `/totem/validar/ticket/${codigoTicket}`;
        }
        buffer = "";
      } else {
        buffer += e.key;
      }
    });
  });
</script>



</body>
</html>
