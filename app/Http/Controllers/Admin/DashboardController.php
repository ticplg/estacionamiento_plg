<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PuntoVenta;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $puntos = PuntoVenta::orderBy('nombre_punto_venta')
            ->where('nombre_punto_venta', '!=', 'APP')
            ->get();

        $procesadas = \App\Models\HistorialFactura::where('estado_factura', 'Aprobado')->count() +
        \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Aprobado')->count();
        $pendientes = \App\Models\HistorialFactura::where('estado_factura', 'Pendiente')->count() +  \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Pendiente')->count();
        $rechazadas = \App\Models\HistorialFactura::where('estado_factura', 'Rechazado')->count() + \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Rechazado')->count();

        return view('admin.dashboard_custom', compact('puntos', 'procesadas', 'pendientes', 'rechazadas'));
    }

    public function estadoEquiposPartial()
    {
        $puntos = PuntoVenta::orderBy('nombre_punto_venta')
            ->where('nombre_punto_venta', '!=', 'APP')
            ->get();

        return view('admin.dashboard._estado_equipos', compact('puntos'));
    }

    public function resumenFacturasPartial()
    {
        $procesadas = \App\Models\HistorialFactura::where('estado_factura', 'Aprobado')->count() +
        \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Aprobado')->count();
        $pendientes = \App\Models\HistorialFactura::where('estado_factura', 'Pendiente')->count() +  \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Pendiente')->count();
        $rechazadas = \App\Models\HistorialFactura::where('estado_factura', 'Rechazado')->count() + \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Rechazado')->count();

        return view('admin.dashboard._resumen_facturas', compact('procesadas', 'pendientes', 'rechazadas'));
    }
}

