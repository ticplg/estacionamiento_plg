<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\Configuracion;
use App\Models\EventoEspecial;
use App\Models\EventoDescuento;
use App\Models\TicketEventoDescuento;
use App\Models\RegistroEstacionamiento;
use App\Models\RegistroDescuentoAplicado;
use App\Models\TicketEventoDescuentoEspecial;
use App\Models\RegistroDescuentoCine;
use Illuminate\Support\Facades\Log;


class DescuentoCine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:descuento-cine';


    protected $description = 'Command description';

    private $parkingDeviceId;
    private $externalDeviceId;
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

    private function cfg($name, $default = null)
    {
        try {
            $valor = Configuracion::where('nombre_parametro', $name)->first()->valor;
            return $valor ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function setConfigurations()
    {
        try {
            $this->usuario          = $this->cfg('usuario', '');
            $this->password         = $this->cfg('password', '');
            $this->externalDeviceId = 457;//$this->cfg('externalDeviceId', '456');
            $this->parkingDeviceId  = 80;//$this->cfg('parkingDeviceId', '82');
            $this->facilityId       = $this->cfg('facilityId', '');
            $this->url              = $this->cfg('url', '');
            $this->url_mega_print   = $this->cfg('url_mega_print', '');
            $this->accessKey        = $this->cfg('accesskey_mega_print', '');
            $this->secretKey        = $this->cfg('secretkey_mega_print', '');

            $this->info('Configuraciones cargadas.');
        } catch (\Throwable $e) {
            \Log::info($e);
            $this->warn('No se pudieron cargar todas las configuraciones. Se usarán valores por defecto donde sea posible.');
        }
    }

    public function handle()
    {
        $this->setConfigurations();

        // Encadenamos, pero cada método se protege por sí mismo
        try {
            $this->descuento_cine();
        } catch (\Throwable $e) {
            $this->error('descuento_cine() falló, continúo con el siguiente paso.');
        }

        $this->info('Proceso general finalizado.');
    }

    public function descuento_parking(TicketEventoDescuento $ticket)
    {
        try {
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
                            <msg:externalDeviceId>'.$this->externalDeviceId.'</msg:externalDeviceId>
                            <msg:parkingDeviceId>'.$this->parkingDeviceId.'</msg:parkingDeviceId>
                        </msg:InsertElectronicValidation>
                    </soapenv:Body>
                </soapenv:Envelope>
            ';

            $client = new Client();

            $response = $client->post($this->url, [
                'headers' => [
                    'Content-Type'  => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':' . $this->password),
                ],
                'body' => $body,
                'timeout' => 20,
            ]);

            if ($response->getStatusCode() === 200) {
                $ticket->fecha_hora_exoneracion = date('Y-m-d H:i:s');
                $ticket->save();
                $this->info("Ticket {$ticket->identificador} exonerado 100%.");
            } else {
                $this->warn("Exoneración retornó status {$response->getStatusCode()} para ticket {$ticket->identificador}");
            }
        } catch (\Throwable $e) {
            \Log::info($e);
            $this->warn("No se pudo exonerar ticket {$ticket->identificador}. Continúo.");
        }
    }

    public function descuento_cine()
    {
        $tickets = RegistroDescuentoCine::whereNull('fecha_exoneracion')
            ->whereBetween('created_at', [
                Carbon::yesterday()->startOfDay(),
                Carbon::today()->endOfDay(),
            ])
            ->get();
        foreach($tickets as $ticket)
        {
            $ok = $this->aplicar_descuento_ticket($ticket->identificador, 35, $ticket);

            if($ok)
            {
                $ticket->fecha_exoneracion = now();
                $ticket->save();
            }
        }
    }

    public function aplicar_descuento_ticket($identificador, $codigo, RegistroDescuentoCine $ticket)
    {
        try 
        {
            $url = $this->url;

            $body = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
                <soapenv:Header/>
                <soapenv:Body>
                    <msg:InsertElectronicValidation>
                        <msg:validationId>APT.VAL.1901198.'.$codigo.'</msg:validationId>
                        <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                            <ns481:Identificator>'.$identificador.'</ns481:Identificator>
                            <ns481:Type>PARK</ns481:Type>
                        </msg:ticketId>
                        <msg:externalDeviceId>'.$this->externalDeviceId.'</msg:externalDeviceId>
                        <msg:parkingDeviceId>'.$this->parkingDeviceId.'</msg:parkingDeviceId>
                    </msg:InsertElectronicValidation>
                </soapenv:Body>
            </soapenv:Envelope>';

            $client = new Client();

            $ticket->body = $body;
            $ticket->save();

            $response = $client->post($url, [
                'headers' => [
                    'Content-Type'  => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':' . $this->password),
                ],
                'body' => $body,
                'timeout' => 20,
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();
            $responseBody = (string) $response->getBody();

            $ticket->response_status = $status;
            $ticket->respuesta_servicio  = $responseBody;
            $ticket->save();

            if ($response->getStatusCode() === 200) {
                $this->info("Descuento {$codigo} aplicado a {$identificador}");
                return true;
            } else {
                $this->warn("Status {$response->getStatusCode()} al aplicar descuento {$codigo} a {$identificador}");
                return false;
            }
        } catch (\Throwable $e) {
            \Log::info($e);
            $this->warn("No se pudo aplicar descuento {$codigo} a {$identificador}. Continúo.");
            return false;
        }
    }

}
