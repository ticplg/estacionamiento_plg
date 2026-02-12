<?php

namespace App\Console\Commands;

use Storage;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\Configuracion;
use App\Models\HistorialFactura;
use Illuminate\Console\Command;

class DocumentosRefacturacion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:documentos-refacturacion';

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
        $this->setConfigurations();
        $this->generar_factura();
    } 

    public function generar_factura()
    {
        $factura_enviar = HistorialFactura::where('documento', '44444401-7')
        ->where('id', '<', 5208)->get();

        $this->info('ENTRA A GENERAR');

        //$factura_enviar = HistorialFactura::where('id', '=', 5208)->get();

        foreach ($factura_enviar as $enviar) {
            try {
                $monto = $enviar->monto_factura;
                $cliente = $enviar->razon_social;
                $documento = $enviar->documento;
                $this->generateAndSendXml($monto, $cliente, $documento, $enviar->id);
                $this->info('FACTURA: '. $enviar->numero_factura . ' ENVIADA');
            } catch (\Exception $e) {
                // Captura el error y continúa con el siguiente registro
                $this->info("Error al procesar la factura con ID {$enviar->id}: " . $e->getMessage());
                \Log::error("Error al procesar la factura con ID {$enviar->id}: " . $e->getMessage());
            }
        }

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
        $numero_factura = $factura->numero_factura;
        $xmlContent = $this->generateXmlContent($monto, $cliente, $documento, $numero_factura, $factura_id);
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

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            // Parsear la respuesta XML a JSON
            $xml = simplexml_load_string($responseBody);
            $json = json_encode($xml);


            $factura->fecha_inicio_timbrado = $xmlContent["fecha_inicio_timbrado"];
            $factura->timbrado = $xmlContent["timbrado"];
            $factura->numero_factura = $xmlContent["numero_factura"];
            $factura->cdc = $xmlContent["cadena"];
            $factura->save();

            //$numero_factura->valor = $numero_factura->valor + 1; 
            //$numero_factura->save();



        } 
        catch (RequestException $e) 
        {
            \Log::error($e->getMessage());
        }
    }


    private function generateXmlContent($monto, $cliente, $documento, $numero_factura, $factura_id)
    {

        $detalles_factura = HistorialFactura::find($factura_id);

        $factura = explode('-', $numero_factura)[2];
        $fecha = $detalles_factura->created_at->format('Y-m-d\TH:i:s');
        $anho = $detalles_factura->created_at->format('Y');
        $mes = $detalles_factura->created_at->format('m');
        $dia = $detalles_factura->created_at->format('d');
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
        if($documento == '44444401-7')
        {
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
        }
        elseif(count(explode('-', $documento)) == 2)
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
                <iTipIDRec>1</iTipIDRec>
                <dDTipIDRec>Cédula paraguaya</dDTipIDRec>
                <dNumIDRec>$documento</dNumIDRec>
                <dNomRec>$dNomRec</dNomRec>
                <dCodCliente>$dCodCliente</dCodCliente>
            </gDatRec>
            XML;
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
            \Log::error($e->getMessage());
        }
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
}
