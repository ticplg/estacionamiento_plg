@extends(backpack_view('blank'))

@section('header')
@endsection

@section('content')
    {{-- Tarjetas de resumen --}}
    <section class="content-header">
        <h1>
            <small>Facturas</small>
        </h1>
    </section>
    <div id="resumen-facturas" class="row mb-4">
        @include('admin.dashboard._resumen_facturas')
    </div>

    {{-- Estado de equipos --}}
    <section class="content-header">
        <h1>
            <small>Estado de Equipos</small>
        </h1>
    </section>
    <div id="estado-equipos" class="row">
        @include('admin.dashboard._estado_equipos')
    </div>

    <script>
        function actualizarEstadoEquipos() {
            // Actualizar estado de equipos
            fetch("{{ route('dashboard.estado_equipos_partial') }}")
                .then(response => response.text())
                .then(html => {
                    document.getElementById("estado-equipos").innerHTML = html;
                })
                .catch(error => {
                    console.error("Error al actualizar el estado de equipos:", error);
                });

            // Actualizar resumen de facturas
            fetch("{{ route('dashboard.resumen_facturas_partial') }}")
                .then(response => response.text())
                .then(html => {
                    document.getElementById("resumen-facturas").innerHTML = html;
                })
                .catch(error => {
                    console.error("Error al actualizar el resumen de facturas:", error);
                });
        }

        document.addEventListener("DOMContentLoaded", () => {
            setInterval(actualizarEstadoEquipos, 60000); // cada 3 minutos
        });
    </script>
@endsection
