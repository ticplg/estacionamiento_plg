<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\Configuracion;
use App\Models\EventoEspecial;
use App\Models\EventoDescuento;
use Illuminate\Console\Command;
use App\Models\TicketEventoDescuento;
use App\Models\RegistroEstacionamiento;
use App\Models\RegistroDescuentoAplicado;
use App\Models\TicketEventoDescuentoEspecial;
use App\Models\RegistroDescuentoCine;
use Illuminate\Support\Facades\Log;

class AplicarExoneracionTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:aplicar-exoneracion-ticket';

    /**
     * The console command description.
     *
     * @var string
     */
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
            $this->externalDeviceId = 458;//$this->cfg('externalDeviceId', '456');
            $this->parkingDeviceId  = 81;//$this->cfg('parkingDeviceId', '82');
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
            //$this->descuento_cine();
        } catch (\Throwable $e) {
            $this->error('descuento_cine() falló, continúo con el siguiente paso.');
        }

        // Encadenamos, pero cada método se protege por sí mismo
        try {
            $this->descuento_parking_v2();
        } catch (\Throwable $e) {
            $this->error('descuento_parking_v2() falló, continúo con el siguiente paso.');
        }

        try {
            $this->descuento_parking_especial();
        } catch (\Throwable $e) {
            $this->error('descuento_parking_especial() falló, continúo con el siguiente paso.');
        }

        try {
            $this->duracion_zf();
        } catch (\Throwable $e) {
            $this->error('duracion_zf() falló, finalizo el comando.');
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

		$ticket->finalizo_descuento = 1;
                $ticket->save();
            }
	} catch (\Throwable $e) {

             $ticket->finalizo_descuento = 1;
             $ticket->save();

            \Log::info($e);
            $this->warn("No se pudo exonerar ticket {$ticket->identificador}. Continúo.");
        }
    }

    public function descuento_parking_especial()
    {
        $this->info('Iniciando descuento_parking_especial...');
        try {
            $now = Carbon::now('America/Asuncion');

            $eventos = EventoEspecial::where('fecha_hora_inicio', '<=', $now)
                ->where('fecha_hora_fin_exoneracion', '>=', $now)
                //->where('tarifado', 0)
                ->pluck('id');

            $tickets = TicketEventoDescuentoEspecial::whereIn('evento_especial_id', $eventos)->get();
            $this->line('Tickets especiales encontrados: ' . $tickets->count());
        } catch (\Throwable $e) {
            \Log::info($e);
            $this->warn('No se pudieron listar tickets especiales. Continúo.');
            return;
        }

        foreach ($tickets as $ticket) {
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
                    $this->info("Especial exonerado {$ticket->identificador}");
                } else {
			$this->warn("Status {$response->getStatusCode()} en especial para {$ticket->identificador}");

			$ticket->finalizo_descuento = 1;
                        $ticket->save();
                }
	    } catch (\Throwable $e) {

                $ticket->finalizo_descuento = 1;
                $ticket->save();

                \Log::info($e);
                $this->warn("No se pudo exonerar especial {$ticket->identificador}. Continúo.");
            }
        }

        $this->info('Finalizado descuento_parking_especial.');
    }

    public function duracion_zf()
    {
        $this->info('Iniciando actualización de duración en descuentos...');

        try {
            $historico = RegistroDescuentoAplicado::whereNull('duracion')->get();
        } catch (\Throwable $e) {
            \Log::info($e);
            $this->warn('No se pudo obtener historial. Salto paso duracion_zf().');
            return;
        }

        $total = $historico->count();
        $actualizados = 0;
        $omitidos = 0;

        foreach ($historico as $index => $his) {
            try {
                $duracion = RegistroEstacionamiento::where('identificador', $his->identificador)
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($duracion) {
                    $his->duracion = $duracion->parking_duration;
                    $his->save();
                    $actualizados++;
                    $this->line("[" . ($index + 1) . "/$total] Actualizado ID {$his->id} con duración {$his->duracion}");
                } else {
                    $omitidos++;
                    $this->warn("[" . ($index + 1) . "/$total] Sin registro de duración para identificador {$his->identificador}");
                }
            } catch (\Throwable $e) {
                $omitidos++;
                $this->warn("[" . ($index + 1) . "/$total] Error en ID {$his->id}. Continúo.");
            }
        }

        $this->newLine();
        $this->info("Proceso finalizado.");
        $this->info("Total registros: $total");
        $this->info("Actualizados: $actualizados");
        $this->info("Omitidos: $omitidos");
    }


    public function descuento_cine()
    {
        $tickets = RegistroDescuentoCine::whereNull('fecha_exoneracion')->get();
        foreach($tickets as $ticket)
        {
            $ok = $this->aplicar_descuento_ticket($ticket->identificador, 35);

            if($ok)
            {
                $ticket->fecha_exoneracion = now();
                $ticket->save();
            }
        }
    }

    public function descuento_parking_v2()
    {
        $this->info('Iniciando descuento_parking_v2...');

        try {
            $now = Carbon::now('America/Asuncion');

            $eventos = EventoDescuento::where('fecha_hora_inicio', '<=', $now)
                ->where('fecha_hora_fin_exoneracion', '>=', $now)
                ->pluck('id');

            $this->line('Eventos activos encontrados: ' . $eventos->count());

            $tickets = TicketEventoDescuento::whereIn('evento_id', $eventos)->where('finalizo_descuento', 0)->get();
            $this->line('Tickets encontrados: ' . $tickets->count());
        } catch (\Throwable $e) {
            \Log::info($e);
            $this->warn('No se pudieron listar tickets para v2. Salto este paso.');
            return;
        }

        foreach ($tickets as $ticket) {
            try {
                $this->line("Procesando ticket ID {$ticket->id}, identificador {$ticket->identificador}");

                $evento = $ticket->evento ?? null;
                if (!$evento) {
                    $this->warn("Ticket {$ticket->id} sin relación evento. Continúo.");
                    continue;
                }

                if ($evento->tarifado) {
                    $this->line("Evento {$evento->id} es tarifado, verificando monto...");

                    $monto_actual = $this->obtener_monto($ticket->identificador);
                    $this->line("Monto actual: {$monto_actual}");

                    $monto_configurado = $this->cfg($evento->tipo_evento, 0);
                    $this->line("Monto configurado: {$monto_configurado}");

                    $ticket->fecha_hora_exoneracion = date('Y-m-d H:i:s');
                    if($monto_actual > 0)
                    {
                        $ticket->monto_ticket = $monto_actual;
                    }
                    $ticket->save();

                    // Aplica descuentos de 35 hasta que baje al configurado o menos
                    $loop_guard = 0;
                    while ($monto_actual > $monto_configurado && $loop_guard < 20) {
                        $loop_guard++;
                        $this->warn("Monto mayor al configurado, aplicando descuento (iteración {$loop_guard})...");
                        $ok = $this->aplicar_descuento_ticket($ticket->identificador, 35);

                        if (!$ok) {
                            $this->warn('Aplicar descuento devolvió fallo. Corto el ciclo para este ticket.');
                            break;
                        }

                        // Reconsultar
                        $monto_actual = $this->obtener_monto($ticket->identificador);
                        $this->line("Nuevo monto: {$monto_actual}");

                        if($monto_actual > 0)
                        {
                            $ticket->monto_ticket = $monto_actual;
                        }
                        $ticket->fecha_hora_exoneracion = date('Y-m-d H:i:s');
                        $ticket->save();
                    }

                    $this->info("Ticket {$ticket->identificador} procesado (tarifado).");
                } else {
                    $this->info("Evento no tarifado: exoneración al 100%.");
                    $this->descuento_parking($ticket);
                    $ticket->monto_ticket = 0;
                    $ticket->save();
                }
            } catch (\Throwable $e) {
                $ticket->finalizo_descuento = 1;
                $ticket->save();
                $this->warn("Error con ticket {$ticket->identificador}. Continúo con el siguiente.");
            }
        }

        $this->info('Finalizado descuento_parking_v2.');
    }

    public function aplicar_descuento_ticket($identificador, $codigo)
    {
        try {
            
            $ticket = TicketEventoDescuento::where('identificador', $identificador)->first();

            $url = $this->url;

            $body = '
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
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

            $response = $client->post($url, [
                'headers' => [
                    'Content-Type'  => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':' . $this->password),
                ],
                'body' => $body,
                'timeout' => 20,
            ]);

            if ($response->getStatusCode() === 200) {
                $this->info("Descuento {$codigo} aplicado a {$identificador}");
                return true;
            } else {
                $this->warn("Status {$response->getStatusCode()} al aplicar descuento {$codigo} a {$identificador}");
                
                $ticket->finalizo_descuento = 1;
                $ticket->save();

                return false;
            }
        } catch (\Throwable $e) {

            $ticket->finalizo_descuento = 1;
            $ticket->save();

            \Log::info($e);
            $this->warn("No se pudo aplicar descuento {$codigo} a {$identificador}. Continúo.");
            return false;
        }
    }

    public function obtener_monto($identificador)
    {
        try {
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

            $response = $client->post($url, [
                'headers' => [
                    'Content-Type'  => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':' . $this->password),
                ],
                'body' => $body,
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->warn("GetRateInfo status {$statusCode} para {$identificador}");
                return 0.0;
            }

            $responseBody = $response->getBody()->getContents();

            // Parse XML
            $dom = new \DOMDocument();
            $dom->loadXML($responseBody);

            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('soap', 'http://schemas.xmlsoap.org/soap/envelope/');
            $xpath->registerNamespace('msg', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg');
            $xpath->registerNamespace('ns2', 'http://www.skidata.com/contractor/dtaservice/v7/common');
            $xpath->registerNamespace('ns3', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/data');

            $price = $xpath->evaluate('string(//ns3:Price/ns2:Amount)');
            // Otros campos disponibles por si luego querés usarlos:
            // $currencyCode = $xpath->evaluate('string(//ns3:Price/ns2:CurrencyCode)');
            // $netPrice     = $xpath->evaluate('string(//ns3:NetPrice/ns2:Amount)');
            // $netTurnover  = $xpath->evaluate('string(//ns3:NetTurnover/ns2:Amount)');
            // $rateNumber   = $xpath->evaluate('string(//ns3:RateNumber)');
            // $dateTimeEnd  = $xpath->evaluate('string(//ns3:DateTimeEnd)');
            // $rateEnd      = $xpath->evaluate('string(//ns3:RateEnd)');

            $monto = is_numeric($price) ? (float)$price : 0.0;
            return $monto;
        } catch (\Throwable $e) {

            $ticket = TicketEventoDescuento::where('identificador', $identificador)->first();
            if($ticket)
            {
                $ticket->finalizado = 1;
                $ticket->save();
            }
            \Log::info($e);
            $this->warn("No se pudo obtener monto para {$identificador}. Devuelvo 0.");
            return 0.0;
        }
    }
}

