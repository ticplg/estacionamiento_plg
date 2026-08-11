<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Models\Configuracion;
use App\Models\PuntoVenta;
use App\Models\RucEmail;
use App\Models\QRTransaction;
use App\Models\RucActivo;
use App\Models\RegistroEstacionamientoPago;
use App\Models\TransaccionTarjetaTotem;
use App\Models\HistorialFactura;
use Illuminate\Support\Facades\Http;

class ParkingTottem extends Controller
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

    public function totem_inicio()
    {
        return view('totems.index_sin_publicidad');
    }

    public function validar_ticket($identificador)
    {
        //$identificador = '29431464190119800014376';
        return view('totems.detalle_ticket', compact('identificador'));
        
    }

    public function rateInfo($identificador)
    {
        $input = $identificador; // Ejemplo de entrada
        //$input = '30461395190119800038416'; // Ejemplo de entrada

        // Verificar si contiene una barra "/"
        if (strpos($input, '/') !== false) {
            // Extraer los números después de la última barra
            preg_match('/\/(\d+)$/', $input, $matches);
            $identificador = $matches[1] ?? null;
        } else {
            // Si no hay una barra, asumir que es un número
            $identificador = ctype_digit($input) ? $input : null;
        }
        
        //return $identificador;
        $pago = RegistroEstacionamientoPago::where('identificador', $identificador)
        ->where('created_at', '>=', Carbon::now()->subMinutes(30))
        ->first();


        if($pago)
        {
            return response()->json([
                'status' => 200,
                'pagado' => true,
                'data' => [],
            ]);
        }

        $url = $this->url;

        $hora_inicio =  $this->cardInfo($identificador);
        $hora_inicio =  $this->cardInfo($identificador)['entryDateTime'];
       
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

            $result = [
                'price' => $price,
                'price_format' => number_format($price, 0, '', '.'),
                //'price' => ($request->user_id == 39200 || $request->user_id == 4184) ? 10000 : $price,
                //'price_format' => ($request->user_id == 39200 || $request->user_id == 4184) ? '10.000' : number_format($price, 0, '', '.'),
                'currency' => $currencyCode,
                'entryDateTime' => date('d-m-y H:i', strtotime($hora_inicio)),
                'finishDateTime' => date('d-m-y H:i', strtotime($dateTimeEnd)),
                'parkingDuration' =>  $this->calcular_duracion($hora_inicio),
                'totalBilledTime' =>  $this->calcular_duracion_con_descuento($hora_inicio),
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

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            // Cargar la respuesta XML usando DOMDocument
            $dom = new \DOMDocument();
            $dom->loadXML($responseBody);

            // Utilizar DOMXPath para navegar el XML con namespaces
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('msg', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg');
            $xpath->registerNamespace('ns3', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/data');

            // Obtener los valores necesarios
            $parkTransactionId = $xpath->evaluate('string(//ns3:ParkTransactionId)');
            $entryDeviceNumber = $xpath->evaluate('string(//ns3:EntryDeviceNumber)');
            $entryDateTime = $xpath->evaluate('string(//ns3:EntryDateTime)');
            $exitDateTime = $xpath->evaluate('string(//ns3:ExitDateTime)');
            $lastPayment = $xpath->evaluate('string(//ns3:LastPayment)');
            $rateEnd = $xpath->evaluate('string(//ns3:RateEnd)');
            $country = $xpath->evaluate('string(//ns3:Country)');
            $province = $xpath->evaluate('string(//ns3:Province)');
            $licenseType = $xpath->evaluate('string(//ns3:LicenseType)');
            $licenseNumber = $xpath->evaluate('string(//ns3:LicenseNumber)');
            $reservationKey = $xpath->evaluate('string(//ns3:ReservationKey)');
            $reservationValidFrom = $xpath->evaluate('string(//ns3:ReservationValidFrom)');

            return $result = [
                'parkTransactionId' => $parkTransactionId,
                'entryDeviceNumber' => $entryDeviceNumber,
                'entryDateTime' => $entryDateTime,
                'exitDateTime' => $exitDateTime,
                'lastPayment' => $lastPayment,
                'rateEnd' => $rateEnd,
                'country' => $country,
                'province' => $province,
                'licenseType' => $licenseType,
                'licenseNumber' => $licenseNumber,
                'reservationKey' => $reservationKey,
                'reservationValidFrom' => $reservationValidFrom,
                'ticketNumber' => $identificador,
            ];

        } 
        catch (\Exception $e) 
        {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function calcular_duracion_con_descuento($dateString) 
    {
        $tiempo_de_gracia = 2;
        // Crear un objeto DateTime a partir de la cadena de fecha recibida
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $dateString, new \DateTimeZone('America/Asuncion'));
        if ($date === false) {
            throw new Exception("Invalid date format");
        }
    
        // Obtener la fecha y hora actual en la misma zona horaria y restarle 2 horas
        $now = new \DateTime('now', new \DateTimeZone('America/Asuncion'));
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

    public function calcular_duracion($dateString) 
    {
        // Crear un objeto DateTime a partir de la cadena de fecha recibida
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s.u', $dateString, new \DateTimeZone('America/Asuncion'));
        if ($date === false) {
            throw new Exception("Invalid date format");
        }
    
        // Obtener la fecha y hora actual en la misma zona horaria
        $now = new \DateTime('now', new \DateTimeZone('America/Asuncion'));
    
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

    public function datos_factura(Request $request)
    {
        $ticket = $request->ticket;
        $monto = $request->monto;
        return view('totems.datos_factura', compact('ticket', 'monto'));
    }

    public function seleccionar_forma_pago(Request $request)
    {
        if(isset($request->ruc) && isset($request->email) && isset($request->razon_social))
        {
            $email = RucActivo::where('ruc', $request->ruc)->first();
            if($email)
            {
                $email->email = $request->email;
                $email->save();
            }
            else
            {
    
                $documento = explode('-', $request->ruc);
    
                if(count($documento) > 1)
                {
                    $email = new RucActivo;
                    $email->nombre = $request->razon_social;
                    $email->ruc = $request->ruc;
                    $email->codigo = $documento[0];
                    $email->tipo = $documento[1];
                    $email->identificador = 'NOAPLICA';
                    $email->estado = 'ACTIVO';
                    $email->email = $request->email;
                    $email->save();
                }
                else
                {
                    $email = new RucActivo;
                    $email->nombre = $request->razon_social;
                    $email->ruc = $request->ruc;
                    $email->codigo = 0;
                    $email->tipo = 0;
                    $email->identificador = 'NOAPLICA';
                    $email->estado = 'ACTIVO';
                    $email->email = $request->email;
                    $email->save();
                }
            }
            $email = $email->id;
        }
        else
        {
            $email = 0;
        }
        
        $identificador = $request->identificador;
        $monto = $request->monto;

        return view('totems.forma_pago', compact('email', 'monto', 'identificador'));

    }

    public function pago_qr(Request $request)
    {
        $email = $request->email;
        $identificador = $request->identificador;
        $monto =  $request->monto;
        $pago = $this->generarQRExpress($request->monto, $identificador);
        //$pago = QRTransaction::find(84);
        return view('totems.pago_qr', compact('pago', 'email', 'identificador', 'monto'));
    }

    public function generarQRExpress($monto, $identificador)
    {
        // Inicializar Guzzle Client
        $client = new Client();
        
        $publica = ENV('QR_PUBLICA_BANCARD');
        $privada =  ENV('QR_PRIVADA_BANCARD');

        //return 'Basic '.base64_encode($publica.':'.$privada);
        $headers = [
            'Authorization' => 'Basic '.base64_encode($publica.':'.$privada),
            'Content-Type' => 'application/json',
        ];
    
        // Definir el cuerpo de la solicitud en formato JSON
        $body = [
            'amount' => $monto,
            'description' => 'Pago Estacionamiento'
        ];
    
        // Enviar la solicitud HTTP POST con Guzzle
        try {

            $response = $client->post('https://comercios.bancard.com.py/external-commerce/api/0.1/commerces/'.ENV('COMERCIO_QR_BANCARD').'/branches/'.ENV('SUCURSAL_QR_BANCARD').'/selling/generate-qr-express', [
                'headers' => $headers,
                'json' => $body, // Usar la opción 'json' para que Guzzle codifique automáticamente el array a JSON
            ]);

            /*$response = $client->post('https://desa.infonet.com.py:8035/external-commerce/api/0.1/commerces/'.ENV('COMERCIO_QR_BANCARD').'/branches/'.ENV('SUCURSAL_QR_BANCARD').'/selling/generate-qr-express', [
                'headers' => $headers,
                'json' => $body, // Usar la opción 'json' para que Guzzle codifique automáticamente el array a JSON
            ]);*/
    
            // Retornar el cuerpo de la respuesta
            $data = json_decode($response->getBody());

            $result = [
                'hook_alias'    => $data->qr_express->hook_alias,
                'url'           => $data->qr_express->url,
            ];

            $pago = new QRTransaction;
            $pago->hook_alias = $data->qr_express->hook_alias;
            $pago->qr_url = $data->qr_express->url;
            $pago->identificador = $identificador;
            $pago->usuario_id = 0;
            $pago->save();

            return $pago;

        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verificar_email(Request $request)
    {
        $documento = explode('-', $request->ruc);
        if(count($documento) > 1)
        {
            $cliente = RucActivo::where('codigo', $documento[0])
            ->where('tipo', $documento[1])->first();
            if(!$cliente)
            {
                $cliente = RucActivo::where('codigo', $documento[0])->first();
            }
        }
        else
        {
            $cliente = RucActivo::where('codigo', $documento[0])->first();
        }

        if($cliente)
        {
            $razon_social = $cliente->nombre;
            $documento = $cliente->ruc;
            $email = $cliente->email;
        }
        else
        {
            $razon_social = '';
            $email = '';
        }

        return 
        [
            'email' => $email,
            'cliente' => $razon_social,
            'documento' => $documento,
        ];
    }

    public function pago_tarjeta(Request $request)
    {
        $identificador = $request->identificador;
        $monto = $request->monto;
        $email = $request->email;
        return view('totems.pago_tarjeta', compact('email', 'monto', 'identificador'));
    }

    public function pago_tarjeta_pos(Request $request)
    {

        $ip = PuntoVenta::where('nombre_punto_venta', $request->equipo)->first()->ip_pos;
        $valor = $request->monto;
        $identificador = $request->identificador;

        $response = Http::timeout(180) // Establecer tiempo de espera de 180 segundos
        ->post($ip.'pos/venta-ux', [
            'facturaNro' => 999999999999999,
            'monto' => $valor,
        ]);

        if ($response->successful()) 
        {
            $bin = json_decode($response->body(), true)['bin'];
            $nsu = json_decode($response->body(), true)['nsu'];

            $response = Http::timeout(180)
            ->post($ip.'pos/descuento', [
                "bin"   => $bin,
                "nsu"   => $nsu,
                "monto" => $valor
            ]);

            if ($response->successful()) 
            {
                $transaccion = new TransaccionTarjetaTotem;
                $json = $response->json();
                
                $transaccion->numero_boleta       = $json['nroBoleta']        ?? null;
                $transaccion->codigo_autorizacion = $json['codigoAutorizacion'] ?? null;
                $transaccion->codigo_comercio     = $json['codigoComercio']     ?? null;
                $transaccion->pan                 = $json['pan']                 ?? null;
                $transaccion->nombre_cliente      = $json['nombreCliente']      ?? null;
                $transaccion->nombre_tarjeta      = $json['nombreTarjeta']      ?? null;
                $transaccion->issuerId            = $json['issuerId']           ?? null;
                $transaccion->identificador       = $identificador;
                $transaccion->monto               = $valor;
                
                $transaccion->save();

                return response()->json([
                    'code' => 1,
                    'message' => 'Pago Exitoso',
                    'data' => $transaccion,
                ], 200);
            
            }
            else 
            {
                return response()->json([
                    'code' => 0,
                    'message' => 'Error en la petición',
                    'error' => json_decode($response->body(), true)['message']
                ], $response->status());
            }
            
        } 
        else 
        {
            return response()->json([
                'code' => 0,
                'message' => 'Error en la petición',
                'error' => json_decode($response->body(), true)['message']
            ], $response->status());
        }
    }

    public function pagar_estacionamiento($monto, $identificador, $currency_code)
    {

        $url = $this->url;

        $precio = $monto;

        $body = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg" xmlns:data="http://www.skidata.com/interfaces/parking/ticketManagement/v4/data" xmlns:com="http://www.skidata.com/contractor/dtaservice/v7/common">
            <soapenv:Header/>
            <soapenv:Body>
                <msg:BookPayment>
                    <msg:facilityId>' .$this->facilityId. '</msg:facilityId>
                    <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                        <ns481:Identificator>'.$identificador.'</ns481:Identificator>
                        <ns481:Type>PARK</ns481:Type>
                    </msg:ticketId>
                    <msg:paymentItem xsi:type="data:CashPaymentItem" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                        <data:Amount>
                            <com:Amount>' . $precio. '</com:Amount>
                            <com:CurrencyCode>' . $currency_code. '</com:CurrencyCode>
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
            $registro_pago->identificador = $identificador;
            $registro_pago->price =   $precio;
            $registro_pago->user_id = 0;
            $registro_pago->user_name = "TOTEM";
            $registro_pago->user_lastname = "TOTEM";
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

    public function totem_gracias(Request $request)
    {
        $this->pagar_estacionamiento($request->monto, $request->identificador, 'PYG');
        $factura = new HistorialFactura;
        
        if($request->email == 0)
        {
            $factura->documento = '44444401-7';
            $factura->razon_social = 'Cliente Ocasional';
        }
        else
        {
            $cliente = RucActivo::find($request->email);
            $factura->documento = $cliente->ruc;
            $factura->razon_social = $cliente->nombre;
        }

        if($request->transaccion_tarjeta_id != 0)
        {
            $transaccion = TransaccionTarjetaTotem::find($request->transaccion_tarjeta_id);
            $factura->transaccion_tarjeta_totem_id =  $transaccion->id;
            $factura->authorization_number = $transaccion->codigo_autorizacion;
            $factura->ticket_number  = $transaccion->numero_boleta;
        }

        if($request->transaccion_qr_id != 0)
        {
            $transaccion = QRTransaction::find($request->transaccion_qr_id);
            $factura->transaccion_qr_id =  $transaccion->id;
            $factura->authorization_number = $transaccion->authorization_code;
            $factura->ticket_number  = $transaccion->ticket_number;
        }

        $factura->monto_factura = $request->monto;
        $factura->fecha_factura = date('Y-m-d');
        $factura->identificador = $request->identificador;
        $factura->user_id = 0;
        $factura->punto_venta = $request->equipo;
        $factura->save();

        return view('totems.gracias');
    }

    public function validar_ticket_pago_cero(Request $request)
    {
        $this->pagar_estacionamiento(0, $request->ticket, 'PYG');
        return view('totems.gracias');
    }

    public function dinamic()
    {
        $ventas = HistorialFactura::where('numero_factura', '001-032-0035248')
        ->get();

        foreach($ventas as $venta)
        {
            $serie = explode('-', $venta->numero_factura);
            $body = [
                "Empresa" => "DAYL",
                "Serie" => $serie[0].'-'.$serie[1],
                "Numero" => $serie[2],
                "Fecha" => date('YmdHis', strtotime($venta->created_at)),
                "Cliente" => "CTE-000063",
                "RUC" => $venta->documento,
                "Nombre" => $venta->razon_social,
                "Vendedor" => $venta->punto_venta != 'APP' ? ucfirst(strtolower($venta->punto_venta)) : $venta->punto_venta,
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
        }
        //return $body;
        $pagos = HistorialFactura::where('numero_factura', '001-032-0035248')
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

            if($pago->tipo_tarjeta == "credit" || $pago->tipo_tarjeta == "TC" || $pago->tipo_tarjeta == "CARD")
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
                "Numero" => ltrim($pago->ticket_number, '0'),
                "Vendedor" => $pago->punto_venta != 'APP' ? ucfirst(strtolower($pago->punto_venta)) : $pago->punto_venta,
                "Monto" => $pago->monto_factura,
                "Depositos" => [
                    [
                        "Empresa" => "DAYL",
                        "TipoDoc" => $tipoDoc,
                        "Boleta" => ltrim($pago->ticket_number, '0'),
                        "Fecha" => date('YmdHis', strtotime($pago->created_at)),
                        "Monto" => $pago->monto_factura,
                        "Moneda" => "PYG"
                    ]
                ],
                "Ventas" => [
                    [
                        "Numero" => $serie[2],
                        "Serie" => $serie[0].'-'.$serie[1],
                        "Fecha" => date('YmdHis', strtotime($pago->created_at)),
                        "Monto" => $pago->monto_factura,
                    ]
                ]
            ];
        }
        return $body;
    }


}