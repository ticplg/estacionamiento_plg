<!DOCTYPE html>
<html>
<head>
    <title>Reporte</title>
    <style>
        /* Estilos para el loader */
        .loader {
            border: 8px solid rgba(0, 0, 0, 0.1);
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 100px;
            height: 100px;
            animation: spin 1.5s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Estilo para el mensaje */
        .loader-text {
            position: absolute;
            top: calc(50% + 80px); /* Ajuste la posición para estar debajo del spinner */
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
            font-family: Arial, sans-serif;
            color: #2c3e50;
            text-align: center;
            font-weight: bold;
        }

        /* Ocultar el loader y el mensaje después de cargar */
        .hidden {
            display: none;
        }

        /* Mantener visible el spinner y el texto en la espera */
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f3f3f3;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            downloadReport();
        });

        function downloadReport() {
            const urlReporte = {!! json_encode($url_reporte) !!};
            const redirectUrl = {!! json_encode($url_redirect) !!};

            // Mostrar el loader y el mensaje
            document.getElementById('loader').style.display = 'block';
            document.getElementById('loader-text').style.display = 'block';

            fetch(urlReporte)
                .then(response => {
                    if (response.ok) {
                        // Abre el archivo en una nueva pestaña
                        response.blob().then(blob => {
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = ''; // Puedes definir un nombre de archivo aquí si es necesario
                            document.body.appendChild(a);
                            a.click();
                            a.remove();

                            // Espera 3 segundos antes de redirigir (suficiente para la descarga)
                            setTimeout(() => {
                                window.location.href = redirectUrl;
                            }, 3000); // Retraso de 3 segundos
                        });
                    } else {
                        alert('Error generando el reporte');
                    }
                })
                .catch(error => console.error('Error:', error))
                .finally(() => {
                    // El loader y el mensaje se mantendrán visibles durante los 3 segundos de espera
                    setTimeout(() => {
                        // Ocultar el loader después del redireccionamiento
                        document.getElementById('loader').style.display = 'none';
                        document.getElementById('loader-text').style.display = 'none';
                    }, 3000); // Ocultar después de 3 segundos
                });
        }
    </script>
</head>
<body>
    <!-- Loader -->
    <div id="loader" class="loader"></div>
    <!-- Mensaje -->
    <div id="loader-text" class="loader-text">Generando reporte, por favor aguarde...</div>
</body>
</html>
