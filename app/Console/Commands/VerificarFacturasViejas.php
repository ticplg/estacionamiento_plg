<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class VerificarFacturasViejas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verificar_facturas_viejas
                            {--hasta=2026-10-01 : Última fecha de factura emitida por el proveedor anterior (factpy)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica en factpy el estado de las facturas pendientes emitidas antes de la migración a Code100';

    public function handle()
    {
        $this->verificar_facturas_viejas($this->option('hasta'));
    }

    public function verificar_facturas_viejas($hasta)
    {
        $this->info("==== Inicio: app:verificar_facturas_viejas (facturas hasta {$hasta}) ====");

        $app = \App\Models\HistorialFactura::where('estado_factura', 'Pendiente')
        ->whereNotNull('cdc')
        ->where('fecha_factura', '<=', $hasta)
        ->take(10000)
        ->get();

        $skydata = \App\Models\FacturaCajeroSkydata::where('estado_factura', 'Pendiente')
        ->whereNotNull('cdc')
        ->where('fecha_emision', '<=', $hasta)
        ->take(10000)
        ->get();

        $this->consultar_estado_factpy($app, 'App');
        $this->consultar_estado_factpy($skydata, 'Skydata');
    }

    private function consultar_estado_factpy($facturas, $origen)
    {
        $this->info("Verificando estado {$origen} en factpy: {$facturas->count()} facturas pendientes");

        $client = new Client();
        $aprobadas = 0;
        $rechazadas = 0;

        foreach($facturas as $factura)
        {
            try {
                $response = $client->post('https://api.factpy.com/facturacion-api/consultaDE.php', [
                    'form_params' => [
                        'cdc' => $factura->cdc,
                        'recordID' => '45-9BD5A2E5',
                    ],
                    'http_errors' => false,
                    'connect_timeout' => 10,
                    'timeout' => 30,
                ]);
            } catch (\Throwable $e) {
                $this->error("Factura {$origen} {$factura->id}: error consultando factpy: ".$e->getMessage());
                continue;
            }

            $data = json_decode($response->getBody()->getContents(), true);

            // Respuesta inesperada: se informa y queda Pendiente para volver a consultar
            if (!is_array($data) || !array_key_exists('status', $data)) {
                $this->error("Factura {$origen} {$factura->id} (cdc={$factura->cdc}): HTTP {$response->getStatusCode()} respuesta inválida");
                continue;
            }

            if ($data['status'] === true) {
                $factura->estado_factura = 'Aprobado';
                $aprobadas++;
                $this->info("Factura {$origen} Aprobada {$factura->id}");
            } else {
                $factura->estado_factura = 'Rechazado';
                $rechazadas++;
                $this->info("Factura {$origen} Rechazada {$factura->id}");
            }
            $factura->save();
        }
        $this->info("Proceso {$origen} finalizado. Aprobadas: {$aprobadas}, rechazadas: {$rechazadas}");
    }
}
