<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ConsultaRucService
{
    /**
     * Consulta un RUC (sin DV) en api.consulta-ruc.com.py.
     * Devuelve ['ruc', 'persona', 'estado', 'esFacturador'] o null si no existe o falla el servicio.
     */
    public function consultar($ruc)
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

            if($res->getStatusCode() != 200)
            {
                return null;
            }

            return json_decode($res->getBody()->getContents(), true)['data'][0] ?? null;
        }
        catch(\Throwable $e)
        {
            Log::error('consulta-ruc: '.$e->getMessage());
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
