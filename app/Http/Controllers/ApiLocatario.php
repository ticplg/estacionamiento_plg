<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Configuracion;
use App\Models\UsuarioLocatario;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Models\RegistroDescuentoLocatario;
use Illuminate\Support\Facades\Validator;

class ApiLocatario extends Controller
{
    private $externalDeviceId;
    private $parkingDeviceId;
    private $usuario;
    private $password;
    private $facilityId;
    private $url;

    public function __construct()
    {
        $this->usuario = Configuracion::where('nombre_parametro', 'usuario')->first()->valor;
        $this->password = Configuracion::where('nombre_parametro', 'password')->first()->valor;
        $this->facilityId = Configuracion::where('nombre_parametro', 'facilityId')->first()->valor;
        $this->url = Configuracion::where('nombre_parametro', 'url')->first()->valor;

        // Si existen parámetros propios de locatarios se usan; si no, los generales
        $this->externalDeviceId = $this->parametro('locatario_externalDeviceId') ?? $this->parametro('externalDeviceId');
        $this->parkingDeviceId = $this->parametro('locatario_parkingDeviceId') ?? $this->parametro('parkingDeviceId');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'usuario' => 'required|string',
            'password' => 'required|string',
        ], [
            'usuario.required' => 'El usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $usuario = UsuarioLocatario::where('usuario', $request->usuario)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Usuario o contraseña incorrectos.'
            ], 401);
        }

        if (!$usuario->estaActivo()) {
            return response()->json([
                'status' => 403,
                'message' => 'Usuario inactivo. Contactar con el administrador.'
            ], 403);
        }

        // Cada login invalida la sesión anterior del usuario
        $token = Str::random(60);
        $usuario->api_token = hash('sha256', $token);
        $usuario->save();

        return response()->json([
            'status' => 200,
            'message' => 'Has ingresado correctamente.',
            'token' => $token,
            'data' => $this->datos_usuario($usuario),
        ], 200);
    }

    public function perfil(Request $request)
    {
        return response()->json([
            'status' => 200,
            'data' => $this->datos_usuario($request->attributes->get('usuario_locatario')),
        ], 200);
    }

    public function logout(Request $request)
    {
        $usuario = $request->attributes->get('usuario_locatario');
        $usuario->api_token = null;
        $usuario->save();

        return response()->json([
            'status' => 200,
            'message' => 'Sesión cerrada.'
        ], 200);
    }

    // Se consulta al leer el QR, antes de mostrar el formulario
    public function verificar_ticket(Request $request)
    {
        $identificador = $this->identificador_qr((string) $request->query('qr', ''));

        if ($identificador === null) {
            return response()->json([
                'status' => 422,
                'message' => 'Por favor, vuelva a pasar el código QR por el escáner.'
            ], 422);
        }

        if ($this->descuento_aplicado($identificador)) {
            return response()->json([
                'status' => 409,
                'message' => 'El ticket ya tiene un descuento de locatario aplicado.',
                'data' => ['identificador' => $identificador],
            ], 409);
        }

        return response()->json([
            'status' => 200,
            'data' => ['identificador' => $identificador],
        ], 200);
    }

    public function aplicar_descuento(Request $request)
    {
        $usuario = $request->attributes->get('usuario_locatario');

        $validator = Validator::make($request->all(), [
            'qr' => 'required|string',
            'nombre' => 'nullable|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'nro_factura' => 'nullable|string|max:255',
        ], [
            'qr.required' => 'El código QR es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $identificador = $this->identificador_qr($request->qr);

        if ($identificador === null) {
            return response()->json([
                'status' => 422,
                'message' => 'Por favor, vuelva a pasar el código QR por el escáner.'
            ], 422);
        }

        // Evita que dos lecturas simultáneas del mismo ticket apliquen dos descuentos
        $lock = Cache::lock('descuento_locatario_' . $identificador, 60);

        if (!$lock->get()) {
            return response()->json([
                'status' => 409,
                'message' => 'El ticket se está procesando. Intente de nuevo en unos segundos.'
            ], 409);
        }

        try {
            if ($this->descuento_aplicado($identificador)) {
                return response()->json([
                    'status' => 409,
                    'message' => 'El ticket ya tiene un descuento de locatario aplicado.'
                ], 409);
            }

            $monto_ticket = $this->obtener_monto($identificador);

            if ($monto_ticket === null) {
                return response()->json([
                    'status' => 422,
                    'message' => 'No se pudo consultar el saldo del ticket.'
                ], 422);
            }

            if ($monto_ticket <= 0) {
                return response()->json([
                    'status' => 422,
                    'message' => 'El ticket no tiene saldo pendiente.'
                ], 422);
            }

            $validacion = $this->insertar_validacion($identificador, $usuario->codigo_descuento);

            $monto_final = $validacion['status'] == 200 ? $this->obtener_monto($identificador) : null;

            $registro = RegistroDescuentoLocatario::create([
                'usuario_id' => $usuario->id,
                'fecha_registro' => now(),
                'identificador' => $identificador,
                'codigo_descuento' => $usuario->codigo_descuento,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'nro_factura' => $request->nro_factura,
                'monto_ticket' => $monto_ticket,
                'monto_descuento' => $monto_final !== null ? $monto_ticket - $monto_final : null,
                'monto_final' => $monto_final,
                'response_status' => $validacion['status'],
                'response_body' => $validacion['body'],
            ]);
        } finally {
            $lock->release();
        }

        if ($validacion['status'] != 200) {
            return response()->json([
                'status' => 500,
                'message' => 'No se pudo aplicar el descuento en el ticket.',
                'data' => $registro,
            ], 500);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Descuento aplicado correctamente.',
            'data' => $registro,
        ], 200);
    }

    // Descuentos aplicados por el local en un rango de fechas (pantalla de reporte de la app)
    public function reporte_descuentos(Request $request)
    {
        $reporte = $this->datos_reporte($request);

        if (isset($reporte['error'])) {
            return response()->json([
                'status' => 422,
                'message' => $reporte['error']
            ], 422);
        }

        return response()->json([
            'status' => 200,
            'data' => $reporte,
        ], 200);
    }

    private function datos_reporte(Request $request)
    {
        $validator = Validator::make($request->query(), [
            'desde' => 'required|date_format:Y-m-d',
            'hasta' => 'required|date_format:Y-m-d|after_or_equal:desde',
        ], [
            'desde.required' => 'La fecha desde es obligatoria.',
            'hasta.required' => 'La fecha hasta es obligatoria.',
            'desde.date_format' => 'La fecha desde no es válida.',
            'hasta.date_format' => 'La fecha hasta no es válida.',
            'hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
        ]);

        if ($validator->fails()) {
            return ['error' => $validator->errors()->first()];
        }

        $desde = Carbon::createFromFormat('Y-m-d', $request->query('desde'))->startOfDay();
        $hasta = Carbon::createFromFormat('Y-m-d', $request->query('hasta'))->endOfDay();

        if ($desde->copy()->addMonthsNoOverflow(3)->lt($hasta->copy()->startOfDay())) {
            return ['error' => 'El rango máximo es de 3 meses.'];
        }

        // El reporte es del local: incluye los descuentos de todos sus usuarios (cajas)
        $local = $request->attributes->get('usuario_locatario')->local;
        $usuarios = UsuarioLocatario::where('local', $local)->pluck('usuario', 'id');

        $descuentos = RegistroDescuentoLocatario::whereIn('usuario_id', $usuarios->keys())
            ->where('response_status', 200)
            ->whereBetween('fecha_registro', [$desde, $hasta])
            ->orderBy('fecha_registro')
            ->get()
            ->map(function ($registro) use ($usuarios) {
                return [
                    'fecha_registro' => $registro->fecha_registro->format('Y-m-d H:i:s'),
                    'identificador' => $registro->identificador,
                    'nombre' => $registro->nombre,
                    'apellido' => $registro->apellido,
                    'nro_factura' => $registro->nro_factura,
                    'monto_ticket' => $registro->monto_ticket,
                    'monto_descuento' => $registro->monto_descuento,
                    'monto_final' => $registro->monto_final,
                    'usuario' => $usuarios[$registro->usuario_id] ?? null,
                ];
            });

        return [
            'local' => $local,
            'desde' => $desde->format('Y-m-d'),
            'hasta' => $hasta->format('Y-m-d'),
            'resumen' => [
                'cantidad' => $descuentos->count(),
                'monto_ticket' => (int) $descuentos->sum('monto_ticket'),
                'monto_descuento' => (int) $descuentos->sum('monto_descuento'),
                'monto_final' => (int) $descuentos->sum('monto_final'),
            ],
            'descuentos' => $descuentos->values()->all(),
        ];
    }

    private function datos_usuario(UsuarioLocatario $usuario)
    {
        return [
            'id' => $usuario->id,
            'usuario' => $usuario->usuario,
            'local' => $usuario->local,
            'codigo_descuento' => $usuario->codigo_descuento,
        ];
    }

    // Si el QR trae una URL, el identificador es lo que viene después de la última /
    private function identificador_qr($qr)
    {
        $identificador = trim(Str::afterLast(trim($qr), '/'));

        return preg_match('/^[A-Za-z0-9]{23}$/', $identificador) ? $identificador : null;
    }

    private function descuento_aplicado($identificador)
    {
        return RegistroDescuentoLocatario::where('identificador', $identificador)
            ->where('response_status', 200)
            ->exists();
    }

    private function parametro($nombre)
    {
        return Configuracion::where('nombre_parametro', $nombre)->value('valor');
    }

    // Saldo del ticket en PYG (GetRateInfo). Ya incluye los descuentos aplicados.
    private function obtener_monto($identificador)
    {
        $body = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
            <soapenv:Header/>
            <soapenv:Body>
                <msg:GetRateInfo xmlns:dta="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <msg:facilityId>' . $this->facilityId . '</msg:facilityId>
                    <msg:ticketId xsi:type="dta:GenericIdentification">
                        <dta:Identificator>' . $identificador . '</dta:Identificator>
                        <dta:Type>PARK</dta:Type>
                    </msg:ticketId>
                    <msg:externalDeviceId>' . $this->externalDeviceId . '</msg:externalDeviceId>
                    <msg:parkingDeviceId>' . $this->parkingDeviceId . '</msg:parkingDeviceId>
                </msg:GetRateInfo>
            </soapenv:Body>
        </soapenv:Envelope>';

        $respuesta = $this->enviar_soap($body);

        if ($respuesta['status'] != 200) {
            return null;
        }

        $dom = new \DOMDocument();

        if (!@$dom->loadXML($respuesta['body'])) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('ns2', 'http://www.skidata.com/contractor/dtaservice/v7/common');
        $xpath->registerNamespace('ns3', 'http://www.skidata.com/interfaces/parking/ticketManagement/v4/data');

        $price = $xpath->evaluate('string(//ns3:Price/ns2:Amount)');

        return is_numeric($price) ? (int) $price : null;
    }

    // InsertElectronicValidation: 200 = aplicado, 500 = SOAP Fault
    private function insertar_validacion($identificador, $codigo)
    {
        $body = '
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:msg="http://www.skidata.com/interfaces/parking/ticketManagement/v4/msg">
            <soapenv:Header/>
            <soapenv:Body>
                <msg:InsertElectronicValidation>
                    <msg:validationId>APT.VAL.1901198.' . $codigo . '</msg:validationId>
                    <msg:ticketId xsi:type="ns481:GenericIdentification" xmlns:ns481="http://www.skidata.com/contractor/dtaservice/v7/common" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                        <ns481:Identificator>' . $identificador . '</ns481:Identificator>
                        <ns481:Type>PARK</ns481:Type>
                    </msg:ticketId>
                    <msg:externalDeviceId>' . $this->externalDeviceId . '</msg:externalDeviceId>
                    <msg:parkingDeviceId>' . $this->parkingDeviceId . '</msg:parkingDeviceId>
                </msg:InsertElectronicValidation>
            </soapenv:Body>
        </soapenv:Envelope>';

        return $this->enviar_soap($body);
    }

    private function enviar_soap($body)
    {
        $client = new Client();

        try {
            $response = $client->post($this->url, [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($this->usuario . ':' . $this->password),
                ],
                'body' => $body,
                'timeout' => 30,
                // Un SOAP Fault llega como 500: se devuelve el status en vez de lanzar excepción
                'http_errors' => false,
            ]);

            return [
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
            ];
        } catch (\Exception $e) {
            Log::error('Skidata locatarios: ' . $e->getMessage());

            return [
                'status' => 0,
                'body' => $e->getMessage(),
            ];
        }
    }
}
