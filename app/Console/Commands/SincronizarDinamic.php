<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Storage;
use GuzzleHttp\Client;
use App\Models\HistorialFactura;
use App\Models\QRTransaction;
use App\Models\Configuracion;

class SincronizarDinamic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sincronizar-dinamic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
    * Execute the console command.
    */

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
        $this->asignar_tipo_tarjeta();
        $this->envio_facturas();
    }
    
    public function asignar_tipo_tarjeta()
    {

        $historicos = HistorialFactura::whereNull('tipo_tarjeta')->where('transaccion_tarjeta_id', '!=', 0)->get();

        foreach($historicos as $historico)
        {
            $url = "https://app.paseolagaleria.com.py/api/vales/verificaroperacion";
            $client = new Client();
        
            $body = [
                "transaccion_id" => $historico->transaccion_tarjeta_id,
            ];
        
            try {
                $response = $client->get($url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'token' => 'KOwSyZzaCYybBCZTdZb2gNhnnJ59ywRRh83jY2f3XmLqW18QW0tUN8wrGrc07xVl4s9FvL6eEFlh3DIu',
                    ],
                    'json' => $body,
                ]);
        
                $responseBody = json_decode($response->getBody()->getContents(), true);
                if (isset($responseBody['card_type'])) {
                    $historico->tipo_tarjeta = $responseBody['card_type'];
                    $historico->save(); // Guarda los cambios en la base de datos
                }
    
            } catch (RequestException $e) {
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

        $historicos = HistorialFactura::whereNull('tipo_tarjeta')->where('transaccion_qr_id', '!=', 0)->get();
        foreach($historicos as $historico)
        {
            $transaccion = QRTransaction::find($historico->transaccion_qr_id);
            $historico->tipo_tarjeta = $transaccion->account_type;
            $historico->save();
        }
    }

    public function envio_facturas()
    {
        $ventas = HistorialFactura::where('factura_sincronizada', 0)
        ->whereNotNull('numero_factura')
        ->whereNotNull('tipo_tarjeta')
        ->get();

        foreach($ventas as $venta)
        {
            $serie = explode('-', $venta->numero_factura);
            $client = new Client();
            //$url = 'http://200.35.177.28:3092/wsrestful/api/registrarfactura';
            $url = 'https://app.nexcelgt.com/wsrestful/api/registrarfactura';

            $headers = [
                'Content-Type' => 'application/json',
            ];

            $body = [
                "Empresa" => "DAYL",
                "Serie" => $serie[0].'-'.$serie[1],
                "Numero" => $serie[2],
                "Fecha" => date('YmdHis'),
                "Cliente" => "CTE-000063",
                "RUC" => $venta->documento,
                "Nombre" => $venta->razon_social,
                "Vendedor" => $venta->punto_venta,
                "Total" => $venta->monto_factura,
                "Items" => [
                    [
                        "SKU" => "PARK",
                        "Cantidad" => 1,
                        "Precio" => $venta->monto_factura,
                        "TotalLinea" => $venta->monto_factura
                    ]
                ]
            ];

            //\Log::info($body);

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $body
            ]);


            if(json_decode($response->getBody(), true)["CodigoRes"] == "0")
            {
                $venta->factura_sincronizada = 1;
                $venta->mensaje_factura = json_decode($response->getBody(), true)["Mensaje"];
                $venta->save();
            }
            else
            {
                $venta->mensaje_factura = json_decode($response->getBody(), true)["Mensaje"];
                $venta->save();
            }
        }

        $pagos = HistorialFactura::where('pago_sincronizado', 0)
        ->whereNotNull('numero_factura')
        ->whereNotNull('tipo_tarjeta')
        ->get();


        foreach($pagos as $pago)
        {

            $client = new Client();

            $serie = explode('-', $pago->numero_factura);

            //$url = 'http://200.35.177.28:3092/wsrestful/api/RegistraLiquidacion';
            $url = 'https://app.nexcelgt.com/wsrestful/api/RegistraLiquidacion';

            $headers = [
                'Content-Type' => 'application/json',
            ];

            //$tipoDoc = ["CARD", "DEBIT"];

            if($pago->tipo_tarjeta == "credit" || $pago->tipo_tarjeta == "TC")
            {
                $tipoDoc = "CARD";
            }
            else
            {
                $tipoDoc = "DEBIT";
            }

            $body = [
                "Fecha" => date('YmdHis'),
                "Empresa" => "DAYL",
                "Numero" => "$pago->ticket_number",
                "Vendedor" => "APP",
                "Monto" => $pago->monto_factura,
                "Depositos" => [
                    [
                        "Empresa" => "DAYL",
                        "TipoDoc" => $tipoDoc,
                        "Boleta" => "$pago->ticket_number",
                        "Fecha" => date('YmdHis'),
                        "Monto" => $pago->monto_factura,
                        "Moneda" => "PYG"
                    ]
                ],
                "Ventas" => [
                    [
                        "Numero" => $serie[2],
                        "Serie" => $serie[0].'-'.$serie[1],
                        "Fecha" => date('YmdHis'),
                        "Monto" => $pago->monto_factura,
                    ]
                ]
            ];

            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $body
            ]);

            json_decode($response->getBody(), true);

            if(json_decode($response->getBody(), true)["CodigoRes"] == "0")
            {
                $pago->pago_sincronizado = 1;
                $pago->mensaje_pago =  json_decode($response->getBody(), true)["Mensaje"];
                $pago->save();
            }
            else
            {
                $pago->mensaje_pago =  json_decode($response->getBody(), true)["Mensaje"];
                $pago->save();
            }

        }

    }
}
