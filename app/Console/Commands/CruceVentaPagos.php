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
        // Consulta el estado en Code100: GET {base}/daylic/control-factura/{cdc}
        $url = config('services.facturacion_code100.control_url');
        if (empty($url)) {
            // Por defecto se deriva de la URL de envío: .../daylic/factura -> .../daylic/control-factura
            $url = preg_replace('#/factura/?$#', '/control-factura', strtok((string) config('services.facturacion_code100.url'), '?'));
        }
        $apiKey = config('services.facturacion_code100.key');

        if (empty($url) || empty($apiKey)) {
            $this->error('Falta configurar FACTURACION_CODE100_API_URL / FACTURACION_CODE100_API_KEY.');
            return;
        }

        $facturas = \App\Models\HistorialFactura::where('estado_factura', 'Pendiente')
        ->whereNotNull('cdc')
        ->take(10000)
        ->get();

        $this->info("Verificando estado en Code100 ({$url}): {$facturas->count()} facturas pendientes");

        $client = new Client();
        $aprobadas = 0;
        $rechazadas = 0;

        foreach($facturas as $factura)
        {
            try {
                $response = $client->get(rtrim($url, '/').'/'.$factura->cdc, [
                    'headers' => [
                        'X-API-Key' => $apiKey,
                        'Accept' => 'application/json',
                    ],
                    'http_errors' => false,
                    'connect_timeout' => 10,
                    'timeout' => 30,
                ]);
            } catch (\Throwable $e) {
                $this->error("Factura {$factura->id}: error consultando Code100: ".$e->getMessage());
                continue;
            }

            $statusCode = $response->getStatusCode();
            $data = json_decode($response->getBody()->getContents(), true);

            // 422 = CDC inválido, 409 = Code100 tiene ese número con otro CDC: se informa y queda Pendiente
            if ($statusCode !== 200 || !is_array($data)) {
                $detalle = is_array($data) ? ($data['detail'] ?? json_encode($data)) : '';
                $this->error("Factura {$factura->id} (cdc={$factura->cdc}): HTTP {$statusCode} {$detalle}");
                continue;
            }

            $estado = strtolower((string) ($data['estado_code100'] ?? $data['estado'] ?? ''));

            if (str_starts_with($estado, 'aprob')) {
                $factura->estado_factura = 'Aprobado';
                $factura->save();
                $aprobadas++;
                $this->info("Factura Aprobada {$factura->id}");
            } elseif (str_starts_with($estado, 'rechaz')) {
                $factura->estado_factura = 'Rechazado';
                $factura->save();
                $rechazadas++;
                $this->info("Factura Rechazada {$factura->id}: ".($data['motivo'] ?? 'sin motivo'));
            }
            // Cualquier otro estado (ej. "pendiente") se deja como está para volver a consultar
        }
        $this->info("Proceso finalizado. Aprobadas: {$aprobadas}, rechazadas: {$rechazadas}");
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
