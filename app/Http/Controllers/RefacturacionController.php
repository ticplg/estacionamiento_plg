<?php

namespace App\Http\Controllers;

use DB;
use Storage;
use App\Models\HistorialFactura;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class RefacturacionController extends Controller
{
    public function anular_factura()
    {
        $rows = HistorialFactura::where('punto_venta', 'TOTEM1')
        ->where('updated_at', '>=', '2025-09-30 00:00:00')
        ->where('id', '>=', 70541)
        ->get();

        foreach($rows as $row)
        {
            $row->factura_anulada = $row->numero_factura;
            $row->save();
            return $this->generateAndSendXml($row->cdc, $row->id);
        }

    }

    public function generateAndSendXml($cdc, $factura_id)
    {

        $authResponse = $this->getAuthToken();
        if (!is_array($authResponse) || ($authResponse['status'] ?? 500) !== 200) {
            $this->error('   !! Error al obtener token.');
            return;
        }

        $xmlContent = $this->generateXmlContent($cdc);
        $nombre = time();
        $filePath = 'xml_files/'.$nombre.'.xml';

        Storage::disk('public')->put($filePath, $xmlContent['xml']);
        Storage::disk('local')->put($filePath, $xmlContent['xml']);

        if (!Storage::disk('local')->exists($filePath)) {
            $this->error('   !! XML no existe en storage local tras guardar.');
            return;
        }

        $fileContent = Storage::disk('local')->get($filePath);
        if (empty($fileContent)) {
            $this->error('   !! XML vacío.');
            return;
        }

        $endpoint = "https://services.ifacere-kude.com/api/ecf/anulacion";
        $client = new Client();

        try {
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
                        'contents' => $factura_id,
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();

            return $responseBody = $response->getBody()->getContents();

        } catch (RequestException $e) {
           
        }
    }

    private function generateXmlContent(string $cdc)
    {
        // ID único de envío
        $dId = '5637173832';

        // Fecha/hora de firma en PY
        $dFecFirma = now('America/Asuncion')->format('Y-m-d\TH:i:s');

        // Escapar por seguridad (si $cdc siempre es alfanumérico podés omitir)
        $cdcEsc = htmlspecialchars($cdc, ENT_XML1, 'UTF-8');

        $xmlContent = <<<XML
    <rEnviEventoDe xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <dId>{$dId}</dId>
    <dEvReg>
        <gGroupGesEve
        xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd siRecepEventoDe_v150.xsd"
        xmlns="http://ekuatia.set.gov.py/sifen/xsd"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
        <rGesEve xmlns="http://ekuatia.set.gov.py/sifen/xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsd Evento_v150.xsd">
            <rEve Id="250306001">
            <dFecFirma>{$dFecFirma}</dFecFirma>
            <dVerFor>150</dVerFor>
            <gGroupTiEvt>
                <rGeVeCan>
                <Id>{$cdcEsc}</Id>
                <mOtEve>CANCELACION</mOtEve>
                </rGeVeCan>
            </gGroupTiEvt>
            </rEve>
        </rGesEve>
        </gGroupGesEve>
    </dEvReg>
    </rEnviEventoDe>
    XML;

        return ['xml' => $xmlContent];
    }


    public function getAuthToken()
    {
        $url = "https://services.ifacere-kude.com/api/token";
        $client = new Client();

        $body = [
            "accessKey" => "80128957",
            "secretKey" => "*BcyMqJd6WsSNXsKmO#%qDlgy",
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
        }
    }

}
