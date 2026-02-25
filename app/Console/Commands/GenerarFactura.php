<?php

namespace App\Console\Commands;

use PDF;
use Storage;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use App\Models\Configuracion;
use App\Models\RucActivo;
use App\Models\PuntoVenta;
use Illuminate\Console\Command;
use App\Models\HistorialFactura;
use App\Models\QRTransaction;
use App\Models\DescuentoEstacionamiento;
use App\Models\BancardTransaction;
use App\Models\TransaccionTarjetaTotem;
use App\Models\RegistroEstacionamientoPago;

class GenerarFactura extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:generar-factura';

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

    private function logStep(string $msg): void
    {
        $this->line('['.date('Y-m-d H:i:s').'] '.$msg);
    }

    private function setConfigurations()
    {
        $this->logStep('Cargando configuraciones...');
        $this->usuario = Configuracion::where('nombre_parametro', 'usuario')->first()->valor ?? null;
        $this->password = Configuracion::where('nombre_parametro', 'password')->first()->valor ?? null;
        $this->externalDeviceId = Configuracion::where('nombre_parametro', 'externalDeviceId')->first()->valor ?? null;
        $this->parkingDeviceId = Configuracion::where('nombre_parametro', 'parkingDeviceId')->first()->valor ?? null;
        $this->facilityId = Configuracion::where('nombre_parametro', 'facilityId')->first()->valor ?? null;
        $this->url = Configuracion::where('nombre_parametro', 'url')->first()->valor ?? null;
        $this->url_mega_print = Configuracion::where('nombre_parametro', 'url_mega_print')->first()->valor ?? null;
        $this->accessKey = Configuracion::where('nombre_parametro', 'accesskey_mega_print')->first()->valor ?? null;
        $this->secretKey = Configuracion::where('nombre_parametro', 'secretkey_mega_print')->first()->valor ?? null;
        $this->info('Configuraciones cargadas.');
    }

    public function handle()
    {
        $this->info('==== Inicio: app:generar-factura ====');
        $started = microtime(true);

        $this->setConfigurations();

        foreach ([
            'generar_factura',
            'obtenerPdf',
            'asignar_tipo_tarjeta',
            'envio_facturas',
            'verificar_codigo_descuento',
            'transacciones_tarjeta',
            'verificar_pagos_qr', // estaba comentado en tu handle original
        ] as $method) {
            try {
                $this->info(">> Ejecutando {$method}()");
                $t0 = microtime(true);
                $this->{$method}();
                $this->info("<< Finalizado {$method}() en ".number_format(microtime(true) - $t0, 2).'s');
            } catch (\Throwable $e) {
                $this->error("!! Error en {$method}(): ".$e->getMessage());
            }
        }

        $this->info('==== Fin: app:generar-factura. Duración total: '.number_format(microtime(true) - $started, 2).'s ====');
    }

    public function generar_factura()
    {
        $this->logStep('Normalizando RUC/razón social pendientes...');
        $facturas = HistorialFactura::whereNull('documento')->get();
        $this->info('Pendientes sin documento: '.$facturas->count());

        foreach ($facturas as $factura) {
            $ruc = RucActivo::where('nombre', $factura->razon_social)->first();
            if ($ruc) {
                $factura->documento = $ruc->ruc;
                $factura->save();
                $this->line(" - Asignado RUC '{$ruc->ruc}' a id={$factura->id}");
            }
        }

        $facturas = HistorialFactura::whereNull('documento')->whereNull('razon_social')->get();
        $this->info('Pendientes sin documento y sin razón social: '.$facturas->count());
        foreach ($facturas as $factura) {
            $factura->documento = '44444401-7';
            $factura->razon_social = 'Cliente Ocacional';
            $factura->save();
            $this->line(" - Set por defecto Cliente Ocasional id={$factura->id}");
        }

        $factura_enviar = HistorialFactura::whereNull('numero_factura')
            ->orderBy('id', 'asc')
            ->take(50)
            ->get();

        $this->info('Facturas a enviar (sin número): '.$factura_enviar->count());

        foreach ($factura_enviar as $enviar) {
            try {
                $this->line(" -> Generando XML + envío: id={$enviar->id}, monto={$enviar->monto_factura}, cliente={$enviar->razon_social}");
                $this->generateAndSendXml($enviar->monto_factura, $enviar->razon_social, $enviar->documento, $enviar->id, $enviar->created_at);
            } catch (\Exception $e) {
                $this->error("   !! Error factura id={$enviar->id}: ".$e->getMessage());
            }
        }
    }

    public function generateAndSendXml($monto, $cliente, $documento, $factura_id, $fecha_hora_emision)
    {
        $factura = HistorialFactura::find($factura_id);
        if (!$factura) {
            $this->warn("   !! HistorialFactura id={$factura_id} no encontrada.");
            return;
        }

        $this->line("   > Obteniendo token...");
        $authResponse = $this->getAuthToken();
        if (!is_array($authResponse) || ($authResponse['status'] ?? 500) !== 200) {
            $this->error('   !! Error al obtener token.');
            return;
        }
        $this->line('   > Token OK');

        $punto_venta = PuntoVenta::where('nombre_punto_venta', $factura->punto_venta)->first();
        if (!$punto_venta) {
            $this->warn("   !! Punto de venta '{$factura->punto_venta}' no encontrado para factura id={$factura->id}");
            return;
        }

        $this->line('   > Generando XML...');
        $xmlContent = $this->generateXmlContent($monto, $cliente, $documento, $punto_venta, $fecha_hora_emision);
        $nombre = time().rand(0, 9999999);
        $filePath = 'xml_files/'.$nombre.'.xml';

        Storage::disk('public')->put($filePath, $xmlContent['xml']);
        Storage::disk('local')->put($filePath, $xmlContent['xml']);
        $factura->path_xml = '/storage/'.$filePath;
        $factura->save();
        $this->line("   > XML guardado en {$filePath}");

        if (!Storage::disk('local')->exists($filePath)) {
            $this->error('   !! XML no existe en storage local tras guardar.');
            return;
        }

        $fileContent = Storage::disk('local')->get($filePath);
        if (empty($fileContent)) {
            $this->error('   !! XML vacío.');
            return;
        }

        $endpoint = $this->url_mega_print.'/ecf';
        $client = new Client();

        try {
            $this->line("   > Enviando XML a {$endpoint} (id_peticion={$factura->id})...");
            $response = $client->post($endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $authResponse['body']['token'],
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
                        'contents' => $factura->id,
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $this->line("   > Respuesta HTTP: {$statusCode}");

            $responseBody = $response->getBody()->getContents();
            $xml = @simplexml_load_string($responseBody);
            if (!$xml) {
                $this->warn('   !! Respuesta no es XML parseable.');
            }

            $factura->fecha_inicio_timbrado = $xmlContent['fecha_inicio_timbrado'];
            $factura->timbrado = $xmlContent['timbrado'];
            $factura->numero_factura = $xmlContent['numero_factura'];
            $factura->cdc = $xmlContent['cadena'];
            $factura->save();

            $punto_venta->numero_siguiente = $punto_venta->numero_siguiente + 1;
            $punto_venta->save();

            $this->info("   > Factura generada OK: nro={$factura->numero_factura}");

        } catch (RequestException $e) {
            $this->error('   !! Error enviando XML: '.$e->getMessage());
        }
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
            $this->line("   > Solicitando token en {$url}...");
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $body,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            $this->line("   > Token HTTP: {$statusCode}");
            return [
                'status' => $statusCode,
                'body' => json_decode($responseBody, true)
            ];

        } catch (RequestException $e) {
            $this->error('   !! Error token: '.$e->getMessage());
        }
    }

    private function generateXmlContent($monto, $cliente, $documento, $punto_venta, $fecha_hora_emision)
    {
        // (SIN CAMBIOS de lógica, solo logs puntuales)
        $this->line('   > Armando contenido XML / cálculos módulo 11...');
        // ... (tu mismo contenido original) ...
        $factura = str_pad($punto_venta->numero_siguiente, 7, '0', STR_PAD_LEFT);

        // Si $fecha_hora_emision ya es Carbon, esto funciona igual.
        // Si es string/DateTime, Carbon::parse lo convierte.
        $fh = $fecha_hora_emision instanceof Carbon
            ? $fecha_hora_emision->copy()
            : Carbon::parse($fecha_hora_emision);

        // Opcional: ajustar zona horaria
        $fh = $fh->setTimezone('America/Asuncion');

        $fecha = $fh->format('Y-m-d\TH:i:s');
        $anho  = $fh->format('Y');
        $mes   = $fh->format('m');
        $dia   = $fh->format('d');



        $dId = '5637173832';
        $cadena = '01801289572'.$punto_venta->codigo_establecimiento.$punto_venta->codigo_sucursal.$factura. '2' . $anho . $mes . $dia . '1999758768';

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

        if ($documento == '44444401-7') {
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
        } elseif (count(explode('-', $documento)) == 2) {
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
        } else {
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
                    <dEst>$punto_venta->codigo_establecimiento</dEst>
                    <dPunExp>$punto_venta->codigo_sucursal</dPunExp>
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

        return [
            'xml'                   => $xmlContent,
            'timbrado'              => $dNumTim,
            'numero_factura'        => $punto_venta->codigo_establecimiento.'-'.$punto_venta->codigo_sucursal.'-'.$factura,
            'fecha_inicio_timbrado' => $dFeIniT,
            'cadena'                => $DE_Id,
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
                return getenv('CHECK_DIGIT_IS10');
            }
        }

        return strval($finalResult);
    }

    public function obtenerPdf()
    {
        $facturas = HistorialFactura::whereNull('path_factura')->whereNotNull('numero_factura')->get();
        $this->info('Generando PDFs pendientes: '.$facturas->count());

        foreach ($facturas as $venta) {
            $this->line(" -> PDF para nro={$venta->numero_factura} (id={$venta->id})");
            $pdf = PDF::loadView('documentos_emitidos.factura_pdf', compact('venta'))
                ->setPaper([0, 0, 400.77, 900.89], 'portrait');

            $fileName = $venta->numero_factura . '.pdf';
            $path = 'facturas/' . $fileName;

            $pdf->save(storage_path('app/public/' . $path));

            $venta->path_factura = 'storage/' . $path;
            $venta->save();

            $this->line("   > Guardado: {$venta->path_factura}");
        }
    }

    public function asignar_tipo_tarjeta()
    {
        $historicos = HistorialFactura::whereNull('tipo_tarjeta')->where('transaccion_tarjeta_id', '!=', 0)->get();
        $this->info('Asignando tipo tarjeta (Bancard API) pendientes: '.$historicos->count());

        foreach ($historicos as $historico) {
            $url = "https://app.paseolagaleria.com.py/api/vales/verificaroperacion";
            $client = new Client();

            $body = [
                "transaccion_id" => $historico->transaccion_tarjeta_id,
            ];

            try {
                $this->line(" -> Consultando card_type (transaccion_tarjeta_id={$historico->transaccion_tarjeta_id})");
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
                    $historico->save();
                    $this->line("   > Asignado tipo_tarjeta={$historico->tipo_tarjeta} a id={$historico->id}");
                }

            } catch (RequestException $e) {
                $this->error('   !! Error verificaroperacion: '.$e->getMessage());
            }
        }

        $historicos = HistorialFactura::whereNull('tipo_tarjeta')->where('transaccion_qr_id', '!=', 0)->get();
        $this->info('Asignando tipo tarjeta desde QRTransaction: '.$historicos->count());
        foreach ($historicos as $historico) {
            $transaccion = QRTransaction::find($historico->transaccion_qr_id);
            if ($transaccion) {
                $historico->tipo_tarjeta = $transaccion->account_type;
                $historico->save();
                $this->line("   > (QR) id={$historico->id} tipo_tarjeta={$historico->tipo_tarjeta}");
            }
        }

        $historico_totem_tarjeta = HistorialFactura::whereNull('tipo_tarjeta')->where('transaccion_tarjeta_totem_id', '!=', 0)->get();
        $this->info('Asignando tipo tarjeta desde Totem: '.$historico_totem_tarjeta->count());
        foreach ($historico_totem_tarjeta as $historico) {
            $transaccion = TransaccionTarjetaTotem::find($historico->transaccion_tarjeta_totem_id);
            if ($transaccion) {
                if ($transaccion->issuerId == 'AC' || $transaccion->issuerId == 'MC' || $transaccion->issuerId == 'VC' || $transaccion->issuerId == '') {
                    $historico->tipo_tarjeta = 'CARD';
                } else {
                    $historico->tipo_tarjeta = 'DEBIT';
                }
                $historico->save();
                $this->line("   > (Totem) id={$historico->id} tipo_tarjeta={$historico->tipo_tarjeta}");
            }
        }
    }

    public function envio_facturas()
    {
        $ventas = HistorialFactura::where('factura_sincronizada', 0)
            ->whereNotNull('numero_factura')
            ->whereNotNull('tipo_tarjeta')
            ->get();
        $this->info('Enviando facturas a Nexcel pendientes: '.$ventas->count());

        foreach ($ventas as $venta) {
            $serie = explode('-', $venta->numero_factura);
            $client = new Client();
            $url = 'https://app.nexcelgt.com/wsrestful/api/registrarfactura';

            $headers = ['Content-Type' => 'application/json'];

            if ($venta->tipo_tarjeta == "credit" || $venta->tipo_tarjeta == "TC" || $venta->tipo_tarjeta == "CARD") {
                $tipoDoc = "CARD";
            } else {
                $tipoDoc = "DEBIT";
            }

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
                "Identificador" => (mb_strlen($venta->identificador) > 15) ?  $venta->identificador : null,
                "TipoDoc" => $tipoDoc, /// "DEBIT"
                "Boleta" => ltrim($venta->ticket_number, '0'),
                "Items" => [
                    [
                        "SKU" => "PARK",
                        "Cantidad" => 1,
                        "Precio" => $venta->monto_factura,
                        "TotalLinea" => $venta->monto_factura
                    ]
                ]
            ];

            $this->line(" -> Enviando factura nro={$venta->numero_factura} (id={$venta->id})");
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $body
            ]);

            $res = json_decode($response->getBody(), true);
            $this->line("   > Respuesta CodigoRes=".$res['CodigoRes']." Mensaje=".$res['Mensaje']);

            if ($res["CodigoRes"] == "0") {
                $venta->factura_sincronizada = 1;
                $venta->mensaje_factura = $res["Mensaje"];
                $venta->save();
            } else {
                $venta->mensaje_factura = $res["Mensaje"];
                $venta->save();
            }
        }

        $pagos = HistorialFactura::where('pago_sincronizado', 0)
            ->whereNotNull('numero_factura')
            ->whereNotNull('tipo_tarjeta')
            ->get();
        $this->info('Enviando pagos a Nexcel pendientes: '.$pagos->count());

        foreach ($pagos as $pago) {
            $client = new Client();
            $serie = explode('-', $pago->numero_factura);
            $url = 'https://app.nexcelgt.com/wsrestful/api/RegistraLiquidacion';
            $headers = ['Content-Type' => 'application/json'];

            if ($pago->tipo_tarjeta == "credit" || $pago->tipo_tarjeta == "TC" || $pago->tipo_tarjeta == "CARD") {
                $tipoDoc = "CARD";
            } else {
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

            $this->line(" -> Enviando pago ticket={$pago->ticket_number} (id={$pago->id}) tipo={$tipoDoc}");
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $body
            ]);

            $res = json_decode($response->getBody(), true);
            $this->line("   > Respuesta CodigoRes=".$res['CodigoRes']." Mensaje=".$res['Mensaje']);

            if ($res["CodigoRes"] == "0") {
                $pago->pago_sincronizado = 1;
                $pago->mensaje_pago =  $res["Mensaje"];
                $pago->save();
            } else {
                $pago->pago_sincronizado = 1;
                $pago->mensaje_pago =  $res["Mensaje"];
                $pago->save();
            }
        }
    }

    public function verificar_codigo_descuento()
    {
        $descuentos = DescuentoEstacionamiento::where('activo', 1)->get();
        $this->info('Verificando códigos de descuento activos: '.$descuentos->count());

        foreach ($descuentos as $descuento) {
            if ($descuento->fecha_hasta < date('Y-m-d')) {
                $descuento->activo = 0;
                $descuento->save();
                $this->line(" -> Desactivado descuento id={$descuento->id} (vencido)");
            }
        }
    }

    public function transacciones_tarjeta()
    {
        $url = "https://app.paseolagaleria.com.py/api/vales/obteneroperacionestarjeta";
        $client = new Client();

        try {
            $this->info('Obteniendo operaciones de tarjeta...');
            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'token' => 'KOwSyZzaCYybBCZTdZb2gNhnnJ59ywRRh83jY2f3XmLqW18QW0tUN8wrGrc07xVl4s9FvL6eEFlh3DIu',
                ],
            ]);

            $datos = json_decode($response->getBody()->getContents(), true)["datos"] ?? [];
            $this->info('Transacciones recibidas: '.count($datos));

            $urlConfirm = "https://app.paseolagaleria.com.py/api/vales/confirmarpagorecibido";

            foreach ($datos as $dato) {
                $transaccion = new BancardTransaction;
                $transaccion->id =  $dato["id"];
                $transaccion->usuario_id =  $dato["usuario_id"];
                $transaccion->amount =  $dato["amount"];
                $transaccion->currency =  $dato["currency"];
                $transaccion->status =  $dato["status"];
                $transaccion->subject =  $dato["subject"];
                $transaccion->subject_id =  $dato["subject_id"];
                $transaccion->request_data =  $dato["request_data"];
                $transaccion->response_data =  $dato["response_data"];
                $transaccion->created_at =  $dato["created_at"];
                $transaccion->updated_at =  $dato["updated_at"];
                $transaccion->card_id =  $dato["card_id"];
                $transaccion->error =  $dato["error"];
                $transaccion->manual =  $dato["manual"];
                $transaccion->conciliado =  $dato["conciliado"];
                $transaccion->ci =  $dato["ci"];
                $transaccion->email =  $dato["email"];
                $transaccion->fullname =  $dato["full_name"];
                $transaccion->tipo_tarjeta =  $dato["tipo_tarjeta"];
                $transaccion->save();

                $this->line(" -> Guardada transaccion id={$transaccion->id}");

                $body = ["transaccion_id" => $transaccion->id];

                $response = $client->get($urlConfirm, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'token' => 'KOwSyZzaCYybBCZTdZb2gNhnnJ59ywRRh83jY2f3XmLqW18QW0tUN8wrGrc07xVl4s9FvL6eEFlh3DIu',
                    ],
                    'json' => $body,
                ]);

                $this->line("   > Confirmado pago id={$transaccion->id}");
            }

            $transacciones = BancardTransaction::where('factura_id', 0)
                ->where('status', 'Success')
                ->get();
            $this->info('Asociando transacciones a facturas (pendientes): '.$transacciones->count());

            foreach ($transacciones as $transaccion) {
                $factura = HistorialFactura::where('transaccion_tarjeta_id', $transaccion->id)->first();
                if ($factura) {
                    $transaccion->factura_id = $factura->id;
                    $transaccion->save();
                    $this->line(" -> Vinculada transaccion {$transaccion->id} -> factura {$factura->id}");
                }
            }

        } catch (RequestException $e) {
            $this->error('!! Error transacciones_tarjeta: '.$e->getMessage());
        }
    }

    public function verificar_pagos_qr()
    {
        // (SIN cambios, acá solo algunos logs si luego lo reactivás en handle)
        $operaciones = QRTransaction::where('status', 'confirmed')
            ->where('fecha_hora', '>=', '2025-08-01 00:00:00')
            ->where('factura_id', 0)
            ->get();

        $this->info('Verificando pagos QR confirmados: '.$operaciones->count());

        $cantidad = 0;
        foreach ($operaciones as $operacion) {
            $historico =  HistorialFactura::where('ticket_number', $operacion->ticket_number)->first();
            if (!$historico) {
                if ($operacion->created_at >= '2024-02-06 00:00:00') {
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

                    $cantidad++;
                    $this->line(" -> Generada factura por QR id={$factura->id}");
                }
            } else {
                $operacion->factura_id = $historico->id;
                $operacion->save();
                $this->line(" -> Vinculada operación QR {$operacion->id} a factura {$historico->id}");
            }
        }

        $this->info("Total facturas generadas por QR en este ciclo: {$cantidad}");

        $historico =  HistorialFactura::where('identificador', '0')->get();
        foreach ($historico as $his) {
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

            $this->line(" -> Normalizado identificador en HistorialFactura id={$his->id}");
        }

        $tarjetas = BancardTransaction::where('status', 'Success')
            ->where('created_at', '>=', '2025-08-01 00:00:00')
            ->where('factura_id', 0)
            ->get();
        $this->info('Evaluando tarjetas sin factura desde 2025-08-01: '.$tarjetas->count());

        foreach ($tarjetas as $tarjeta) {
            $historico = HistorialFactura::where('ticket_number', json_decode($tarjeta->response_data)->confirmation->ticket_number)
                ->first();

            if (!$historico) {
                $historico =  HistorialFactura::where('user_id', $tarjeta->usuario_id)->orderBy('id', 'DESC')->first();

                $razon_social = "Cliente Ocasional";
                $documento = "44444401-7";

                if ($historico) {
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

                $this->line(" -> Generada factura por tarjeta id={$factura->id}");

            } else {
                $tarjeta->factura_id = $historico->id;
                $tarjeta->save();
                $this->line(" -> Vinculada tarjeta {$tarjeta->id} a factura {$historico->id}");
            }
        }


        $tarjeta_totem = TransaccionTarjetaTotem::where('created_at', '>=', '2025-08-01 00:00:00')
        ->where('factura_id', 0)
        ->get();

        foreach($tarjeta_totem as $tarjeta)
        {

            $historico = HistorialFactura::where('ticket_number', $tarjeta->numero_boleta)
            ->first();

            if(!$historico)
            {
                $historico =  HistorialFactura::where('user_id', $tarjeta->usuario_id)
                ->orderBy('id', 'DESC')
                ->first();

                $razon_social = "Cliente Ocasional";
                $documento = "44444401-7";

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
}


