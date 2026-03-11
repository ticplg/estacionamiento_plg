<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiEstacionamiento;
use App\Http\Controllers\AuthController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login_user', [AuthController::class, 'login_user']);
});

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/registrar_porteria', [ApiEstacionamiento::class, 'registrar_porteria']);
    Route::post('/registrar_checkin', [ApiEstacionamiento::class, 'registrar_checkin']);
    Route::post('/registrar_checkout', [ApiEstacionamiento::class, 'registrar_checkout']);
    
    Route::get('/datos_ticket', [ApiEstacionamiento::class, 'datos_ticket']);
});



//Route::post('/estacionamiento', [ApiEstacionamiento::class, 'cardInfo']);
Route::post('/estacionamiento', [ApiEstacionamiento::class, 'rateInfo']);

Route::post('/pagar/estacionamiento', [ApiEstacionamiento::class, 'pagar_estacionamiento']);
Route::post('/generar/factura/estacionamiento', [ApiEstacionamiento::class, 'generar_factura']);
Route::post('/estacionamiento/version/app', [ApiEstacionamiento::class, 'version_app']);

Route::get('/estacionamiento/factura', [ApiEstacionamiento::class, 'obtener_factura']);
Route::get('/estacionamiento/facturas', [ApiEstacionamiento::class, 'obtener_facturas']);
Route::get('/estacionamiento/verificar_ruc', [ApiEstacionamiento::class, 'verificar_ruc']);
Route::get('/estacionamiento/historial/lecturas', [ApiEstacionamiento::class, 'obtener_historial_lecturas']);

Route::post('/estacionamiento/marcar', [ApiEstacionamiento::class, 'marcar_estacionamiento']);
Route::post('/estacionamiento/obtener/marca', [ApiEstacionamiento::class, 'obtener_marca']);
Route::post('/estacionamiento/eliminar/marca', [ApiEstacionamiento::class, 'eliminar_marca']);
Route::post('/estacionamiento/recibirpagos', [ApiEstacionamiento::class, 'recibir_pagos']);
Route::get('/estacionamiento/forma_pagos', [ApiEstacionamiento::class, 'forma_pagos']);

/*Route::get('/insertar/validacion/estacionamiento', [ApiEstacionamiento::class, 'insertar_validacion_electronica']);*/

Route::post('/generar_qr', [ApiEstacionamiento::class, 'generarQRExpress']);
Route::post('/bancard/qr/checkout', [ApiEstacionamiento::class, 'bancardCallback']);
Route::post('/bancard/qr/check/payment', [ApiEstacionamiento::class, 'checkQrBancardPayment']);
Route::post('/bancard/qr/revert', [ApiEstacionamiento::class, 'revertPayment']);


Route::post('/envio_facturas', [ApiEstacionamiento::class, 'envio_facturas']);

Route::get('/estacionamiento/eventos', [ApiEstacionamiento::class, 'eventos']);
Route::post('/estacionamiento/registra/descuento', [ApiEstacionamiento::class, 'registrar_ticket_evento']);
Route::post('/estacionamiento/registra/descuento/proveedores', [ApiEstacionamiento::class, 'registrar_ticket_proveedores']);

Route::get('/estacionamiento/evento_especial', [ApiEstacionamiento::class, 'evento_especial']);
Route::post('/estacionamiento/registra/descuento/especial', [ApiEstacionamiento::class, 'registrar_ticket_evento_especial']);

Route::post('/estacionamiento/registra/descuento/cine', [ApiEstacionamiento::class, 'registra_ticket_cine']);



Route::post('/ping-equipo', function (Request $request) {
    $equipo = $request->input('equipo');

    // Validación básica opcional
    if (!$equipo) {
        return response()->json(['error' => 'Nombre del equipo es requerido'], 400);
    }

    $puntoVenta = \App\Models\PuntoVenta::where('nombre_punto_venta', $equipo)->first();

    if ($puntoVenta) {
        $puntoVenta->ultima_conexion = now();
        $puntoVenta->save();
    }

    return response()->json(['status' => 'ok']);
});