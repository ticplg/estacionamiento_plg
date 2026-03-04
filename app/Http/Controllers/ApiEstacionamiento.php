<?php

namespace App\Http\Controllers;

use Storage;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\RucActivo;
use Illuminate\Http\Request;
use App\Models\TicketHotel;
use App\Models\Configuracion;
use App\Models\QRTransaction;
use App\Models\TicketPorteria;
use App\Models\EventoEspecial;
use App\Models\EventoDescuento;
use App\Models\HistorialFactura;
use App\Models\HistorialMarcacion;
use App\Models\TicketEventoDescuento;
use App\Models\EspacioEstacionamiento;
use App\Models\RegistroEstacionamiento;
use App\Models\DescuentoEstacionamiento;
use App\Models\RegistroDescuentoAplicado;
use App\Models\RegistroEstacionamientoPago;
use App\Models\TicketEventoDescuentoEspecial;
use App\Models\RegistroDescuentoCine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiEstacionamiento extends Controller
{
    private $externalDeviceId;
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

    public function rateInfo(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'version' => ['required', 'string', 'max:50'],
                'so'      => ['required', 'string', 'max:50'],
            ],
            [
                'version.required' => 'La versión de la aplicación es obligatoria.',
                'version.string'   => 'La versión debe ser un texto válido.',
                'version.max'      => 'La versión no puede superar los 50 caracteres.',

                'so.required' => 'El sistema operativo es obligatorio.',
                'so.string'   => 'El sistema operativo debe ser un texto válido.',
                'so.max'      => 'El sistema operativo no puede superar los 50 caracteres.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => 'Error de validación.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $input = $request->identificador;

        $version = $request->input('version');
        $so      = $request->input('so');

        // Verificar si contiene una barra "/"
        if (strpos($input, '/') !== false) {
            // Extraer los números después de la última barra
            preg_match('/\/(\d+)$/', $input, $matches);
            $identificador = $matches[1] ?? null;
        } else {
            // Si no hay una barra, asumir que es un número
            $identificador = ctype_digit($input) ? $input : null;
        }
        
        $enGracia = RegistroEstacionamientoPago::where('identificador', $identificador)
            ->where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->latest('created_at')
            ->first();

        if ($enGracia) {
            return response()->json([
                'status' => 200,
                'pagado' => true,
                'data' => [],
            ]);
        }

        $url = $this->url;

        $descuento = DescuentoEstacionamiento::where('aplica_todos', 1)->where('activo', 1)->first();

        if(false)
        {
            $this->aplicar_descuento_ticket($identificador, $descuento->codigo, null, $version, $so);
        }
        else
        {
            $descuento_zf = Configuracion::where('nombre_parametro', 'validar_zona_fit')->first();
            if($descuento_zf->valor == 'SI')
            {
                /*$resultado_zona_fit = $this->verificar_cliente_zona_fit($request->cedula_usuario);
                //\Log::info($resultado_zona_fit);
                //\Log::info($resultado_zona_fit);
                //if($resultado_zona_fit["body"]['esta_al_dia'] == 1)
                if($resultado_zona_fit["body"]['esta_al_dia'] == 1 && isset($resultado_zona_fit['body']['planActivoCliente']))
                {
                    $this->aplicar_descuento_ticket($identificador, 35, $resultado_zona_fit, $version, $so);
                }*/
                $zf = $this->verificar_cliente_zona_fit($request->cedula_usuario);

                if (
                    ($zf['ok'] ?? false) === true &&
                    ($zf['body']['esta_al_dia'] ?? 0) == 1 &&
                    isset($zf['body']['planActivoCliente'])
                ) {
                    try {
                        $this->aplicar_descuento_ticket($identificador, 35, $zf, $version, $so);
                    } catch (\Throwable $e) {
                        \Log::warning('Fallo aplicar_descuento_ticket (no bloqueante)', ['error' => $e->getMessage()]);
                    }
                }                    
            }
	    }

        $cardData = $this->cardInfo($identificador);
        //$cardData = $identificador;
        $hora_inicio = (isset($cardData['error']) || empty($cardData['entryDateTime']))
            ? null
            : $cardData['entryDateTime'];

       
       $body = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
            <soapenv:Header/>
            <soapenv:Body>
                <msg:GetRateInfo xmlns:dta="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <msg:facilityId>' .$this->facilityId. '</msg:facilityId>
                    <msg:ticketId xsi:type="dta:GenericIdentification">
                        <dta:Identificator>'.$identificador.'</dta:Identificator>
                        <dta:Type>PARK</dta:Type>
                    </msg:ticketId>
                    <msg:externalDeviceId>' . $this->externalDeviceId . '</msg:externalDeviceId>
                    <msg:parkingDeviceId>' . $this->parkingDeviceId . '</msg:parkingDeviceId>
                </msg:GetRateInfo>
            </soapenv:Body>
        </soapenv:Envelope>';

        $client = new Client();

        try 
        {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                ],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            // Cargar la respuesta XML usando DOMDocument
            $dom = new \DOMDocument();
            $dom->loadXML($responseBody);

            // Utilizar DOMXPath para navegar el XML con namespaces
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('msg', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg');
            $xpath->registerNamespace('ns2', 'http://www.skidata.com/contractor/dtaservice/v7/common');
            $xpath->registerNamespace('ns3', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/data');

            // Obtener los valores necesarios
            $price = $xpath->evaluate('string(//ns3:Price/ns2:Amount)');
            $currencyCode = $xpath->evaluate('string(//ns3:Price/ns2:CurrencyCode)');
            $netPrice = $xpath->evaluate('string(//ns3:NetPrice/ns2:Amount)');
            $netTurnover = $xpath->evaluate('string(//ns3:NetTurnover/ns2:Amount)');
            $rateNumber = $xpath->evaluate('string(//ns3:RateNumber)');
            $dateTimeEnd = $xpath->evaluate('string(//ns3:DateTimeEnd)');
            $rateEnd = $xpath->evaluate('string(//ns3:RateEnd)');

            //$dateTimeEnd_ = new \DateTime($dateTimeEnd);
            //$rateEnd_ = new \DateTime($rateEnd);
            //$interval = $dateTimeEnd_->diff($rateEnd_);

            $fechaParametro = Carbon::parse($hora_inicio);
            $ahora = Carbon::now();

            $segundosTotales  = $fechaParametro->diffInSeconds($ahora);

            $horas = floor($segundosTotales / 3600);
            $minutos = floor(($segundosTotales % 3600) / 60);
            $segundos = $segundosTotales % 60;

            $registro = new RegistroEstacionamiento;
            $registro->identificador = $identificador;
            $registro->user_app_id = $request->user_id;
            $registro->user_first_name = $request->first_name;  
            $registro->user_last_name = $request->last_name;
            $registro->fecha_lectura = date('Y-m-d');
            $registro->hora_lectua = date('H:i:s');
            $registro->price = $price;
            $registro->version = $version;
            $registro->so = $so;
            $registro->parking_duration = sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos); //$this->calcular_duracion($hora_inicio);
            $registro->save();

            $result = [
                'price' => $price,
                'price_format' => number_format($price, 0, '', '.'),
                //'price' => ($request->user_id == 39200 || $request->user_id == 4184) ? 10000 : $price,
                //'price_format' => ($request->user_id == 39200 || $request->user_id == 4184) ? '10.000' : number_format($price, 0, '', '.'),
                'currency' => $currencyCode,
                'entryDateTime' => date('d-m-y H:i', strtotime($hora_inicio)),
                'finishDateTime' => date('d-m-y H:i', strtotime($dateTimeEnd)),
                'parkingDuration' =>  sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos),
                'totalBilledTime' =>  sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos),
                'ticketNumber' => $identificador,
                'netPrice' => $netPrice,
                'netTurnover' => $netTurnover,
                'rateNumber' => $rateNumber,
                'dateTimeEnd' => $dateTimeEnd,
                'rateEnd' => $rateEnd,

            ];

            return response()->json([
                'status' => $statusCode,
                'pagado' => false,
                'data' => $result,
            ]);
        } 
        catch (\Exception $e) 
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function aplicar_descuento_ticket($identificador, $codigo, $datos_zona_fit, $version, $so)
    {
        $url = $this->url;
        
        $clienteId = $datos_zona_fit['body']['cliente']['id'];

        $descuento_cliente = RegistroDescuentoAplicado::where('zf_cliente_id', $clienteId)
        ->where('created_at', '>=', Carbon::today())
        ->first();
        
        if(!$descuento_cliente)
        {
            $descuento_aplicado = RegistroDescuentoAplicado::where('identificador', $identificador)->first();
            if(!$descuento_aplicado)
            {
                $body = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
                    <soapenv:Header/>
                    <soapenv:Body>
                        <msg:InsertElectronicValidation>
                            <msg:validationId>APT.VAL.1901198.'.$codigo.'</msg:validationId>
                            <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <ns481:Identificator>'.$identificador.'</ns481:Identificator>
                                <ns481:Type>PARK</ns481:Type>
                            </msg:ticketId>
                            <msg:externalDeviceId>' . $this->externalDeviceId . '</msg:externalDeviceId>
                            <msg:parkingDeviceId>' . $this->parkingDeviceId . '</msg:parkingDeviceId>
                        </msg:InsertElectronicValidation>
                    </soapenv:Body>
                </soapenv:Envelope>';
    
                $client = new Client();
    
                try 
                {
                    $response = $client->post($url, [
                        'headers' => [
                            'Content-Type' => 'text/xml; charset=utf-8',
                            'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                        ],
                        'body' => $body,
                    ]);
        
                    $statusCode = $response->getStatusCode();
                    $responseBody = (string) $response->getBody();
    
                    if($statusCode == 200)
                    {
                        $aplicado = new RegistroDescuentoAplicado;
                        $aplicado->codigo_descuento = $codigo;
                        $aplicado->identificador = $identificador;
                        $aplicado->body = $body;
                        $aplicado->response = $responseBody;
                        $aplicado->response_status = 200;
    
                        if(isset($datos_zona_fit))
                        {
                            $aplicado->zf_cliente_id = $datos_zona_fit['body']['cliente']['id'];
                            $aplicado->zf_tipo_plan = $datos_zona_fit['body']['planActivoCliente']['tipo'];
                            $aplicado->zf_cliente_nombre = $datos_zona_fit['body']['cliente']['nombre'];
                            $aplicado->zf_cliente_apellido = $datos_zona_fit['body']['cliente']['apellido'];
                            $aplicado->zf_cliente_cedula = $datos_zona_fit['body']['cliente']['cedula'];
                        }

                        $aplicado->version = $version;
                        $aplicado->so = $so;
                        $aplicado->fecha = date('Y-m-d');
                        $aplicado->save();
                    }
                    else
                    {
                        $aplicado->response = $responseBody;
                        $aplicado->response_status = 200;
                        $aplicado->save();
                    }
                } 
                catch (\Exception $e) 
                {
                    return response()->json([
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ], 200);
                }
            }
        }
    }

    public function calcular_duracion($dateString) 
    {
        // Crear un objeto DateTime a partir de la cadena de fecha recibida
        //$date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $dateString, new \DateTimeZone('America/Asuncion'));
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $dateString, new \DateTimeZone('Etc/GMT+3'));
        if ($date === false) {
            throw new \Exception("Invalid date format");
        }
    
        // Obtener la fecha y hora actual en la misma zona horaria
        //$now = new \DateTime('now', new \DateTimeZone('America/Asuncion'));
        $now = new \DateTime('now', new \DateTimeZone('Etc/GMT+3'));
    
        // Calcular la diferencia entre la fecha recibida y la fecha actual
        $interval = $now->diff($date);
    
        // Convertir la diferencia a horas, minutos y segundos totales
        $totalHours = $interval->days * 24 + $interval->h;
        $totalMinutes = $totalHours * 60 + $interval->i;
        $totalSeconds = $totalMinutes * 60 + $interval->s;
    
        // Formatear la diferencia en hh:mm:ss
        $hours = str_pad(floor($totalSeconds / 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad(floor(($totalSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($totalSeconds % 60, 2, '0', STR_PAD_LEFT);
    
        return "$hours:$minutes:$seconds";
    }

    /*public function verificar_cliente_zona_fit($documento)
    {
        $url = "https://gestion.zonafit.com.py/api/acceso/verificar_acceso";
        $client = new Client();
    
        try {
            $response = $client->get($url, [
                'query' => ['cedula' => $documento],
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]);
    
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
    
            return [
                'status' => $statusCode,
                'body' => json_decode($responseBody, true)
            ];
    
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
    }*/

    public function verificar_cliente_zona_fit($documento)
    {
        $url = "https://gestion.zonafit.com.py/api/acceso/verificar_acceso";

        $client = new Client([
            'timeout'         => 3, // total
            'connect_timeout' => 2, // conexión
        ]);

        try {
            $response = $client->get($url, [
                'query' => ['cedula' => $documento],
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'http_errors' => false, // ✅ clave: no excepción por 4xx/5xx
            ]);

            $status = $response->getStatusCode();
            $raw    = (string) $response->getBody();

            $json = json_decode($raw, true);

            // si vino HTML (502) o no es JSON válido, lo tratamos como fallo controlado
            if (!is_array($json)) {
                /*Log::warning('ZonaFit respuesta no JSON', [
                    'status' => $status,
                    'cedula' => $documento,
                    'body'   => mb_substr($raw, 0, 300),
                ]);*/

                return [
                    'ok'     => false,
                    'status' => $status,
                    'body'   => null,
                    'error'  => 'Respuesta no válida de ZonaFit',
                ];
            }

            // si no es 200, igual devolvemos body pero marcamos ok=false
            return [
                'ok'     => ($status === 200),
                'status' => $status,
                'body'   => $json,
                'error'  => ($status === 200) ? null : 'ZonaFit devolvió error HTTP',
            ];
        } catch (RequestException $e) {
            /*Log::warning('ZonaFit exception', [
                'cedula' => $documento,
                'error'  => $e->getMessage(),
            ]);*/

            return [
                'ok'     => false,
                'status' => null,
                'body'   => null,
                'error'  => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            /*Log::warning('ZonaFit throwable', [
                'cedula' => $documento,
                'error'  => $e->getMessage(),
            ]);*/

            return [
                'ok'     => false,
                'status' => null,
                'body'   => null,
                'error'  => $e->getMessage(),
            ];
        }
    }

    public function calcular_duracion_con_descuento($dateString) 
    {
        $tiempo_de_gracia = 2;
        // Crear un objeto DateTime a partir de la cadena de fecha recibida
        //$date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $dateString, new \DateTimeZone('America/Asuncion'));
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $dateString, new \DateTimeZone('Etc/GMT+3'));
        if ($date === false) {
            throw new \Exception("Invalid date format");
        }
    
        // Obtener la fecha y hora actual en la misma zona horaria y restarle 2 horas
        //$now = new \DateTime('now', new \DateTimeZone('America/Asuncion'));
        $now = new \DateTime('now', new \DateTimeZone('Etc/GMT+3'));
        
        $now->modify('-'.$tiempo_de_gracia.' hours');
    
        // Calcular la diferencia entre la fecha recibida y la fecha actual
        $interval = $now->diff($date);
    
        // Convertir la diferencia a horas, minutos y segundos totales
        $totalHours = $interval->days * 24 + $interval->h;
        $totalMinutes = $totalHours * 60 + $interval->i;
        $totalSeconds = $totalMinutes * 60 + $interval->s;
    
        // Formatear la diferencia en hh:mm:ss
        $hours = str_pad(floor($totalSeconds / 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad(floor(($totalSeconds % 3600) / 60), 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($totalSeconds % 60, 2, '0', STR_PAD_LEFT);
    
        return "$hours:$minutes:$seconds";
    }
    
    public function getAuthToken()
    {
        $url = $this->url_mega_print."/token";
        $client = new Client();
    
        $body = [
            "accessKey" => $this->accessKey,
            "secretKey" => $this->secretKey,
        ];
    
        try {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);
    
            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            return [
                'status' => $statusCode,
                'body' => json_decode($responseBody, true)
            ];

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
    
    public function cardInfo($identificador)
    {
        $url = $this->url;
        $body = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
            <soapenv:Header/>
            <soapenv:Body>
                <msg:GetCardInfo>
                    <msg:facilityId>' .$this->facilityId. '</msg:facilityId>
                    <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <ns481:Identificator>'.$identificador.'</ns481:Identificator>
                    <ns481:Type>PARK</ns481:Type>
                    </msg:ticketId>
                    <msg:externalDeviceId>' . $this->externalDeviceId . '</msg:externalDeviceId>
                    <msg:parkingDeviceId>' . $this->parkingDeviceId . '</msg:parkingDeviceId>
                </msg:GetCardInfo>
            </soapenv:Body>
        </soapenv:Envelope>';

        $client = new Client();

        try 
        {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                ],
                'body' => $body,
            ]);

            $responseBody = $response->getBody()->getContents();

            $dom = new \DOMDocument();
            $dom->loadXML($responseBody);

            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('msg', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg');
            $xpath->registerNamespace('ns3', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/data');

            return [
                'parkTransactionId' => $xpath->evaluate('string(//ns3:ParkTransactionId)'),
                'entryDeviceNumber' => $xpath->evaluate('string(//ns3:EntryDeviceNumber)'),
                'entryDateTime' => $xpath->evaluate('string(//ns3:EntryDateTime)'),
                'exitDateTime' => $xpath->evaluate('string(//ns3:ExitDateTime)'),
                'lastPayment' => $xpath->evaluate('string(//ns3:LastPayment)'),
                'rateEnd' => $xpath->evaluate('string(//ns3:RateEnd)'),
                'country' => $xpath->evaluate('string(//ns3:Country)'),
                'province' => $xpath->evaluate('string(//ns3:Province)'),
                'licenseType' => $xpath->evaluate('string(//ns3:LicenseType)'),
                'licenseNumber' => $xpath->evaluate('string(//ns3:LicenseNumber)'),
                'reservationKey' => $xpath->evaluate('string(//ns3:ReservationKey)'),
                'reservationValidFrom' => $xpath->evaluate('string(//ns3:ReservationValidFrom)'),
                'ticketNumber' => $identificador,
            ];
        } 
        catch (\Exception $e) 
        {
            return [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function pagar_estacionamiento(Request $request)
    {
        $url = $this->url;

        //$precio = 0; //($request->user_id != 39200) ? $request->price : 0;
        $precio = $request->price;

        $body = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg" xmlns:data="http://www.skidata.com/interfaces/parking/ticketManagement/v4/data" xmlns:com="http://www.skidata.com/contractor/dtaservice/v7/common">
            <soapenv:Header/>
            <soapenv:Body>
                <msg:BookPayment>
                    <msg:facilityId>' .$this->facilityId. '</msg:facilityId>
                    <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                        <ns481:Identificator>'.$request->identificador.'</ns481:Identificator>
                        <ns481:Type>PARK</ns481:Type>
                    </msg:ticketId>
                    <msg:paymentItem xsi:type="data:CashPaymentItem" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                        <data:Amount>
                            <com:Amount>' . $precio. '</com:Amount>
                            <com:CurrencyCode>' . $request->currency_code. '</com:CurrencyCode>
                        </data:Amount>
                    </msg:paymentItem>
                    <msg:externalDeviceId>' . $this->externalDeviceId . '</msg:externalDeviceId>
                    <msg:parkingDeviceId>' . $this->parkingDeviceId . '</msg:parkingDeviceId>
                </msg:BookPayment>
            </soapenv:Body>
        </soapenv:Envelope>';

        $client = new Client();

        try 
        {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                ],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            // Cargar la respuesta XML usando DOMDocument
            $dom = new \DOMDocument();
            $dom->loadXML($responseBody);

            // Utilizar DOMXPath para navegar el XML con namespaces
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('msg', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg');
            $xpath->registerNamespace('ns2', 'http://www.skidata.com/contractor/dtaservice/v7/common');
            $xpath->registerNamespace('ns3', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/data');

            // Obtener los valores necesarios
            $amountPaid = $xpath->evaluate('string(//ns3:AmountPaid/ns2:Amount)');
            $currencyCodePaid = $xpath->evaluate('string(//ns3:AmountPaid/ns2:CurrencyCode)');
            $remainingPrice = $xpath->evaluate('string(//ns3:RemainingPrice/ns2:Amount)');
            $currencyCodeRemaining = $xpath->evaluate('string(//ns3:RemainingPrice/ns2:CurrencyCode)');
            $changeMoney = $xpath->evaluate('string(//ns3:ChangeMoney/ns2:Amount)');
            $currencyCodeChange = $xpath->evaluate('string(//ns3:ChangeMoney/ns2:CurrencyCode)');
            $paidUntil = $xpath->evaluate('string(//ns3:PaidUntil/ns2:Value)');


            $registro_pago = new RegistroEstacionamientoPago;
            $registro_pago->identificador = $request->identificador;
            $registro_pago->price =   $request->price;
            $registro_pago->user_id = $request->user_id;
            $registro_pago->user_name = $request->first_name;
            $registro_pago->user_lastname = $request->last_name;
            $registro_pago->fecha_pago = date('Y-m-d');
            $registro_pago->hora_pago = date('H:i:s');
            $registro_pago->save();

            $result = [
                'id_operacion' => $registro_pago->id, 
                'AmountPaid' => [
                    'Amount' => $amountPaid,
                    'CurrencyCode' => $currencyCodePaid,
                ],
                'RemainingPrice' => [
                    'Amount' => $remainingPrice,
                    'CurrencyCode' => $currencyCodeRemaining,
                ],
                'ChangeMoney' => [
                    'Amount' => $changeMoney,
                    'CurrencyCode' => $currencyCodeChange,
                ],
                'PaidUntil' => $paidUntil,
            ];

            return response()->json([
                'status' => $statusCode,
                'data' => $result,
            ]);
        } 
        catch (RequestException $e) 
        {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody()->getContents();
                return response()->json([
                    'status' => $statusCode,
                    'error' => 'Server error',
                    'message' => $responseBody,
                ], $statusCode);
            }

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function generar_factura(Request $request)
    {
        $factura = HistorialFactura::where('identificador', $request->identificador)->where('fecha_factura', date('Y-m-d'))->first();
        if(!$factura)
        {
            $factura = new HistorialFactura;
            $factura->monto_factura = $request->monto;
            $factura->fecha_factura = date('Y-m-d');

            if($request->ruc == '88888801-5')
            {
                $factura->documento = '44444401-7';
                $factura->razon_social = 'Cliente Ocasional';
            }
            else
            {        
                $cliente = RucActivo::where('ruc', $request->ruc)->first();
                if(!$cliente)
                {
                    $cliente = RucActivo::where('codigo', $request->ruc)->first();
                }

                $factura->documento = $cliente->ruc;
                $factura->razon_social = $cliente->nombre;
            }

            $factura->identificador = $request->identificador;

            if($request->metodo_pago == 'TARJETA')
            {
                $factura->user_id = $request->transaccion['usuario_id'];
                $factura->transaccion_tarjeta_id = $request->transaccion['id'];
                $factura->authorization_number = json_decode($request->transaccion['response_data'])->confirmation->authorization_number;
                $factura->ticket_number = json_decode($request->transaccion['response_data'])->confirmation->ticket_number;
            }
            else
            {

                $pago = RegistroEstacionamientoPago::where('identificador', $request->identificador)->first();
                $factura->user_id = $pago->user_id;
                $factura->transaccion_qr_id =  $request->transaccion['id'];
                $factura->authorization_number = $request->transaccion['authorization_code'];
                $factura->ticket_number  = $request->transaccion['ticket_number'];
            }

            $factura->save();
            //$monto = $request->monto;
            //$cliente = $request->nombre_cliente;
            //$documento = $request->ruc;
            //$this->generateAndSendXml($monto, $cliente, $documento, $factura->id);
        }
        return $factura;
    }

    public function generateAndSendXml($monto, $cliente, $documento, $factura_id)
    {
        $factura = HistorialFactura::find($factura_id);

        // Paso 1: Obtener el token de autenticación
        $authResponse = $this->getAuthToken();
        if ($authResponse['status'] !== 200) {
            return response()->json(['status' => 'error', 'message' => 'Error al obtener el token de autenticación'], 500);
        }

        $authToken = $authResponse['body']['token'];

        // Paso 2: Generar y guardar el archivo XML
        $numero_factura = Configuracion::where('nombre_parametro', 'numero_factura')->first();
        $xmlContent = $this->generateXmlContent($monto, $cliente, $documento, $numero_factura->valor);
        $nombre = time();
        $filePath = 'xml_files/'.$nombre.'.xml'; // Define la ruta donde guardar el archivo
        Storage::disk('public')->put($filePath, $xmlContent["xml"]);
        
        Storage::disk('local')->put($filePath, $xmlContent["xml"]);

        $factura->path_xml = '/strorage/'.$filePath;
        $factura->save();
        
        //return $factura;

        // Verificar que el archivo se haya guardado correctamente
        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['status' => 'error', 'message' => 'Error al guardar el archivo XML'], 500);
        }

        // Paso 3: Leer el archivo desde el almacenamiento
        $fileContent = Storage::disk('local')->get($filePath);

        // Verificar que el contenido del archivo sea correcto
        if (empty($fileContent)) {
            return response()->json(['status' => 'error', 'message' => 'El archivo XML está vacío'], 500);
        }
        // Paso 4: Enviar el archivo XML junto con datos adicionales
        $endpoint = $this->url_mega_print.'/ecf';
        $client = new Client();

        try 
        {
            $response = $client->post($endpoint, 
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json',
                ],
                'multipart' => [
                    [
                        'name' => 'encabezado',
                        'contents' => $fileContent,
                        'filename' => $nombre.'xml',
                    ],
                    [
                        'name' => 'id_peticion',
                        'contents' => $factura->id, // Reemplaza con el valor dinámico
                    ],
                ],
            ]);

            /*$numero_factura->valor = $numero_factura->valor + 1; 
            $numero_factura->save();*/

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            // Parsear la respuesta XML a JSON
            $xml = simplexml_load_string($responseBody);
            $json = json_encode($xml);

            
            $factura->fecha_inicio_timbrado = $xmlContent["fecha_inicio_timbrado"];
            $factura->timbrado = $xmlContent["timbrado"];
            $factura->numero_factura = $xmlContent["numero_factura"];
            $factura->cdc = $xmlContent["cadena"];
            /*$factura->mega_print_id_operacion = json_decode($json, true)['rProtDe']['Id'];
            $factura->mega_print_fec_proc = json_decode($json, true)['rProtDe']['dFecProc'];
            $factura->mega_print_dig_val = json_decode($json, true)['rProtDe']['dDigVal'];
            $factura->mega_print_est_res = json_decode($json, true)['rProtDe']['dEstRes'];
            $factura->mega_print_aut = json_decode($json, true)['rProtDe']['dProtAut'];*/
            $factura->save();
            
            $numero_factura->valor = $numero_factura->valor + 1; 
            $numero_factura->save();

            /*$endpoint = $this->url_mega_print.'/ecf/pdf?cdc=';
            
            $response = $client->get($endpoint.$factura->mega_print_id_operacion, 
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $authToken,
                    'Accept' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $factura->path_factura = json_decode($responseBody, true)['url'];
            $factura->save();*/

            return $factura;

        } 
        catch (RequestException $e) 
        {
            if ($e->hasResponse()) {
                return response()->json([
                    'status' => $e->getResponse()->getStatusCode(),
                    'body' => json_decode($e->getResponse()->getBody()->getContents(), true)
                ]);
            }
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function obtener_facturas(Request $request)
    {
        $facturas = HistorialFactura::where('user_id', $request->usuario_id)
        ->whereNotNull('numero_factura')
        ->orderBy('id', 'DESC')->get();
        $result = [];
        foreach($facturas as $factura)
        {
            $result[] = [
                "id" => $factura->id,
                "fecha_factura" => $factura->fecha_factura,
                "fecha_factura_formateada" => date('d-m-Y', strtotime($factura->fecha_factura)),
                "monto_factura" => $factura->monto_factura,
                "numero_factura" => $factura->numero_factura,
                "monto_factura_formateado" => number_format($factura->monto_factura, 0, '', '.'),
            ];
        }

        return response()->json([
            'status' => 200,
            'data' => $result,
        ]);
    }

    public function obtener_historial_lecturas(Request $request)
    {
        $rows = DB::table('registro_estacionamientos as a')
            ->leftJoin('registro_descuento_aplicados as b', 'b.identificador', '=', 'a.identificador')
            ->select([
                'a.identificador',
                DB::raw("DATE_FORMAT(a.fecha_lectura, '%d/%m/%Y') as fecha_lectura"),
                'a.hora_lectua as hora_lectura',
                'a.parking_duration',
                DB::raw("CASE WHEN b.identificador IS NOT NULL THEN 'SI' ELSE 'NO' END AS descuento"),
            ])
            ->where('a.created_at', '>=', DB::raw('NOW() - INTERVAL 3 DAY'))
            ->where('a.user_app_id', $request->usuario_id)
            ->orderBy('a.created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'data'   => $rows,
        ]);
    }

    public function obtener_factura(Request $request)
    {
        $facturas = HistorialFactura::where('id', $request->factura_id)->orderBy('id', 'DESC')->get();
        $result = [];
        /*$client = new Client();

        $authResponse = $this->getAuthToken();
        if ($authResponse['status'] !== 200) {
            return response()->json(['status' => 'error', 'message' => 'Error al obtener el token de autenticación'], 500);
        }

        $authToken = $authResponse['body']['token'];*/

        foreach($facturas as $factura)
        {
            /*if(!isset($factura->path_factura))
            {
                $endpoint = $this->url_mega_print.'/ecf/pdf?cdc=';
            
                $response = $client->get($endpoint.$factura->cdc, 
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $authToken,
                        'Accept' => 'application/json',
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody()->getContents();
                $factura->path_factura = null;//json_decode($responseBody, true)['url'];
                $factura->save();
            }*/

            $result = [
                "id" => $factura->id,
                "fecha_factura" => $factura->fecha_factura,
                "fecha_factura_formateada" => date('d-m-Y', strtotime($factura->fecha_factura)),
                "monto_factura" => $factura->monto_factura,
                "numero_factura" => $factura->numero_factura,
                "monto_factura_formateado" => number_format($factura->monto_factura, 0, '', '.'),
                "factura_path" => 'https://parkingplg.paseolagaleria.com.py/' .$factura->path_factura,
            ];
        }

        return response()->json([
            'status' => 200,
            'data' => $result,
        ]);
    }

    private function generateXmlContent($monto, $cliente, $documento, $numero_factura)
    {
        $factura = str_pad($numero_factura, 7, '0', STR_PAD_LEFT);
        $fecha = (new \DateTime())->format('Y-m-d\TH:i:s');
        $anho = (new \DateTime())->format('Y');
        $mes = (new \DateTime())->format('m');
        $dia = (new \DateTime())->format('d');
        $dId = '5637173832';
        $cadena = '01801289572001032' . $factura . '2' . $anho . $mes . $dia . '1999758768';

        $DE_Id = $cadena.$this->getModule11($cadena);
        $dDVId = $this->getModule11($cadena);
        $dFecFirma =  date('Y-m-d\TH:i:s');
        $dCodSeg = '999758768';
        $dNumTim = '17600887';
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
        if($documento == '88888801-5')
        {
            $gDatRec = <<<XML
            <gDatRec>
                <iNatRec>2</iNatRec>
                <iTiOpe>2</iTiOpe>
                <cPaisRec>PRY</cPaisRec>
                <dDesPaisRe>Paraguay</dDesPaisRe>
                <iTiContRec>3</iTiContRec>
                <dDTipIDRec>IMPORTES CONSOLIDADOS</dDTipIDRec>
                <dNumIDRec>44444401-7</dNumIDRec>
                <dNomRec>IMPORTES CONSOLIDADOS</dNomRec>
                <dCodCliente>000000235351808</dCodCliente>
            </gDatRec>
            XML;
        }
        else
        {
            if(count(explode('-', $documento)) == 2)
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
                    <iTiContRec>2</iTiContRec>
                    <iTipIDRec>1</iTipIDRec>
                    <dDTipIDRec>Cédula paraguaya</dDTipIDRec>
                    <dNumIDRec>$documento</dNumIDRec>
                    <dNomRec>$dNomRec</dNomRec>
                    <dCodCliente>$dCodCliente</dCodCliente>
                </gDatRec>
                XML;
            }
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
                  <dEst>001</dEst>
                  <dPunExp>032</dPunExp>
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
            'timbrado'              => $dNumTim,
            'numero_factura'        => '001-032-'.$factura,
            'fecha_inicio_timbrado' => $dFeIniT,
            'cadena' => $DE_Id,
        ];

    }

    public function getModule11($rut) {
        $counterOfValues = 2;
        $addition = 0;
    
        for ($i = strlen($rut) - 1; $i >= 0; $i--) {
            if ($counterOfValues > 11) {
                $counterOfValues = 2;
            }
            $op1 = intval($rut[$i]);
            $addition += $op1 * $counterOfValues;
            $counterOfValues += 1;
        }
        
        $moduleOperator = 11;
        $division = $addition % $moduleOperator;
        $finalResult = 0;
        if ($division > 1) {
            $finalResult = $moduleOperator - $division;
            if ($finalResult == 10) {
                return getenv('CHECK_DIGIT_IS10'); // Asegúrate de tener esta variable de entorno configurada
            }
        }
        
        return strval($finalResult);
    }

    public function marcar_estacionamiento(Request $request)
    {
        $espacio = EspacioEstacionamiento::find($request->id_ubicacion);
        if($espacio)
        {
            $historial = HistorialMarcacion::where('user_app_id', $request->user_app_id)->get();
            foreach($historial as $hist)
            {
                $hist->delete();
            }

            $historial = new HistorialMarcacion;
            $historial->user_app_id = $request->user_app_id;
            $historial->user_app_correo = $request->user_app_correo;
            $historial->user_app_nombre = $request->user_app_nombre;
            $historial->id_ubicacion = $request->id_ubicacion;
            $historial->fecha_registro = $request->fecha_registro;
            $historial->fecha_vence = Carbon::parse($request->fecha_registro)->addHours(24);
            $historial->save();

            return response()->json([
                'status' => 200,
                'mensaje' => "Registro creado",
                'data' => $historial,
            ], 200);
        }
        else
        {
            return response()->json([
                'status' => 400,
                'mensaje' => "QR no válido.",
                'data' => [],
            ], 404);
        }
    }

    public function obtener_marca(Request $request)
    {
        $historial = HistorialMarcacion::where('user_app_id', $request->user_app_id)->first();

        if($historial)
        {
            $espacio = EspacioEstacionamiento::where('id', $historial->id_ubicacion)
            ->get();
        }
        else
        {
            $espacio = [];
        }


        return response()->json([
            'status' => 200,
            'mensaje' => "Registro enviado",
            'data' => $espacio,
        ], 200);
    }

    public function eliminar_marca(Request $request)
    {
        $historial = HistorialMarcacion::where('user_app_id', $request->user_app_id)->get();
        foreach($historial as $hist)
        {
            $hist->delete();
        }


        return response()->json([
            'status' => 200,
            'mensaje' => "Registro eliminado",
            'data' => [],
        ], 200);
    }

    public function generarQRExpress(Request $request)
    {
        //VALIDACIÓN (identificador obligatorio)
        $validated = $request->validate([
            'identificador'  => ['required','string','max:100'],
            'monto'          => ['required','numeric','min:1'],
            'user_id'        => ['required','integer'],
            'nombre_cliente' => ['nullable','string','max:255'],
            'ruc'            => ['nullable','string','max:50'],
        ], [
            'identificador.required' => 'El identificador es obligatorio.',
            'monto.required'         => 'El monto es obligatorio.',
            'monto.numeric'          => 'El monto debe ser numérico.',
            'monto.min'              => 'El monto debe ser mayor a 0.',
            'user_id.required'       => 'El user_id es obligatorio.',
        ]);

        // Inicializar Guzzle Client
        $client = new Client();

        $publica = "apps/TQPJv56thLhDTy0Hgcylc0vsoDyT3m5w";
        $privada = 'cRVRhj,UU$Q2ue48)QkHEAm0sn0j,P2adX)czefj';

        $headers = [
            'Authorization' => 'Basic '.base64_encode($publica.':'.$privada),
            'Content-Type'  => 'application/json',
        ];

        // Cuerpo de la solicitud
        $body = [
            'amount'      => $validated['monto'],
            'description' => 'Pago Estacionamiento',
        ];

        try {
            $response = $client->post(
                'https://comercios.bancard.com.py/external-commerce/api/0.1/commerces/807386/branches/32/selling/generate-qr-express',
                [
                    'headers' => $headers,
                    'json'    => $body,
                ]
            );

            $data = json_decode($response->getBody());

            $result = [
                'hook_alias' => $data->qr_express->hook_alias ?? null,
                'url'        => $data->qr_express->url ?? null,
            ];

            $pago = new QRTransaction();
            $pago->hook_alias      = $result['hook_alias'];
            $pago->qr_url          = $result['url'];
            $pago->usuario_id      = $validated['user_id'];
            $pago->identificador   = $validated['identificador'];
            $pago->nombre_cliente  = $validated['nombre_cliente'] ?? null;
            $pago->ruc_cliente     = $validated['ruc'] ?? null;
            $pago->save();

            return response()->json([
                'status' => $data->status ?? 'unknown',
                'data'   => $result,
            ]);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // Si Bancard devolvió respuesta con error, mostramos ese body si existe
            $resp = $e->getResponse();
            $body = $resp ? (string) $resp->getBody() : null;

            return response()->json([
                'error'   => 'Error en la solicitud a Bancard',
                'message' => $e->getMessage(),
                'body'    => $body,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'Error interno',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function bancardCallback(Request $request)
    {
        // Verificar autenticación Basic Auth
        /*$username = 'bancard_qr_checkout'; // Cambia esto por tu usuario
        //$password = 'yVGfgZ0qsJwwJkRT8sCGD4XtlL87K9'; // Cambia esto por tu contraseña
        
        $password = '1$kj=A7?6$E3@a<z[L5p)O2E>0Qod&';

        // Obtener el header Authorization
        $authHeader = $request->header('Authorization');

        // Verificar si se proporcionó el encabezado Authorization
        if (!$authHeader || !$this->checkBasicAuth($authHeader, $username, $password)) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }*/
        //Log::info('Pago recibido:', $pago);        
        if ($request->has('payment')) {

            $pago = QRTransaction::where('hook_alias', $request->input('payment.hook_alias'))->first();
            if ($pago) 
            {
                $pago->hook_alias = $request->input('payment.hook_alias'); 
                $pago->status = $request->input('payment.status'); 
                $pago->response_code = $request->input('payment.response_code'); 
                $pago->response_description = $request->input('payment.response_description'); 
                $pago->amount = $request->input('payment.amount'); 
                $pago->currency = $request->input('payment.currency'); 
                $pago->installment_number = $request->input('payment.installment_number'); 
                $pago->description = $request->input('payment.description'); 
                $pago->ticket_number = $request->input('payment.ticket_number'); 
                $pago->authorization_code = $request->input('payment.authorization_code'); 
                $pago->commerce_name = $request->input('payment.commerce_name'); 
                $pago->branch_name = $request->input('payment.branch_name'); 
                $pago->bin = $request->input('payment.bin'); 
                $pago->merchant_code = $request->input('payment.merchant_code'); 
                $pago->card_last_numbers = $request->input('payment.card_last_numbers'); 
                $pago->account_type = $request->input('payment.account_type'); 
                $pago->name = $request->input('payment.payer.name'); 
                $pago->lastname = $request->input('payment.payer.lastname');
                $pago->save(); 
            }
        }

        //Log::info('Pago recibido:', $request->all());

        // Realiza aquí el procesamiento necesario
        // Ejemplo: actualizar el estado de la orden

        return response()->json([
            "status" => "success",
            "messages" => [
                [
                    "level" => "success",
                    "key" => "Confirmed",
                    "description" => "Pago recibido con éxito"
                ]
            ]
        ]);

        /*return response()->json([
            "status" => "error",
            "messages" => [
                [
                    "level" => "error",
                    "key" => "ConfirmedError",
                    "description" => "No se pudo procesar la confirmacion"
                ]
            ]
        ], Response::HTTP_OK);*/
    }

    private function checkBasicAuth($authHeader, $username, $password)
    {
        // Extraer la parte "Basic" del header
        if (strpos($authHeader, 'Basic ') !== 0) {
            return false;
        }

        // Obtener la cadena codificada en Base64
        $encodedCredentials = substr($authHeader, 6);

        // Decodificar Base64
        $decodedCredentials = base64_decode($encodedCredentials);

        // La cadena decodificada debería tener el formato "username:password"
        list($authUsername, $authPassword) = explode(':', $decodedCredentials, 2);

        // Verificar que el usuario y la contraseña coincidan
        return $authUsername === $username && $authPassword === $password;
    }

    public function checkQrBancardPayment(Request $request)
    {
        $pago = QRTransaction::where('hook_alias', $request->hook_alias)->first();
        if ($pago) 
        {
            return response()->json([
                'data' => $pago,
            ]);
        }
    }

    public function revertPayment(Request $request)
    {
        // Inicializar Guzzle Client
        $client = new Client();

        $publica = "apps/TQPJv56thLhDTy0Hgcylc0vsoDyT3m5w"; 
        $privada = 'cRVRhj,UU$Q2ue48)QkHEAm0sn0j,P2adX)czefj';

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($publica . ':' . $privada),
            'Content-Type' => 'application/json',
        ];

        $body = [];

        $commerceId = ENV('COMERCIO_QR_BANCARD');
        $branchId = ENV('SUCURSAL_QR_BANCARD');
        $paymentId = $request->hook_alias; // Reemplaza con el ID de pago dinámico

        $url = "https://comercios.bancard.com.py/external-commerce/api/0.1/commerces/{$commerceId}/branches/{$branchId}/selling/payments/revert/{$paymentId}";
        
        //$url = "https://desa.infonet.com.py:8035/external-commerce/api/0.1/commerces/{$commerceId}/branches/{$branchId}/selling/payments/revert/{$paymentId}";

        // Enviar la solicitud HTTP PUT con Guzzle
        try {
            $response = $client->put($url, [
                'headers' => $headers,
                //'json' => $body, // Usar la opción 'json' para el cuerpo, aunque está vacío
            ]);

            // Procesar la respuesta
            $data = json_decode($response->getBody());

            return response()->json([
                'status' => $data->status ?? 'success',
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function envio_facturas()
    {
        $ventas = HistorialFactura::where('factura_sincronizada', 0)->get();

        foreach($ventas as $venta)
        {
            $serie = explode('-', $venta->numero_factura);
            $client = new Client();
            $url = 'http://200.35.177.28:3092/wsrestful/api/registrarfactura';

            $headers = [
                'Content-Type' => 'application/json',
            ];

            $body = [
                "Empresa" => "DAYL",
                "Serie" => $serie[0].'-'.$serie[1],
                "Numero" => $serie[2],
                "Fecha" => date('YmdHis'),
                "Cliente" => "CTE-000001",
                "RUC" => $venta->documento,
                "Nombre" => $venta->razon_social,
                "Vendedor" => "APP",
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

        $pagos = HistorialFactura::where('pago_sincronizado', 0)->get();

        foreach($pagos as $pago)
        {

            $client = new Client();

            $serie = explode('-', $pago->numero_factura);

            $url = 'http://200.35.177.28:3092/wsrestful/api/RegistraLiquidacion';

            $headers = [
                'Content-Type' => 'application/json',
            ];

            $tipoDoc = ["CARD", "DEBIT"];

            $body = [
                "Fecha" => date('YmdHis'),
                "Empresa" => "DAYL",
                "Numero" => "$pago->ticket_number",
                "Vendedor" => "APP",
                "Monto" => $pago->monto_factura,
                "Depositos" => [
                    [
                        "Empresa" => "DAYL",
                        "TipoDoc" => $tipoDoc[array_rand($tipoDoc)],
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

    public function verificar_ruc(Request $request)
    {
        $cliente = RucActivo::where('ruc', $request->ruc)->first();

        if(!$cliente)
        {
            $cliente = RucActivo::where('codigo', $request->ruc)->first();
        }

        return response()->json([
            'status' => 200,
            'data' => $cliente
        ], 200);
    }

    public function forma_pagos(Request $request)
    {
        $qr = Configuracion::where('nombre_parametro', 'pago_qr')->first()->valor;
        $tarjeta = Configuracion::where('nombre_parametro', 'pago_tarjeta')->first()->valor;
        
        $data = 
        [
            "tarjeta" => $tarjeta,
            "qr" => $qr,
        ];
        
        
        return response()->json([
            'status' => 200,
            'data' => $data
        ], 200);
    }

    public function eventos()
    {
        $now = Carbon::now(new \DateTimeZone('Etc/GMT+3'));

        $eventos = EventoDescuento::select('id', 'nombre_evento', 'fecha_hora_fin')
            ->where('fecha_hora_inicio', '<=', $now)
            ->where('fecha_hora_fin', '>=', $now)
            ->get();

        return response()->json([
            'status' => 200,
            'data' => $eventos
        ], 200);

    }

    public function registrar_ticket_evento(Request $request)
    {
        $descuento = TicketEventoDescuento::where('identificador', $request->identificador)->first();


        if(strlen($request->identificador) != 23)
        {
            return response()->json([
                'status' => 500,
                'data' => $descuento,
                'message' => "Por favor, vuelva a pasar el código QR por el escáner."
            ], 500);
        }

        if(!$descuento)
        {
            $descuento = new TicketEventoDescuento;
            $descuento->evento_id = $request->evento_id;
            $descuento->identificador = $request->identificador;
            $descuento->fecha_hora_validacion = date('Y-m-d H:i:s');
            $descuento->decision = $request->decision;
            $descuento->save();
            
            $evento = EventoDescuento::find($request->evento_id);
            $evento->cantidad_validaciones_hechas = $evento->cantidad_validaciones_hechas + 1;
            $evento->save();

        }
        if(!$descuento->evento->tarifado)
        {
            $this->descuento_parking($descuento);
        }
        /*else
        {
            $this->descuento_parking_v2();
        }*/

        return response()->json([
            'status' => 200,
            'data' => $descuento
        ], 200);
    }

    public function descuento_parking(TicketEventoDescuento $ticket)
    {

        $now = Carbon::now(new \DateTimeZone('Etc/GMT+3'));

            $body = '
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
                    <soapenv:Header/>
                    <soapenv:Body>
                        <msg:InsertElectronicValidation>
                            <msg:validationId>APT.VAL.1901198.5001</msg:validationId>
                            <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                                <ns481:Identificator>'.$ticket->identificador.'</ns481:Identificator>
                                <ns481:Type>PARK</ns481:Type>
                            </msg:ticketId>
                            <msg:externalDeviceId>458</msg:externalDeviceId>
                            <msg:parkingDeviceId>81</msg:parkingDeviceId>
                        </msg:InsertElectronicValidation>
                    </soapenv:Body>
                </soapenv:Envelope>
                ';
        
            $client = new Client();
        
            try 
            {
                $response = $client->post($this->url, [
                    'headers' => [
                        'Content-Type' => 'text/xml; charset=utf-8',
                        'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                    ],
                    'body' => $body,
                ]);
        
                $statusCode = $response->getStatusCode();
        
                if($statusCode == 200)
                {
                    $ticket->fecha_hora_exoneracion = date('Y-m-d H:i:s');
                    $ticket->save();
                }
            } 
            catch (\Exception $e) 
            {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 500);
            }
    }
    
    public function evento_especial(Request $request)
    {
        $now = Carbon::now(new \DateTimeZone('Etc/GMT+3'));

        $eventos = EventoEspecial::select('id', 'nombre_evento', 'fecha_hora_fin')
        ->where('fecha_hora_inicio', '<=', $now)
        ->where('fecha_hora_fin', '>=', $now)
        ->where('nombre_exonerador', $request->nombre_exonerador)
        ->get();

        return response()->json([
            'status' => 200,
            'data' => $eventos
        ], 200);
    }

    public function registrar_ticket_evento_especial(Request $request)
    {
        $descuento = TicketEventoDescuentoEspecial::where('identificador', $request->identificador)->first();

        if(strlen($request->identificador) != 23)
        {
            return response()->json([
                'status' => 500,
                'data' => $descuento,
                'message' => "Por favor, vuelva a pasar el código QR por el escáner."
            ], 500);
        }

        if(!$descuento)
        {
            $descuento = new TicketEventoDescuentoEspecial;
            $descuento->evento_especial_id = $request->evento_especial_id;
            $descuento->identificador = $request->identificador;
            $descuento->fecha_hora_validacion = date('Y-m-d H:i:s');
            $descuento->save();
            
            $evento = EventoEspecial::find($request->evento_especial_id);
            $evento->cantidad_validaciones_hechas = $evento->cantidad_validaciones_hechas + 1;
            $evento->save();

        }

        $this->descuento_parking_especial($descuento);

        return response()->json([
            'status' => 200,
            'data' => $descuento
        ], 200);
    }

    public function descuento_parking_especial(TicketEventoDescuentoEspecial $ticket)
    {

        $now = Carbon::now(new \DateTimeZone('Etc/GMT+3'));

        $body = '
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
                <soapenv:Header/>
                <soapenv:Body>
                    <msg:InsertElectronicValidation>
                        <msg:validationId>APT.VAL.1901198.5001</msg:validationId>
                        <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                            <ns481:Identificator>'.$ticket->identificador.'</ns481:Identificator>
                            <ns481:Type>PARK</ns481:Type>
                        </msg:ticketId>
                        <msg:externalDeviceId>456</msg:externalDeviceId>
                        <msg:parkingDeviceId>82</msg:parkingDeviceId>
                    </msg:InsertElectronicValidation>
                </soapenv:Body>
            </soapenv:Envelope>
            ';
    
        $client = new Client();
    
        try 
        {
            $response = $client->post($this->url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                ],
                'body' => $body,
            ]);
    
            $statusCode = $response->getStatusCode();
    
            if($statusCode == 200)
            //if(true)
            {
                $ticket->fecha_hora_exoneracion = date('Y-m-d H:i:s');
                $ticket->save();
            }
        } 
        catch (\Exception $e) 
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function verificar_estado_factura()
    {
        $facturas = HistorialFactura::where('estado_factura', 'Pendiente')
        ->whereNotNull('cdc')
        ->take(10)
        ->get();

        foreach($facturas as $factura)
        {
            $client = new Client();
            $response = $client->post('https://api.factpy.com/facturacion-api/consultaDE.php', [
                'form_params' => [
                    'cdc' => $factura->cdc,
                    'recordID' => '45-9BD5A2E5',
                ],
            ]);

            return $body = $response->getBody()->getContents();

            return $data = json_decode($body, true);

            if($data)
            {
                $factura->estado_factura = 'Aprobado';
                $factura->save();
            }
        }
    }

    public function recibir_pagos(Request $request)
    {
        /*return $request->ci;
        $request->tipo_pago
        $request->response_data
        $request->amount
        $request->usuario_id
        $request->error
        $request->status
        $request->ci
        $request->nombre_completo*/
    }


    public function registrar_porteria(Request $request)
    {
        $porteria = TicketPorteria::where('identificador', $request->identificador)->first();
        if(!$porteria)
        {
            $porteria = new TicketPorteria;
            $porteria->identificador = $request->identificador;
            $porteria->nombre_apellido = $request->nombre_apellido;
            $porteria->chapa_vehiculo = $request->chapa;
            $porteria->numero_orden_trabajo = $request->numero_ot;
            $porteria->usuario_id = \Auth::user()->id;
            $porteria->exonerado = 0;
            $porteria->save();

            $mensaje = 'Registro creado exitosamente';
        }
        else
        {
            $mensaje = 'El codigo de tarjeta leido ya se encuentra en la base de datos, favor verificar.';
        }

        
        return response()->json([
            'status' => 200,
            'mensaje' => $mensaje,
            'data' => $porteria,
        ], 200);


    }


    public function descuento_parking_v2()
    {

        $now = Carbon::now(new \DateTimeZone('Etc/GMT+3'));
        
        $eventos = EventoDescuento::where('fecha_hora_inicio', '<=', $now)
        ->where('fecha_hora_fin_exoneracion', '>=', $now)
        ->pluck('id');

        $tickets = TicketEventoDescuento::whereIn('evento_id', $eventos)->get();

        foreach($tickets as $ticket)
        {
            if($ticket->evento->tarifado)
            {
                $monto_actual = $this->obtener_monto($ticket->identificador);

                $monto_configurado = Configuracion::where('nombre_parametro', $ticket->evento->tipo_evento)->first()->valor;

                while($monto_actual > $monto_configurado)
                {
                    $this->aplicar_descuento_ticket_eventos($ticket);
                    $monto_actual = $this->obtener_monto($ticket->identificador);
                }

            }
        }
    }

    public function aplicar_descuento_ticket_eventos(TicketEventoDescuento $ticket)
    {
        $body = '
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
                <soapenv:Header/>
                <soapenv:Body>
                    <msg:InsertElectronicValidation>
                        <msg:validationId>APT.VAL.1901198.35</msg:validationId>
                        <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                            <ns481:Identificator>'.$ticket->identificador.'</ns481:Identificator>
                            <ns481:Type>PARK</ns481:Type>
                        </msg:ticketId>
                        <msg:externalDeviceId>458</msg:externalDeviceId>
                        <msg:parkingDeviceId>81</msg:parkingDeviceId>
                    </msg:InsertElectronicValidation>
                </soapenv:Body>
            </soapenv:Envelope>
            ';
                        
        $client = new Client();
                        
        try {
            $response = $client->post($this->url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                ],
                'body' => $body,
            ]);
        
            $statusCode = $response->getStatusCode();
        
            if($statusCode == 200)
            {
                $ticket->fecha_hora_exoneracion = date('Y-m-d H:i:s');
                $ticket->save();
            }
        } 
        catch (\Exception $e) 
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function obtener_monto($identificador)
    {
       $url = $this->url;
       $body = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
            <soapenv:Header/>
            <soapenv:Body>
                <msg:GetRateInfo xmlns:dta="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <msg:facilityId>' .$this->facilityId. '</msg:facilityId>
                    <msg:ticketId xsi:type="dta:GenericIdentification">
                        <dta:Identificator>'.$identificador.'</dta:Identificator>
                        <dta:Type>PARK</dta:Type>
                    </msg:ticketId>
                    <msg:externalDeviceId>' . $this->externalDeviceId . '</msg:externalDeviceId>
                    <msg:parkingDeviceId>' . $this->parkingDeviceId . '</msg:parkingDeviceId>
                </msg:GetRateInfo>
            </soapenv:Body>
        </soapenv:Envelope>';

        $client = new Client();

        try 
        {
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                ],
                'body' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            // Cargar la respuesta XML usando DOMDocument
            $dom = new \DOMDocument();
            $dom->loadXML($responseBody);

            // Utilizar DOMXPath para navegar el XML con namespaces
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('msg', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg');
            $xpath->registerNamespace('ns2', 'http://www.skidata.com/contractor/dtaservice/v7/common');
            $xpath->registerNamespace('ns3', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/data');

            // Obtener los valores necesarios
            $price = $xpath->evaluate('string(//ns3:Price/ns2:Amount)');
            $currencyCode = $xpath->evaluate('string(//ns3:Price/ns2:CurrencyCode)');
            $netPrice = $xpath->evaluate('string(//ns3:NetPrice/ns2:Amount)');
            $netTurnover = $xpath->evaluate('string(//ns3:NetTurnover/ns2:Amount)');
            $rateNumber = $xpath->evaluate('string(//ns3:RateNumber)');
            $dateTimeEnd = $xpath->evaluate('string(//ns3:DateTimeEnd)');
            $rateEnd = $xpath->evaluate('string(//ns3:RateEnd)');

            return $price;

        } 
        catch (\Exception $e) 
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function registrar_checkin(Request $request)
    {

        $ticket = TicketHotel::where('identificador', $request->identificador)->first();
        if(!$ticket)
        {
            $ticket = new TicketHotel;
            $ticket->identificador = $request->identificador;
            $ticket->nombre_huesped = $request->nombre_huesped;
            $ticket->habitacion = $request->habitacion;
            $ticket->fecha_checkin = $request->fecha_checkin;
            $ticket->hora_checkin = $request->hora_checkin;
            $ticket->save();
            $mensaje = "Registro creado existosamente";
        }
        else
        {
            $mensaje = "Número de ticket asociado a otro ingreso, verifique y vuelva a intentarlo";
        }
        
        return response()->json([
            'status' => 'ok',
            'mensaje' => $mensaje,
        ], 200);

    }

    public function datos_ticket(Request $request)
    {
        // Validación básica
        $request->validate([
            'identificador' => 'required|string'
        ]);

        try {
            $ticket = TicketHotel::where('identificador', $request->identificador)->first();

            if (!$ticket) {
                return response()->json([
                    'status'  => 'error',
                    'mensaje' => 'Ticket no encontrado.',
                ], 404);
            }

            // Si querés limitar campos expuestos, podés mapear aquí:
            // $data = $ticket->only(['identificador','fecha_checkin','hora_checkin','fecha_checkout','hora_checkout','monto_en_checkout']);
            $data = $ticket;

            return response()->json([
                'status' => 'ok',
                'data'   => $data,
            ], 200);

        } catch (\Throwable $e) {
            /*Log::error('Error en datos_ticket', [
                'identificador' => $request->identificador ?? null,
                'message'       => $e->getMessage(),
            ]);*/

            return response()->json([
                'status'  => 'error',
                'mensaje' => 'No se pudo obtener el ticket por un error interno.',
            ], 500);
        }
    }

    public function registrar_checkout(Request $request)
    {
        // 1) Validación básica del identificador
        $request->validate([
            'identificador' => 'required|string'
        ]);

        try {
            // 2) Transacción: todo o nada
            $resultado = DB::transaction(function () use ($request) {

                // 2.1) Tomamos el ticket pendiente y lo bloqueamos para evitar carreras
                $ticket = TicketHotel::where('identificador', $request->identificador)
                    ->whereNull('fecha_checkout')
                    ->whereNull('hora_checkout')
                    ->lockForUpdate()
                    ->first();

                if (!$ticket) {
                    // Devolvemos estructura para responder fuera de la tx
                    return [
                        'http'    => 404,
                        'status'  => 'error',
                        'mensaje' => 'Número de ticket no encontrado o ya validado. Verifique e intente nuevamente.',
                    ];
                }

                // 2.2) Lógica de negocio
                $ticket->monto_en_checkout = $this->obtener_monto($ticket->identificador);
                $ticket->save();

                // OJO: si exonerar_hotel() hace llamadas externas y querés evitar efectos fuera de la tx,
                // podrías moverlo a un Job con DB::afterCommit() (si tu versión lo soporta).
                $this->exonerar_hotel($ticket->identificador);

                $ticket->fecha_checkout = now()->toDateString();
                $ticket->hora_checkout  = now()->format('H:i:s');
                $ticket->save();

                return [
                    'http'    => 200,
                    'status'  => 'ok',
                    'mensaje' => 'Se registró la exoneración correctamente.',
                    'data'    => [
                        'identificador'     => $ticket->identificador,
                        'monto_en_checkout' => $ticket->monto_en_checkout,
                        'fecha_checkout'    => $ticket->fecha_checkout,
                        'hora_checkout'     => $ticket->hora_checkout,
                    ],
                ];
            });

            // 3) Respuesta al cliente según el resultado de la transacción
            return response()->json([
                'status'  => $resultado['status'],
                'mensaje' => $resultado['mensaje'],
                'data'    => $resultado['data'] ?? null,
            ], $resultado['http']);

        } catch (\Throwable $e) {
            // 4) Log detallado para diagnóstico
            /*Log::error('Error en registar_checkout', [
                'identificador' => $request->identificador ?? null,
                'message'       => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);*/

            // 5) Mensaje claro para el cliente
            return response()->json([
                'status'  => 'error',
                'mensaje' => 'No se pudo completar el checkout por un error interno. Intente nuevamente. Si persiste, contacte a soporte.',
            ], 500);
        }
    }
    
    public function exonerar_hotel($identificador)
    {
        $body = '
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
                <soapenv:Header/>
                <soapenv:Body>
                    <msg:InsertElectronicValidation>
                        <msg:validationId>APT.VAL.1901198.5001</msg:validationId>
                        <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                            <ns481:Identificator>'.$identificador.'</ns481:Identificator>
                            <ns481:Type>PARK</ns481:Type>
                        </msg:ticketId>
                        <msg:externalDeviceId>456</msg:externalDeviceId>
                        <msg:parkingDeviceId>82</msg:parkingDeviceId>
                    </msg:InsertElectronicValidation>
                </soapenv:Body>
            </soapenv:Envelope>
            ';
                        
        $client = new Client();
                        
        try {
            $response = $client->post($this->url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':'.$this->password),
                ],
                'body' => $body,
            ]);
        } 
        catch (\Exception $e) 
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function registra_ticket_cine(Request $request)
    {
        if(strlen($request->identificador) != 23)
        {
            return response()->json([
                'status' => 500,
                'data' => $request->identificador,
                'message' => "Por favor, vuelva a pasar el código QR por el escáner."
            ], 500);
        }
            
        $ticket = RegistroDescuentoCine::where('identificador', $request->identificador)->first();
        
        if(!$ticket)
        {
            $ticket = new RegistroDescuentoCine;
            $ticket->identificador = $request->identificador;
            $ticket->fecha_registro = now();
            $ticket->save();
        }

        return response()->json([
            'status' => 200,
            'data' => $ticket
        ], 200);
    }

    public function version_app(Request $request)
    {
        if($request->so == "android")
        {
            $version = Configuracion::where('nombre_parametro', "version_android")->first();
        }
        else
        {
            $version = Configuracion::where('nombre_parametro', "version_ios")->first();
        }

        return response()->json([
            'status' => 200,
            'data' => $version->valor,
        ], 200);
    }

}
