@forelse ($puntos as $punto)
    @php
        $estado = 'Sin conexión';
        $color = 'danger';
        $icon = 'alert-triangle';
        $ahora = now();

        if ($punto->ultima_conexion) {
            $minutos = $ahora->diffInMinutes($punto->ultima_conexion);
            if ($minutos <= 5) {
                $estado = 'Conectado';
                $color = 'success';
                $icon = 'check-circle';
            }
        }
    @endphp

    <div class="col-md-3">
        <div class="card border-left-{{ $color }} shadow mb-4">
            <div class="card-body text-center">
                <h5 class="mb-1">{{ strtoupper($punto->nombre_punto_venta) }}</h5>
                <div class="text-muted mb-2">
                    Última conexión:<br>
                    {{ $punto->ultima_conexion ? date('d-m-Y H:i:s', strtotime($punto->ultima_conexion)) : '—' }}
                </div>
                <div class="badge bg-{{ $color }} px-3 py-2">
                    <i class="la la-{{ $icon }}"></i> {{ $estado }}
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="col-12">
        <div class="alert alert-warning">No hay equipos registrados.</div>
    </div>
@endforelse


