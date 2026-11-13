<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConsultaRucService
{
    /**
     * Consulta un RUC (sin DV) en api.consulta-ruc.com.py y, si ese servicio falla, en futura100 (Sifen).
     * Devuelve ['ruc', 'persona', 'estado', 'esFacturador'] o null si no existe o fallan los dos servicios.
     */
    public function consultar($ruc)
    {
        $datos = $this->consultarConsultaRuc($ruc);

        // false = el servicio respondió que no existe; null = el servicio falló y se prueba el backup
        if($datos === null)
        {
            Log::warning('consulta-ruc: sin respuesta para '.$ruc.', se consulta futura100');
            $datos = $this->consultarFutura100($ruc);
        }

        return $datos ?: null;
    }

    /**
     * api.consulta-ruc.com.py
     * Devuelve los datos, false si el RUC no existe o null si el servicio falló.
     */
    private function consultarConsultaRuc($ruc)
    {
        $url = rtrim(config('services.consulta_ruc.url'), '/');
        $client = new Client();

        try
        {
            // El token dura 24 h; se cachea para no hacer login en cada consulta
            $token = Cache::remember('consulta_ruc_token', now()->addHours(23), function () use ($client, $url) {
                $res = $client->post($url.'/auth/signin', [
                    'json' => [
                        'email' => config('services.consulta_ruc.email'),
                        'password' => config('services.consulta_ruc.password'),
                    ],
                    'timeout' => 15,
                ]);

                return json_decode($res->getBody()->getContents(), true)['token'] ?? null;
            });

            if(!$token)
            {
                Cache::forget('consulta_ruc_token');
                Log::error('consulta-ruc: login sin token');
                return null;
            }

            $res = $client->get($url.'/consultas-ruc/'.urlencode($ruc), [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ],
                'http_errors' => false,
                'timeout' => 15,
            ]);

            if($res->getStatusCode() == 401 || $res->getStatusCode() == 403)
            {
                // Token vencido o inválido: se descarta para que la próxima consulta vuelva a hacer login
                Cache::forget('consulta_ruc_token');
                Log::warning('consulta-ruc: token rechazado ('.$res->getStatusCode().')');
                return null;
            }

            if($res->getStatusCode() == 404)
            {
                return false;
            }

            if($res->getStatusCode() != 200)
            {
                Log::warning('consulta-ruc: respuesta '.$res->getStatusCode());
                return null;
            }

            return json_decode($res->getBody()->getContents(), true)['data'][0] ?? false;
        }
        catch(\Throwable $e)
        {
            Log::error('consulta-ruc: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Backup: ws-integracion.futura100.com.py (consulta en Sifen).
     * No devuelve el DV, así que se calcula con módulo 11.
     * Devuelve los datos, false si el RUC no existe o null si el servicio falló.
     */
    private function consultarFutura100($ruc)
    {
        if(!ctype_digit((string) $ruc))
        {
            return false;
        }

        $url = rtrim(config('services.futura100.url'), '/');
        $client = new Client();

        try
        {
            // El token dura unas 8 h; se cachea 7 h
            $token = Cache::remember('futura100_token', now()->addHours(7), function () use ($client, $url) {
                $res = $client->post($url.'/login', [
                    'json' => [
                        'ruc' => config('services.futura100.ruc'),
                        'password' => config('services.futura100.password'),
                    ],
                    'timeout' => 15,
                ]);

                return json_decode($res->getBody()->getContents(), true)['token'] ?? null;
            });

            if(!$token)
            {
                Cache::forget('futura100_token');
                Log::error('futura100: login sin token');
                return null;
            }

            $res = $client->post($url.'/consulta-ruc', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ],
                'json' => ['rucs' => [(string) $ruc]],
                'http_errors' => false,
                'timeout' => 20,
            ]);

            if($res->getStatusCode() != 200)
            {
                // Sin token o vencido responde 401/403/415: se descarta para volver a hacer login
                Cache::forget('futura100_token');
                Log::warning('futura100: respuesta '.$res->getStatusCode());
                return null;
            }

            $item = json_decode($res->getBody()->getContents(), true)['response'][0] ?? null;

            if(!$item)
            {
                return null;
            }

            // 0502 = RUC encontrado; 0500 = RUC no existe
            if(($item['cod_respuesta'] ?? '') != '0502')
            {
                return false;
            }

            return [
                'ruc' => $ruc.'-'.self::calcularDv($ruc),
                'persona' => trim($item['razon_social'] ?? ''),
                'estado' => $item['estado'] ?? '',
                'esFacturador' => (bool) ($item['factura_electronica'] ?? false),
            ];
        }
        catch(\Throwable $e)
        {
            Log::error('futura100: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Dígito verificador de un RUC según el algoritmo módulo 11 de la SET (base 11).
     */
    public static function calcularDv($numero)
    {
        $peso = 2;
        $suma = 0;

        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            if ($peso > 11) {
                $peso = 2;
            }
            $suma += intval($numero[$i]) * $peso;
            $peso++;
        }

        $resto = $suma % 11;

        return $resto > 1 ? 11 - $resto : 0;
    }
}
