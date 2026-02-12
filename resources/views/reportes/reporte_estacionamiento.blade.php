<table>
    <thead>
        <tr>
            <th>Identificador</th>
            <th>ID Usuario</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Fecha Lectura</th>
            <th>Hora Lectura</th>
            <th>Precio</th>
            <th>Duración Estacionamiento</th>
        </tr>
    </thead>
    <tbody>
        @foreach($registros as $registro)
        <tr>
            <td>{{ $registro->identificador }}</td>
            <td>{{ $registro->user_app_id }}</td>
            <td>{{ $registro->user_first_name }}</td>
            <td>{{ $registro->user_last_name }}</td>
            <td>{{ $registro->fecha_lectura }}</td>
            <td>{{ $registro->hora_lectura }}</td>
            <td>{{ $registro->price }}</td>
            <td>{{ $registro->parking_duration }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
