<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.



Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
], function () { // custom admin routes
    Route::crud('registro-estacionamiento', 'RegistroEstacionamientoCrudController');
    Route::crud('registro-estacionamiento-pago', 'RegistroEstacionamientoPagoCrudController');
    Route::crud('configuracion', 'ConfiguracionCrudController');
    Route::crud('historial-factura', 'HistorialFacturaCrudController');
    Route::crud('espacio-estacionamiento', 'EspacioEstacionamientoCrudController');
    Route::crud('historial-marcacion', 'HistorialMarcacionCrudController');
    Route::crud('reporte-registro-estacionamiento', 'ReporteRegistroEstacionamientoCrudController');
    Route::crud('transaction', 'TransactionCrudController');
    Route::crud('descuento-estacionamiento', 'DescuentoEstacionamientoCrudController');
    Route::crud('punto-venta', 'PuntoVentaCrudController');
    Route::crud('evento-descuento', 'EventoDescuentoCrudController');
    Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('backpack.dashboard');


    Route::get('/admin/dashboard/estado-equipos-partial', [\App\Http\Controllers\Admin\DashboardController::class, 'estadoEquiposPartial'])
    ->name('dashboard.estado_equipos_partial');

    Route::get('admin/dashboard/resumen-facturas-partial', [\App\Http\Controllers\Admin\DashboardController::class, 'resumenFacturasPartial'])->name('dashboard.resumen_facturas_partial');

    Route::crud('evento-especial', 'EventoEspecialCrudController');
    Route::crud('cajero-skydata', 'CajeroSkydataCrudController');
    Route::crud('factura-cajero-skydata', 'FacturaCajeroSkydataCrudController');
    Route::crud('ticket-porteria', 'TicketPorteriaCrudController');
    Route::crud('ticket-hotel', 'TicketHotelCrudController');
}); // this should be the absolute last line of this file
