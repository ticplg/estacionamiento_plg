<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use Storage;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\ReporteRegistroEstacionamiento as ReporteEstacionamiento;
use App\Models\RegistroEstacionamiento;
use App\Models\BancardTransaction;
use App\Models\EventoDescuento;
use App\Models\QRTransaction;
use App\Models\TransaccionTarjetaTotem;
use App\Models\RegistroEstacionamientoPago;
use App\Models\HistorialFactura;
use App\Models\RegistroDescuentoAplicado;
use App\Models\FacturaCajeroSkydata;
use App\Exports\ReporteEstacionamientoExport;
use App\Exports\ReporteTicketsPagadosExport;
use App\Exports\ReporteTicketsValidadosZonaFitExport;
use App\Exports\FacturaCajeroSkydataExport;
use App\Exports\ReporteTicketsHotelExport;
use App\Exports\ReporteTicketsEventoDescuentoExport;
use App\Exports\TransaccionesPendientesExport;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{


    public function procesar_reporte(Request $request)
    {
        $url_redirect = $request->redirect;
        $url_reporte = $request->url_reporte;
        return view ('reportes.reporte', compact('url_reporte', 'url_redirect'));
    }

    public function reporte_estacionamiento($id)
    {
        $memoriaOriginal = ini_get('memory_limit');
        $reporte_estacionamiento = ReporteEstacionamiento::find($id);

        if ($reporte_estacionamiento->tipo_reporte == 'reporte_lectura_tickets') {
            $registros = RegistroEstacionamiento::where('fecha_lectura', '>=', $reporte_estacionamiento->fecha_desde)
                ->where('fecha_lectura', '<=', $reporte_estacionamiento->fecha_hasta)
                ->get();
    
            return Excel::download(new ReporteEstacionamientoExport($registros), 'reporte_estacionamiento.xlsx');
        }
        elseif($reporte_estacionamiento->tipo_reporte == 'reporte_lectura_pagos')
        {
            ini_set('memory_limit', '512M');
            $registros = DB::table('historial_facturas as b')
            ->select(
                'b.identificador',
                'b.monto_factura',
                'b.fecha_factura',
                'b.numero_factura',
                'b.ticket_number',
                'b.punto_venta',
                'b.cdc',
                'b.estado_factura',
                DB::raw('UPPER(b.razon_social) as razon_social'),
                'b.documento',
                DB::raw("CASE 
                    WHEN b.transaccion_tarjeta_id != 0 OR b.transaccion_tarjeta_totem_id != 0 THEN 'TARJETA'
                    WHEN b.transaccion_qr_id != 0 THEN 'QR'
                    ELSE NULL
                END as forma_pago"),
                DB::raw("CASE 
                    WHEN b.tipo_tarjeta IN ('credit', 'card', 'TC') THEN 'CREDITO'
                    WHEN b.tipo_tarjeta IN ('debit', 'DC', 'TD') THEN 'DEBITO'
                    WHEN b.tipo_tarjeta = 'NN' THEN 'NO DETERMINADO'
                    ELSE 'NO DETERMINADO'
                END as tipo_tarjeta")
            )
            ->where('b.fecha_factura', '>=', $reporte_estacionamiento->fecha_desde)
            ->where('b.fecha_factura', '<=', $reporte_estacionamiento->fecha_hasta)
            ->orderBy('b.fecha_factura', 'asc')
            ->get();

            //ini_set('memory_limit', $memoriaOriginal);

            return Excel::download(new ReporteTicketsPagadosExport($registros), 'reporte_tickets_pagados.xlsx');
        }
        elseif($reporte_estacionamiento->tipo_reporte == 'reporte_validados_zf')
        {

            $registros = DB::table('registro_descuento_aplicados as a')
            ->leftJoin('historial_facturas as b', 'a.identificador', '=', 'b.identificador')
            ->select([
                'a.fecha',
                'a.zf_cliente_id',
                'a.codigo_descuento',
                'a.zf_cliente_cedula',
                'a.zf_cliente_nombre',
                'a.zf_cliente_apellido',
                'a.zf_tipo_plan',
                'a.identificador',
                'a.duracion',
                'b.numero_factura',
                'b.monto_factura'
            ])
            ->where('fecha', '>=', $reporte_estacionamiento->fecha_desde)
            ->where('fecha', '<=', $reporte_estacionamiento->fecha_hasta)
            ->get();


            return Excel::download(new ReporteTicketsValidadosZonaFitExport($registros), 'reporte_tickets_pagados.xlsx');
        }
        elseif($reporte_estacionamiento->tipo_reporte == 'reporte_salon_eventos')
        {
            $datos = DB::table('ticket_evento_descuentos as a')
            ->join('evento_descuentos as b', 'a.evento_id', '=', 'b.id')
            ->select([
                'b.nombre_evento',
                DB::raw("
                    CASE 
                        WHEN b.tipo_evento = 'jornada_completa' THEN 'JORNADA COMPLETA'
                        ELSE 'MEDIA JORNADA'
                    END AS tipo_evento
                "),
                DB::raw("DATE_FORMAT(b.fecha_hora_inicio, '%d/%m/%Y %H:%i:%s') as fecha_hora_inicio"),
                DB::raw("DATE_FORMAT(b.fecha_hora_fin, '%d/%m/%Y %H:%i:%s') as fecha_hora_fin"),
                'a.identificador',
                DB::raw("DATE_FORMAT(a.fecha_hora_validacion, '%d/%m/%Y %H:%i:%s') as fecha_hora_validacion"),
                'b.cantidad_validaciones_disponibles as cantidad',
                DB::raw("
                    CASE 
                        WHEN b.tarifado = '0' THEN 'EXONERADO'
                        ELSE 'TARIFADO'
                    END AS es_tarifado
                "),
                'a.monto_ticket',
            ])->where('b.fecha_hora_inicio', '>=', $reporte_estacionamiento->fecha_desde . ' 00:00:00')->where('b.fecha_hora_inicio', '<=', $reporte_estacionamiento->fecha_hasta . ' 23:59:59')
            ->get();

            return Excel::download(new ReporteTicketsEventoDescuentoExport($datos), 'tickets_evento_descuentos.xlsx');

        }elseif($reporte_estacionamiento->tipo_reporte == 'reporte_cajero_skydata')
        {
            $memoriaOriginal = ini_get('memory_limit');
            ini_set('memory_limit', '2048M');
            $cajero_skydata = DB::table('factura_cajero_skydatas as a')
            ->leftJoin('cajero_skydatas as b', 'a.cajero_id', '=', 'b.id')
            ->select([
                'a.fecha_emision',
                'a.ruc_receptor',
                'a.nombre_receptor',
                'a.total',
                'a.timbrado',
                'a.numero_factura',
                DB::raw("COALESCE(b.nombre_cajero, 'No definido') as nombre_cajero"),
                'a.cdc',
                'a.respuesta_servicio_factura',
                'a.respuesta_servicio_pago',
                'a.tipo_pago',
                'a.numero_boleta',
            ])
            ->where("fecha_emision", '>=', $reporte_estacionamiento->fecha_desde)
            ->where("fecha_emision", '<=', $reporte_estacionamiento->fecha_hasta)
            ->orderBy('a.fecha_emision', 'asc')
            ->get();

            return Excel::download(new FacturaCajeroSkydataExport($cajero_skydata), 'factura_cajero_skydata.xlsx');
        }elseif($reporte_estacionamiento->tipo_reporte == 'reporte_pagos_pendiente_factura')
        {
            $data = [];

            // 🔹 Bancard transacciones tarjeta (APP)
            $transacciones_tarjeta_app = BancardTransaction::select('created_at', 'response_data', 'amount')
                ->where('factura_id', 0)
                ->where('status', 'Success')
                ->get();

            foreach ($transacciones_tarjeta_app as $t) {
                $confirm = json_decode($t->response_data)->confirmation ?? null;

                $data[] = [
                    "fecha" => $t->created_at->format('Y-m-d'),
                    "cod_autorizacion" => $confirm->authorization_number ?? '',
                    "ticket_number" => $confirm->ticket_number ?? '',
                    "monto" => $t->amount,
                    "origen" => 'TARJETA',
                ];
            }

            // 🔹 Transacciones tarjeta totem (sin factura)
            $transaccion_tarjeta_totem = TransaccionTarjetaTotem::select('created_at', 'codigo_autorizacion', 'numero_boleta', 'monto')
                ->where('factura_id', 0)
                ->get();

            foreach ($transaccion_tarjeta_totem as $t) {
                $data[] = [
                    "fecha" => $t->created_at->format('Y-m-d'),
                    "cod_autorizacion" => $t->codigo_autorizacion,
                    "ticket_number" => ltrim($t->numero_boleta, '0'),
                    "monto" => $t->monto,
                    "origen" => 'TARJETA',
                ];
            }

            // 🔹 Transacciones QR confirmadas (sin factura)
            $transaccion_qrs = QRTransaction::select('fecha_hora', 'authorization_code', 'ticket_number', 'amount')
                ->where('factura_id', 0)
                ->where('status', 'confirmed')
                ->get();

            foreach ($transaccion_qrs as $q) {
                $data[] = [
                    "fecha" => \Carbon\Carbon::parse($q->fecha_hora)->format('Y-m-d'),
                    "cod_autorizacion" => $q->authorization_code,
                    "ticket_number" => ltrim($q->ticket_number, '0'),
                    "monto" => $q->amount,
                    "origen" => 'QR',
                ];
            }

            // 🔚 Ordenar por fecha descendente y devolver como Collection
            $datos = collect($data)
                ->where("fecha", '>=', $reporte_estacionamiento->fecha_desde)
                ->where("fecha", '<=', $reporte_estacionamiento->fecha_hasta)
                ->sortBy('fecha')
                ->values(); 
            ini_set('memory_limit', $memoriaOriginal);
            
            return Excel::download(new TransaccionesPendientesExport($datos), 'transacciones_pendientes.xlsx');

        }
        elseif($reporte_estacionamiento->tipo_reporte == 'facturas_rechazadas_sifen')
        {
            $registros = DB::table('historial_facturas as b')
            ->select(
                'b.identificador',
                'b.monto_factura',
                'b.fecha_factura',
                'b.numero_factura',
                'b.ticket_number',
                'b.punto_venta',
                'b.cdc',
                'b.estado_factura',
                DB::raw('UPPER(b.razon_social) as razon_social'),
                'b.documento',
                DB::raw("CASE 
                    WHEN b.transaccion_tarjeta_id != 0 OR b.transaccion_tarjeta_totem_id != 0 THEN 'TARJETA'
                    WHEN b.transaccion_qr_id != 0 THEN 'QR'
                    ELSE NULL
                END as forma_pago"),
                DB::raw("CASE 
                    WHEN b.tipo_tarjeta IN ('credit', 'card', 'TC') THEN 'CREDITO'
                    WHEN b.tipo_tarjeta IN ('debit', 'DC', 'TD') THEN 'DEBITO'
                    WHEN b.tipo_tarjeta = 'NN' THEN 'NO DETERMINADO'
                    ELSE 'NO DETERMINADO'
                END as tipo_tarjeta")
            )
            ->where('b.fecha_factura', '>=', $reporte_estacionamiento->fecha_desde)
            ->where('b.fecha_factura', '<=', $reporte_estacionamiento->fecha_hasta)
            ->where('b.estado_factura', 'Rechazado')
            ->orderBy('b.fecha_factura', 'asc')
            ->get();

            return Excel::download(new ReporteTicketsPagadosExport($registros), 'reporte_tickets_pagados.xlsx');
        }
        elseif($reporte_estacionamiento->tipo_reporte == 'reporte_ticket_hotel')
        {
            $registros = DB::table('ticket_hotels as a')
            ->select(
                'a.identificador',
                DB::raw('UPPER(a.nombre_huesped) as nombre_huesped'),
                'a.habitacion',
                'a.fecha_checkin',
                'a.hora_checkin',
                'a.fecha_checkout',
                'a.hora_checkout',
                'a.monto_en_checkout'
            )
            ->whereDate('a.fecha_checkin', '>=', $reporte_estacionamiento->fecha_desde)
            ->whereDate('a.fecha_checkin', '<=', $reporte_estacionamiento->fecha_hasta)
            ->orderBy('a.fecha_checkin', 'asc')
            ->get();

        return Excel::download(new ReporteTicketsHotelExport($registros), 'reporte_tickets_hotel.xlsx');
        }
    }

    public function checkout(Request $request) {
        return response()->json(
            [
                'code' => 200,
                'mensaje' => "Transaccion Aprobado",
            ]
        );
    }

    public function generar_pdf()
    {
        $facturas = HistorialFactura::whereNull('path_factura')->get();
    
        foreach ($facturas as $venta) {
            // Generar el PDF
            $pdf = PDF::loadView('documentos_emitidos.factura_pdf', compact('venta'))
                ->setPaper([0, 0, 400.77, 900.89], 'portrait');
    
            // Definir el path público donde se guardará el archivo
            $fileName = $venta->numero_factura . '.pdf';
            $path = 'facturas/' . $fileName;  // Ruta relativa dentro de 'storage/app/public'
    
            // Guardar el PDF en 'storage/app/public/facturas/'
            $pdf->save(storage_path('app/public/' . $path));
    
            // Guardar la ruta en la base de datos (pública)
            $venta->path_factura = 'storage/' . $path;
            $venta->save();
        }
    
        //return response()->json(['message' => 'Facturas generadas y guardadas con éxito']);
    }
    
    public function verificar_pagos_qr()
    {
        /*$operaciones = QRTransaction::where('status', 'confirmed')
        ->whereBetween('fecha_hora', [Carbon::yesterday()->startOfDay(), Carbon::today()->endOfDay()])
        ->get();*/
        $operaciones = QRTransaction::where('status', 'confirmed')
        ->where('fecha_hora', '>=', '2025-08-01 00:00:00')
        ->where('factura_id', 0)
        ->get();

        $cantidad = 0;
        foreach($operaciones as $operacion)
        {
            $historico =  HistorialFactura::where('transaccion_qr_id', $operacion->id)->first();
            if(!$historico)
            {
                if($operacion->created_at >= '2024-02-06 00:00:00')
                {
                    $factura = new HistorialFactura;
                    $factura->identificador = isset($operacion->identificador) ? $operacion->identificador : rand(1, 99999999);
                    $factura->monto_factura = $operacion->amount;
                    $factura->fecha_factura = date('Y-m-d');
                    $factura->documento = ($operacion->ruc_cliente  == '88888801-5') ? '44444401-7' : $operacion->ruc_cliente;
                    $factura->razon_social = ($operacion->ruc_cliente  == '88888801-5') ? 'Cliente Ocasional' : $operacion->nombre_cliente;
                    $factura->user_id = $operacion->usuario_id;
                    $factura->transaccion_qr_id =  $operacion->id;
                    $factura->authorization_number = $operacion->authorization_code;
                    $factura->ticket_number  = $operacion->ticket_number;
                    $factura->save();

                    $operacion->factura_id = $factura->id;
                    $operacion->save();

                    $cantidad = $cantidad + 1;
                }
            }
            else
            {
                $operacion->factura_id = $historico->id;
                $operacion->save();
            }
        }

        $historico =  HistorialFactura::where('identificador', '0')->get();

        foreach($historico as $his)
        {
            $registro = new RegistroEstacionamientoPago;
            $registro->identificador = $his->id;
            $registro->price = $his->monto_factura;
            $registro->user_id = $his->user_id;
            $registro->user_name = 'SIN DATOS';
            $registro->user_lastname = 'SIN DATOS';
            $registro->fecha_pago = $his->fecha_factura;
            $registro->hora_pago = date('H:i:s', strtotime($his->created_at));
            $registro->save();
            
            $his->identificador = $his->id;
            $his->save();
        }


        $tarjetas = BancardTransaction::where('status', 'Success')
        ->where('created_at', '>=', '2025-09-01 00:00:00')
        ->where('factura_id', 0)
        ->get();

        foreach($tarjetas as $tarjeta)
        {

            $historico = HistorialFactura::where('transaccion_tarjeta_id', $tarjeta->id)
            ->first();

            if(!$historico)
            {
                $historico =  HistorialFactura::where('user_id', $tarjeta->usuario_id)
                ->orderBy('id', 'DESC')
                ->first();

                $razon_social = "Cliente Ocasional";
                $documento = "44444401-7";

                if($historico)
                {
                    $razon_social = $historico->razon_social;
                    $documento = $historico->documento;
                }

                $factura = new HistorialFactura;
                $factura->identificador = $tarjeta->id . 100;
                $factura->monto_factura = $tarjeta->amount;
                $factura->fecha_factura = date('Y-m-d');
                $factura->documento = $documento;
                $factura->razon_social = $razon_social;
                $factura->user_id = $tarjeta->usuario_id;
                $factura->transaccion_tarjeta_id =  $tarjeta->id;
                $factura->authorization_number = json_decode($tarjeta->response_data)->confirmation->authorization_number;
                $factura->ticket_number = json_decode($tarjeta->response_data)->confirmation->ticket_number;
                $factura->save();

                $tarjeta->factura_id = $factura->id;
                $tarjeta->save();
            }
            else
            {
                $tarjeta->factura_id = $historico->id;
                $tarjeta->save();
            }
        }

        $tarjeta_totem = TransaccionTarjetaTotem::where('created_at', '>=', '2025-08-01 00:00:00')
        ->where('factura_id', 0)
        ->get();

        foreach($tarjeta_totem as $tarjeta)
        {

            $historico = HistorialFactura::where('transaccion_tarjeta_totem_id', $tarjeta->id)
            ->first();

            if(!$historico)
            {
                $historico =  HistorialFactura::where('user_id', $tarjeta->usuario_id)
                ->orderBy('id', 'DESC')
                ->first();

                $razon_social = "Cliente Ocasional";
                $documento = "44444401-7";

                /*if($historico)
                {
                    $razon_social = $historico->razon_social;
                    $documento = $historico->documento;
                }*/

                $factura = new HistorialFactura;
                $factura->identificador = $tarjeta->id . 100;
                $factura->monto_factura = $tarjeta->monto;
                $factura->fecha_factura = date('Y-m-d');
                $factura->documento = $documento;
                $factura->razon_social = $razon_social;
                $factura->user_id = 0;
                $factura->transaccion_tarjeta_totem_id =  $tarjeta->id;
                $factura->authorization_number = $tarjeta->codigo_autorizacion;
                $factura->ticket_number =  $tarjeta->numero_boleta;
                $factura->save();

                $tarjeta->factura_id = $factura->id;
                $tarjeta->save();
            }
            else
            {
                $tarjeta->factura_id = $historico->id;
                $tarjeta->save();
            }
        }
    }

    public function generar_xml()
    {
        $path = public_path('archivos/febrero_pendientes.xlsx');
        $data = Excel::toCollection(null, $path);

        // Solo primera hoja
        $rows = $data[0];

        // Omitir la primera fila (cabecera)
        foreach ($rows as $row) {
            // Ahora $row es una fila de datos (sin la cabecera)
            //return $row;
            $xmlContent = $this->generateXmlContent($row[0], $row[8], $row[15], $row[16], $row[5], $row[3]);
            $nombre = time().'_'.$row[0];

            $filePath = 'facturas_reenviar/xml_files/'.$nombre.'.xml'; // Define la ruta donde guardar el archivo
            Storage::disk('public')->put($filePath, $xmlContent["xml"]);
            Storage::disk('local')->put($filePath, $xmlContent["xml"]);  
        }
    }

    private function generateXmlContent($numero_factura, $fecha, $cdc, $monto, $cliente, $documento)
    {
        $codigo_establecimiento = explode('-', $numero_factura)[0];
        $codigo_sucursal = explode('-', $numero_factura)[1];
        $factura = explode('-', $numero_factura)[2];
        $anho = explode('/',$fecha)[2];
        $mes =  explode('/',$fecha)[1];
        $dia =  explode('/',$fecha)[0];
        $dId = '5637173832';

        $DE_Id = $cdc;
        $dDVId = $ultimo = substr($cdc, -1);
        $dFecFirma =  date('Y-m-d\TH:i:s');
        $dCodSeg = '999758768';
        $dNumTim = '17600887'; //17600887
        $dNumDoc = $factura;
        $dFeIniT = '2024-11-01';
        $dFeEmiDE = $fecha;
        $dNomEmi = 'DAYLIC SOCIEDAD ANÓNIMA';
        $dDirEmi = 'SANTA TERESA ENTRE AVIADORES DEL CHACO Y HERMINIO MALDONADO';
        $dTelEmi = '0216594000';
        $dEmailE = 'e.santos@megaprint.com.gt';
        $dNomRec = $cliente;
        $dCodCliente = '000001';
        $dCantProSer = '1';
        $dTotBruOpeItem = $monto;
        $dBasGravIVA = (str_replace('.', '', $monto) - round(str_replace('.', '', $monto) / 11));
        $dLiqIVAItem = round(str_replace('.', '', $monto) / 11);
        $dTotGralOpe = $monto;
        $dTotIVA = round(str_replace('.', '', $monto) / 11);

        if($documento == '44444401-7' || $documento == "88888801-5")
        {
            $gDatRec = <<<XML
            <gDatRec>
                <iNatRec>2</iNatRec>
                <iTiOpe>2</iTiOpe>
                <cPaisRec>PRY</cPaisRec>
                <dDesPaisRe>Paraguay</dDesPaisRe>
                <iTipIDRec>5</iTipIDRec>
                <dDTipIDRec>Innominado</dDTipIDRec>
                <dNumIDRec>0</dNumIDRec>
                <dNomRec>Sin Nombre</dNomRec>
                <dCodCliente>000003</dCodCliente>
            </gDatRec>
            XML;
        }
        elseif(count(explode('-', $documento)) == 2)
        {
            $ruc = explode('-', $documento)[0];
            $dv_ruc = explode('-', $documento)[1];
        
            $gDatRec = <<<XML
            <gDatRec>
                <iNatRec>1</iNatRec>
                <iTiOpe>2</iTiOpe>
                <cPaisRec>PRY</cPaisRec>
                <dDesPaisRe>Paraguay</dDesPaisRe>
                <iTiContRec>2</iTiContRec>
                <dRucRec>$ruc</dRucRec>
                <dDVRec>$dv_ruc</dDVRec>
                <dNomRec>$dNomRec</dNomRec>
                <dCodCliente>$dCodCliente</dCodCliente>
            </gDatRec>
            XML;
        
        }
        else
        {
            $gDatRec = <<<XML
            <gDatRec>
                <iNatRec>2</iNatRec>
                <iTiOpe>2</iTiOpe>
                <cPaisRec>PRY</cPaisRec>
                <dDesPaisRe>Paraguay</dDesPaisRe>
                <iTipIDRec>1</iTipIDRec>
                <dDTipIDRec>Cédula paraguaya</dDTipIDRec>
                <dNumIDRec>$documento</dNumIDRec>
                <dNomRec>$dNomRec</dNomRec>
                <dCodCliente>$dCodCliente</dCodCliente>
            </gDatRec>
            XML;
        }

        // Contenido XML con placeholders
        $xmlContent = <<<XML
        <rEnviDe xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://ekuatia.set.gov.py/sifen/xsd">
        <dId>$dId</dId>
        <xDE>
            <rDE xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd/ siRecepDE_v150.xsd">
            <dVerFor>150</dVerFor>
            <DE Id="$DE_Id">   
                <dDVId>$dDVId</dDVId>
                <dFecFirma>$dFecFirma</dFecFirma>
                <dSisFact>1</dSisFact>
                <gOpeDE>
                <iTipEmi>1</iTipEmi>
                <dDesTipEmi>Normal</dDesTipEmi>
                <dCodSeg>$dCodSeg</dCodSeg>
                </gOpeDE>
                <gTimb>
                <iTiDE>1</iTiDE>
                <dDesTiDE>Factura electrónica</dDesTiDE>
                <dNumTim>$dNumTim</dNumTim>
                <dEst>$codigo_establecimiento</dEst>
                <dPunExp>$codigo_sucursal</dPunExp>
                <dNumDoc>$dNumDoc</dNumDoc>
                <dFeIniT>$dFeIniT</dFeIniT>
                </gTimb>
                <gDatGralOpe>
                <dFeEmiDE>$dFeEmiDE</dFeEmiDE>
                <gOpeCom>
                    <iTipTra>2</iTipTra>
                    <dDesTipTra>Prestación de servicios</dDesTipTra>
                    <iTImp>1</iTImp>
                    <dDesTImp>IVA</dDesTImp>
                    <cMoneOpe>PYG</cMoneOpe>
                    <dDesMoneOpe>Guarani</dDesMoneOpe>
                </gOpeCom>
                <gEmis>
                    <dRucEm>80128957</dRucEm>
                    <dDVEmi>2</dDVEmi>
                    <iTipCont>2</iTipCont>
                    <dNomEmi>$dNomEmi</dNomEmi>
                    <dDirEmi>$dDirEmi</dDirEmi>
                    <dNumCas>0</dNumCas>
                    <cDepEmi>1</cDepEmi>
                    <dDesDepEmi>CAPITAL</dDesDepEmi>
                    <cDisEmi>1</cDisEmi>
                    <dDesDisEmi>ASUNCION (DISTRITO)</dDesDisEmi>
                    <cCiuEmi>1</cCiuEmi>
                    <dDesCiuEmi>ASUNCION (DISTRITO)</dDesCiuEmi>
                    <dTelEmi>$dTelEmi</dTelEmi>
                    <dEmailE>$dEmailE</dEmailE>
                    <dDenSuc>Administracion</dDenSuc>
                    <gActEco>
                    <cActEco>82999</cActEco>
                    <dDesActEco>OTRAS ACTIVIDADES DE SERVICIOS DE APOYO A EMPRESAS N.C.P.</dDesActEco>
                    </gActEco>
                </gEmis>
                    $gDatRec
                </gDatGralOpe>
                <gDtipDE>
                <gCamFE>
                    <iIndPres>1</iIndPres>
                    <dDesIndPres>Operación presencial</dDesIndPres>
                </gCamFE>
                <gCamCond>
                    <iCondOpe>1</iCondOpe>
                    <dDCondOpe>Contado</dDCondOpe>
                    <gPaConEIni>
                    <iTiPago>1</iTiPago>
                    <dDesTiPag>Efectivo</dDesTiPag>
                    <dMonTiPag>$dTotBruOpeItem</dMonTiPag>
                    <cMoneTiPag>PYG</cMoneTiPag>
                    <dDMoneTiPag>Guarani</dDMoneTiPag>
                    </gPaConEIni>
                </gCamCond>
                <gCamItem>
                    <dCodInt>SERVICIOS</dCodInt>
                    <dDesProSer>Cobro de estacionamiento</dDesProSer>
                    <cUniMed>77</cUniMed>
                    <dDesUniMed>UNI</dDesUniMed>
                    <dCantProSer>$dCantProSer</dCantProSer>
                    <gValorItem>
                    <dPUniProSer>$dTotBruOpeItem</dPUniProSer>
                    <dTotBruOpeItem>$dTotBruOpeItem</dTotBruOpeItem>
                    <gValorRestaItem>
                        <dDescItem>0</dDescItem>
                        <dPorcDesIt>0</dPorcDesIt>
                        <dDescGloItem>0</dDescGloItem>
                        <dAntPreUniIt>0</dAntPreUniIt>
                        <dAntGloPreUniIt>0</dAntGloPreUniIt>
                        <dTotOpeItem>$dTotBruOpeItem</dTotOpeItem>
                    </gValorRestaItem>
                    </gValorItem>
                    <gCamIVA>
                    <iAfecIVA>1</iAfecIVA>
                    <dDesAfecIVA>Gravado IVA</dDesAfecIVA>
                    <dPropIVA>100</dPropIVA>
                    <dTasaIVA>10</dTasaIVA>
                    <dBasGravIVA>$dBasGravIVA</dBasGravIVA>
                    <dLiqIVAItem>$dLiqIVAItem</dLiqIVAItem>
                    <dBasExe>0</dBasExe>
                    </gCamIVA>
                </gCamItem>
                </gDtipDE>
                <gTotSub>
                <dSubExe>0</dSubExe>
                <dSubExo>0</dSubExo>
                <dSub5>0</dSub5>
                <dSub10>$dTotGralOpe</dSub10>
                <dTotOpe>$dTotGralOpe</dTotOpe>
                <dTotDesc>0</dTotDesc>
                <dTotDescGlotem>0</dTotDescGlotem>
                <dTotAntItem>0</dTotAntItem>
                <dTotAnt>0</dTotAnt>
                <dPorcDescTotal>0</dPorcDescTotal>
                <dDescTotal>0</dDescTotal>
                <dAnticipo>0</dAnticipo>
                <dRedon>0</dRedon>
                <dTotGralOpe>$dTotGralOpe</dTotGralOpe>
                <dIVA5>0</dIVA5>
                <dIVA10>$dTotIVA</dIVA10>
                <dLiqTotIVA5>0</dLiqTotIVA5>
                <dLiqTotIVA10>0</dLiqTotIVA10>
                <dTotIVA>$dTotIVA</dTotIVA>
                <dBaseGrav5>0</dBaseGrav5>
                <dBaseGrav10>$dBasGravIVA</dBaseGrav10>
                <dTBasGraIVA>$dBasGravIVA</dTBasGraIVA>
                </gTotSub>
            </DE>
            </rDE>
        </xDE>
        </rEnviDe>
        XML;

        return $data = 
        [
            'xml'                   => $xmlContent,
        ];
    }

    public function asignar_qr_cliente()
    {
        $usuarioIds = DB::table('transactions')
        ->where('usuario_id', '!=', 0)
        ->where('status', 'confirmed')
        ->whereNull('fullname')
        ->whereNull('email')
        ->whereNull('ci')
        ->distinct()
        ->pluck('usuario_id');
        
        foreach($usuarioIds as $usuario)
        {
            $client = new Client();
            $url = "https://app.paseolagaleria.com.py/api/vales/datos-usuarios/" .$usuario;
            try 
            {
                $response = $client->get($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'token' => 'KOwSyZzaCYybBCZTdZb2gNhnnJ59ywRRh83jY2f3XmLqW18QW0tUN8wrGrc07xVl4s9FvL6eEFlh3DIu',
                    ],
                ]);

                $dato = json_decode($response->getBody()->getContents(), true);
                $documento = str_replace('.', '', $dato['ci']);
                $email = $dato['user']['email'];
                $nombre = $dato['user']['first_name'] . ' ' .$dato['user']['last_name'];
                
                DB::table('transactions')
                ->where('usuario_id', $usuario)
                ->where('status', 'confirmed')
                ->update([
                    'fullname' => $nombre,
                    'email' => $email,
                    'ci' => $documento
                ]);
            } 
            catch (RequestException $e) 
            {
                if ($e->hasResponse()) {
                    return [
                        'status' => $e->getResponse()->getStatusCode(),
                        'body' => json_decode($e->getResponse()->getBody()->getContents(), true)
                    ];
                }
                return [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }

        }
    }

}
