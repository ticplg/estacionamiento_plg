<div class="m-t-10 m-b-10 p-l-10 p-r-10 p-t-10 p-b-10">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex flex-column">
                <div class="d-flex">
                    <strong style="min-width: 150px;">Forma Pago:</strong>
                    <span>{{ $entry->tipo_pago }}</span>
                </div>
                <div class="d-flex">
                    <strong style="min-width: 150px;">Asiento Factura:</strong>
                    <span>{{ $entry->respuesta_servicio_factura }}</span>
                </div>
                <div class="d-flex">
                    <strong style="min-width: 150px;">Asiento Pago:</strong>
                    <span>{{ $entry->respuesta_servicio_pago }}</span>
                </div>
                <div class="d-flex">
                    <strong style="min-width: 150px;">Numero Boleta:</strong>
                    <span>{{ $entry->numero_boleta }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="clearfix"></div>
