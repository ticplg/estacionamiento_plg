<?php

namespace App\Console\Commands;


use PDF;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class CruceVentaPagos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cruce-venta-pagos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de transacciones con historial de facturas...');

        $transaccion_totems = \App\Models\TransaccionTarjetaTotem::where('factura_id', 0)->get();
        $this->info("Totems pendientes: " . $transaccion_totems->count());

        foreach ($transaccion_totems as $tran_totem) {
            $factura = \App\Models\HistorialFactura::where('transaccion_tarjeta_totem_id', $tran_totem->id)->first();
            if ($factura) {
                $tran_totem->factura_id = $factura->id;
                $tran_totem->save();
                $this->info("Totem ID {$tran_totem->id} vinculado a Factura ID {$factura->id}");
            }
        }

        $transaccion_qr = \App\Models\QRTransaction::where('factura_id', 0)->where('status', 'confirmed')->get();
        $this->info("QR confirmados pendientes: " . $transaccion_qr->count());

        foreach ($transaccion_qr as $tran_qr) {
            $factura = \App\Models\HistorialFactura::where('transaccion_qr_id', $tran_qr->id)->first();
            if ($factura) {
                $tran_qr->factura_id = $factura->id;
                $tran_qr->save();
                $this->info("QR ID {$tran_qr->id} vinculado a Factura ID {$factura->id}");
            }
        }

        $this->info('Sincronización finalizada.');


        $pendientes_asignacion = \App\Models\FacturaCajeroSkydata::whereNull('cajero_id')->take(1000)->get();
        $this->info("Facturas sin cajero asignado: " . $pendientes_asignacion->count());

        $this->output->progressStart($pendientes_asignacion->count());

        foreach ($pendientes_asignacion as $pend) {
            $codigo = $pend->numero_factura;
            $partes = explode('-', $codigo);
            $resultado = $partes[0] . '-' . $partes[1];

            $cajero = \App\Models\CajeroSkydata::where('punto_expedicion', $resultado)->first();

            if ($cajero) {
                $pend->cajero_id = $cajero->id;
                $pend->save();
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->generar_pdf_factura_skydata();
        $this->verificar_estado_factura_app();
        $this->verificar_estado_factura_skydata();

    }

    public function verificar_estado_factura_skydata()
    {
        $facturas = \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Pendiente')
        ->whereNotNull('cdc')
        ->where('fecha_emision', '!=', date('Y-m-d'))
        ->take(10000)
        ->get();

        $procesadas = 0;

        foreach($facturas as $factura)
        {
            $client = new Client();
            $response = $client->post('https://api.factpy.com/facturacion-api/consultaDE.php', [
                'form_params' => [
                    'cdc' => $factura->cdc,
                    'recordID' => '45-9BD5A2E5',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Verificamos que sea un array y tenga la clave "status"
            if (is_array($data) && array_key_exists('status', $data)) {
                if ($data['status'] === true) {
                    $factura->estado_factura = 'Aprobado';
                    $this->info("Factura Aprobada {$factura->id}");
                } else {
                    $factura->estado_factura = 'Rechazado';
                    $this->info("Factura Rechazada {$factura->id}");
                }
                $factura->save();
            } else {
                // Log o print si vino una respuesta inesperada
                $factura->estado_factura = 'Rechazado';
                $factura->save();
                $this->error("Factura Rechazada {$factura->id}");
                //$this->error("Respuesta inválida o malformada para factura {$factura->id}");
            }
        }
        $this->info("Proceso finalizado. Total aprobadas: {$procesadas}");
    }

    public function verificar_estado_factura_app()
    {
        $facturas = \App\Models\HistorialFactura::where('estado_factura', 'Pendiente')
        ->whereNotNull('cdc')
        //->where('fecha_factura', '!=', date('Y-m-d'))
        ->take(10000)
        ->get();

        $procesadas = 0;

        foreach($facturas as $factura)
        {
            $client = new Client();
            $response = $client->post('https://api.factpy.com/facturacion-api/consultaDE.php', [
                'form_params' => [
                    'cdc' => $factura->cdc,
                    'recordID' => '45-9BD5A2E5',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Verificamos que sea un array y tenga la clave "status"
            if (is_array($data) && array_key_exists('status', $data)) {
                if ($data['status'] === true) {
                    $factura->estado_factura = 'Aprobado';
                    $this->info("Factura Aprobada {$factura->id}");
                } else {
                    $factura->estado_factura = 'Rechazado';
                    $this->info("Factura Rechazada {$factura->id}");
                }
                $factura->save();
            } 
            else 
            {
                if ($factura->fecha_factura && Carbon::parse($factura->fecha_factura)->diffInDays(now()) >= 2) {
                    $factura->estado_factura = 'Rechazado';
                    $factura->save();
                    $this->error("Factura Rechazada {$factura->id}");
                }
            }
        }
        $this->info("Proceso finalizado. Total aprobadas: {$procesadas}");
    }

    public function generar_pdf_factura_skydata()
    {
        $facturas = \App\Models\FacturaCajeroSkydata::whereNull('path_factura')->get();
        
        foreach ($facturas as $venta) {
            // Generar el PDF
            $pdf = PDF::loadView('documentos_emitidos.factura_pdf_skydata', compact('venta'))
                ->setPaper([0, 0, 400.77, 900.89], 'portrait');
    
            // Definir el path público donde se guardará el archivo
            $fileName = $venta->numero_factura . '.pdf';
            $path = 'facturas/' . $fileName;  // Ruta relativa dentro de 'storage/app/public'
    
            // Guardar el PDF en 'storage/app/public/facturas/'
            $pdf->save(storage_path('app/public/' . $path));
    
            // Guardar la ruta en la base de datos (pública)
            $venta->path_factura = 'storage/' . $path;
            $venta->save();
            $this->info("Documento Generado {$venta->id}");
        }
    }
}
