<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Ejecuta `app:generar-factura` sin solaparse con ejecuciones anteriores
        $schedule->command('app:generar-factura')
            ->everyTenMinutes()->withoutOverlapping();

        $schedule->command('app:cruce-venta-pagos')
            ->everyTenMinutes()->withoutOverlapping();

	    $schedule->command('app:ejecutar-validacion-salida')
            ->everyMinute()->withoutOverlapping();

	    $schedule->command('app:descuento-cine')
            ->everyMinute()->withoutOverlapping();

	    $schedule->command('app:tarifa-unica')
            ->everyMinute()->withoutOverlapping();

        $schedule->command('app:aplicar-exoneracion-ticket')
            ->everyFiveMinutes()
            ->withoutOverlapping();

        // Ejecuta `app:sincronizar-dinamic` solo después de `app:generar-factura`
        /*$schedule->command('app:sincronizar-dinamic')
            ->everyMinute()
            ->withoutOverlapping()
            ->after(function () {
                $this->call('app:generar-factura');
            });*/
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
