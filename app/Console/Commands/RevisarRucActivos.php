<?php

namespace App\Console\Commands;

use App\Models\RucActivo;
use App\Models\HistorialFactura;
use App\Services\ConsultaRucService;
use Illuminate\Console\Command;

class RevisarRucActivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:revisar-ruc-activos
                            {--aplicar : Guarda las correcciones; sin esta opción solo informa}
                            {--sin-api : Corrige con el DV calculado sin confirmar en api.consulta-ruc.com.py}
                            {--ruc= : Revisar solo este RUC}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa el dígito verificador de ruc_activos y corrige los incorrectos (y las facturas pendientes que los usan)';

    public function handle()
    {
        $aplicar = $this->option('aplicar');
        $sinApi = $this->option('sin-api');
        $servicio = new ConsultaRucService;

        $this->info('==== Inicio: app:revisar-ruc-activos ('.($aplicar ? 'APLICANDO cambios' : 'solo informe, usar --aplicar para guardar').') ====');

        $revisados = 0;
        $sinGuion = 0;
        $noNumericos = 0;
        $noEncontrados = 0;
        $corregidos = [];

        RucActivo::query()
            ->when($this->option('ruc'), function ($query, $ruc) {
                $query->where('ruc', $ruc);
            })
            ->chunkById(1000, function ($rucs) use ($aplicar, $sinApi, $servicio, &$revisados, &$sinGuion, &$noNumericos, &$noEncontrados, &$corregidos) {
                foreach ($rucs as $rucActivo) {
                    $revisados++;
                    $partes = explode('-', trim($rucActivo->ruc));

                    // Sin DV (ej. cédulas): se dejan como están, el XML los trata como cédula
                    if (count($partes) != 2) {
                        $sinGuion++;
                        continue;
                    }

                    [$numero, $dv] = $partes;

                    if (!ctype_digit($numero)) {
                        $noNumericos++;
                        $this->warn(" ? id={$rucActivo->id} ruc={$rucActivo->ruc}: número no numérico, revisar manualmente");
                        continue;
                    }

                    $dvCalculado = ConsultaRucService::calcularDv($numero);
                    if ((string) $dv === (string) $dvCalculado) {
                        continue;
                    }

                    $rucCorrecto = $numero.'-'.$dvCalculado;

                    if (!$sinApi) {
                        $datos = $servicio->consultar($numero);
                        usleep(200000);

                        if (!$datos) {
                            $noEncontrados++;
                            $this->warn(" ? id={$rucActivo->id} ruc={$rucActivo->ruc}: DV incorrecto (calculado {$dvCalculado}) pero no encontrado en la API, no se modifica");
                            continue;
                        }

                        $rucCorrecto = $datos['ruc'];
                    }

                    $facturas = HistorialFactura::where('documento', $rucActivo->ruc)
                        ->whereNull('numero_factura')
                        ->count();

                    $corregidos[] = [$rucActivo->id, $rucActivo->ruc, $rucCorrecto, $rucActivo->nombre, $facturas];

                    if ($aplicar) {
                        HistorialFactura::where('documento', $rucActivo->ruc)
                            ->whereNull('numero_factura')
                            ->update(['documento' => $rucCorrecto]);

                        $rucActivo->ruc = $rucCorrecto;
                        $rucActivo->save();
                    }
                }
            });

        if ($corregidos) {
            $this->table(['id', 'RUC actual', 'RUC correcto', 'Nombre', 'Facturas pendientes'], $corregidos);
        }

        $this->info("Revisados: {$revisados}");
        $this->info('Con DV incorrecto: '.count($corregidos).($aplicar ? ' (corregidos)' : ' (sin cambios, usar --aplicar)'));
        $this->info("Sin DV (no se revisan): {$sinGuion}");
        $this->info("No numéricos (revisar manualmente): {$noNumericos}");
        if (!$sinApi) {
            $this->info("DV incorrecto pero no encontrados en la API: {$noEncontrados}");
        }

        $this->info('==== Fin: app:revisar-ruc-activos ====');
    }
}
