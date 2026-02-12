@php
    $estado = 'Sin conexión';
    $color = 'danger';
    $icon = 'alert-triangle';
    $ahora = now();

    if ($ultima_conexion) {
        $minutos = $ahora->diffInMinutes($ultima_conexion);
        if ($minutos <= 10) {
            $estado = 'Conectado';
            $color = 'success';
            $icon = 'check-circle';
        } elseif ($minutos <= 60) {
            $estado = 'Inactivo';
            $color = 'warning';
            $icon = 'alert-circle';
        }
    }
@endphp

<div class="col-md-3">
    <div class="card border-left-{{ $color }} shadow h-100 py-2">
        <div class="card-body text-center">
            <div class="h5 mb-1">{{ $nombre }}</div>
            <div class="text-muted mb-2">
                Última conexión:<br>
                {{ optional($ultima_conexion)->format('d/m/Y H:i:s') ?? '---' }}
            </div>
            <div class="badge bg-{{ $color }} px-3 py-2">
                <i class="la la-{{ $icon }}"></i> {{ $estado }}
            </div>
        </div>
    </div>
</div>
