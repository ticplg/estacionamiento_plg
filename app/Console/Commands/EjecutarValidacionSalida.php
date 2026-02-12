<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use App\Models\QRTransaction;
use App\Models\Configuracion;
use Illuminate\Console\Command;
use GuzzleHttp\Exception\RequestException;

class EjecutarValidacionSalida extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ejecutar-validacion-salida';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $parkingDeviceId;
    private $usuario;
    private $password;
    private $facilityId;
    private $url;
    private $url_mega_print;
    private $accessKey;
    private $secretKey;

    public function __construct()
    {
        parent::__construct();
    }

    private function setConfigurations()
    {
        $this->usuario = Configuracion::where('nombre_parametro', 'usuario')->first()->valor;
        $this->password = Configuracion::where('nombre_parametro', 'password')->first()->valor;
        $this->externalDeviceId = Configuracion::where('nombre_parametro', 'externalDeviceId')->first()->valor;
        $this->parkingDeviceId = Configuracion::where('nombre_parametro', 'parkingDeviceId')->first()->valor;
        $this->facilityId = Configuracion::where('nombre_parametro', 'facilityId')->first()->valor;
        $this->url = Configuracion::where('nombre_parametro', 'url')->first()->valor;
        $this->url_mega_print = Configuracion::where('nombre_parametro', 'url_mega_print')->first()->valor;
        $this->accessKey = Configuracion::where('nombre_parametro', 'accesskey_mega_print')->first()->valor;
        $this->secretKey = Configuracion::where('nombre_parametro', 'secretkey_mega_print')->first()->valor;
    } 

    public function handle()
    {
       $this->setConfigurations();
       $this->pagar_estacionamiento();

    }

    public function pagar_estacionamiento()
    {
        $url = $this->url;

        $pagos = QRTransaction::where('fecha_hora', '>=', '2025-08-01 00:00:00')
            ->where('status', '=', 'confirmed')
            ->where('salida_ejecutada', 0)
            ->get();

        if ($pagos->isEmpty()) {
            $this->info("No hay pagos pendientes de salida para procesar.");
            return;
        }

        $this->info("Procesando " . $pagos->count() . " pagos pendientes...");

        foreach ($pagos as $index => $pago) {
            $precio = $pago->amount;
            $identificador = $pago->identificador;

            $this->info("[" . ($index + 1) . "/" . $pagos->count() . "] Procesando ID: $pago->id | Identificador: $identificador | Monto: $precio");

            $body = '...'; // omitido para brevedad

            $client = new Client();

            try {
                $response = $client->post($url, [
                    'headers' => [
                        'Content-Type' => 'text/xml; charset=utf-8',
                        'Authorization' => 'Basic ' . base64_encode($this->usuario . ':' . $this->password),
                    ],
                    'body' => $body,
                ]);

                $pago->salida_ejecutada = 1;
                $this->info("✅ Pago procesado correctamente para ID $pago->id");
            } catch (RequestException $e) {
                $pago->salida_ejecutada = 1;
                $this->error("❌ Error al procesar ID $pago->id: " . $e->getMessage());
            }

            $pago->save();
        }

        $this->info("✅ Proceso de validación de salidas finalizado.");
    }


}
