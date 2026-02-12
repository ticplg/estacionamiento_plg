<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago</title>
</head>
<body>
    <h1>Gracias por tu pago</h1>
    <p>Estimado {{ $data['nombre'] }},</p>
    <p>Hemos recibido tu pago de <strong>{{ $data['monto'] }}</strong>.</p>
    <p>Detalles del recibo:</p>
    <ul>
        <li>Fecha: {{ $data['fecha'] }}</li>
        <li>Referencia: {{ $data['referencia'] }}</li>
    </ul>
    <p>Gracias por confiar en nosotros.</p>
</body>
</html>
