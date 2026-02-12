<div class="col-md-4">
    <div class="card border-left-success shadow h-100 py-2">
        <div class="card-body text-center">
            <h5 class="text-success font-weight-bold">Facturas Procesadas</h5>
            <h2 class="mb-0">{{ $procesadas ?? 0 }}</h2>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card border-left-warning shadow h-100 py-2">
        <div class="card-body text-center">
            <h5 class="text-warning font-weight-bold">Facturas Pendientes</h5>
            <h2 class="mb-0">{{ $pendientes ?? 0 }}</h2>
        </div>
    </div>
</div>
<div class="col-md-4">
    <div class="card border-left-danger shadow h-100 py-2">
        <div class="card-body text-center">
            <h5 class="text-danger font-weight-bold">Facturas Rechazadas</h5>
            <h2 class="mb-0">{{ $rechazadas ?? 0 }}</h2>
        </div>
    </div>
</div>
