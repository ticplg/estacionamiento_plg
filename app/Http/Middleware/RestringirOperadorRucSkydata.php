<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Los usuarios con el rol ROL solo pueden usar el ABM de RUC y correo de Skydata
 * (y su propia cuenta). Cualquier otra pantalla del admin los redirige ahí.
 */
class RestringirOperadorRucSkydata
{
    const ROL = 'Operador RUC Skydata';

    public function handle($request, Closure $next)
    {
        $user = backpack_user();

        if (! $user || ! $user->hasRole(self::ROL)) {
            return $next($request);
        }

        $prefijo = trim(config('backpack.base.route_prefix', 'admin'), '/');

        $permitidas = [
            $prefijo.'/skydata-ruc-correo',
            $prefijo.'/skydata-ruc-correo/*',
            $prefijo.'/logout',
            $prefijo.'/edit-account-info',
            $prefijo.'/change-password',
        ];

        if ($request->is(...$permitidas)) {
            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 403);
        }

        return redirect(backpack_url('skydata-ruc-correo'));
    }
}
