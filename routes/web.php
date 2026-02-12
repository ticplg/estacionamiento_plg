<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParkingTottem;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ApiEstacionamiento;
use App\Http\Controllers\RefacturacionController;
use App\Http\Controllers\Admin\HistorialFacturaCrudController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/admin');
});
Route::get('/procesar_reporte', [ReporteController::class, 'procesar_reporte']);
Route::get('/generar_pdf', [ReporteController::class, 'generar_pdf']);
Route::get('/reporte_estacionamiento/{id}', [ReporteController::class, 'reporte_estacionamiento']);

Route::post('/checkout', [ReporteController::class, 'checkout']);
Route::post('/check-out', [ReporteController::class, 'checkout']);


Route::get('/verificar_pagos_qr', [ReporteController::class, 'verificar_pagos_qr']);

Route::get('/asignar_qr_cliente', [ReporteController::class, 'asignar_qr_cliente']);

Route::get('/admin/search/cliente', [HistorialFacturaCrudController::class, 'search_cliente']);
Route::get('/admin/search/transacciones', [HistorialFacturaCrudController::class, 'transacciones']);

Route::get('/parking/totem', function () {
    return redirect('/totem/parking');
});


Route::get('/parking/pruebas', [ApiEstacionamiento::class, 'descuento_parking_v2']);

Route::get('/parking/validar/factura', [ApiEstacionamiento::class, 'verificar_estado_factura']);


Route::get('/generar/xml', [ReporteController::class, 'generar_xml']);


Route::get('/totem/parking', [ParkingTottem::class, 'totem_inicio']);
Route::get('/totem/resultado/exito', [ParkingTottem::class, 'totem_gracias']);
Route::get('/totem/verificar-email', [ParkingTottem::class, 'verificar_email']);
Route::get('/totem/validar/ticket/{identificado}', [ParkingTottem::class, 'validar_ticket']);
Route::get('/totem/validar/pagocero', [ParkingTottem::class, 'validar_ticket_pago_cero']);
Route::get('/totem/informacion/identificador/{identificado}', [ParkingTottem::class, 'rateInfo']);
Route::get('/totem/datos-factura', [ParkingTottem::class, 'datos_factura']);
Route::get('/totem/seleccionar/forma/pago', [ParkingTottem::class, 'seleccionar_forma_pago']);
Route::get('/totem/pago-tarjeta', [ParkingTottem::class, 'pago_tarjeta']);
Route::get('/totem/pago-qr', [ParkingTottem::class, 'pago_qr']);

Route::get('/dinamic', [ParkingTottem::class, 'dinamic']);



Route::post('/totem/iniciar-proceso-pago/tarjeta', [ParkingTottem::class, 'pago_tarjeta_pos']);


Route::get('/admin/factura/{id}/reenviar', [HistorialFacturaCrudController::class, 'reenviarMegaprint'])->name('factura.volver_enviar');



Route::get('/admin/anular-factura', [RefacturacionController::class, 'anular_factura'])->name('factura.anular');


