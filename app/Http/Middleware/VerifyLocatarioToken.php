<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\UsuarioLocatario;
use Illuminate\Http\Request;

// Auth propia de la app de locatarios: no usa Sanctum para que un token de
// locatario no sirva en las rutas auth:sanctum de la app general.
class VerifyLocatarioToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken() ?: $request->header('token');

        if (!$token) {
            return response()->json([
                'status' => 403,
                'message' => 'Token de usuario no encontrado'
            ], 403);
        }

        $usuario = UsuarioLocatario::where('api_token', hash('sha256', $token))->first();

        if (!$usuario) {
            return response()->json([
                'status' => 403,
                'message' => 'Token no válido'
            ], 403);
        }

        if (!$usuario->estaActivo()) {
            return response()->json([
                'status' => 403,
                'message' => 'Usuario inactivo'
            ], 403);
        }

        $request->attributes->set('usuario_locatario', $usuario);

        return $next($request);
    }
}
