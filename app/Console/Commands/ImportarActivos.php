<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RucActivo;
use Illuminate\Support\Facades\File;

class ImportarActivos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importar:activos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importar activos desde archivos .txt ubicados en public/ruc';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directory = public_path('ruc'); // Ruta de los archivos .txt

        // Verificar si la carpeta existe
        if (!File::exists($directory)) {
            $this->error("El directorio 'public/ruc' no existe.");
            return Command::FAILURE;
        }

        // Obtener todos los archivos .txt en el directorio
        $files = File::files($directory);

        if (empty($files)) {
            $this->info("No se encontraron archivos .txt en el directorio.");
            return Command::SUCCESS;
        }

        $this->info("Procesando archivos...");

        foreach ($files as $file) {
            if ($file->getExtension() === 'txt') {
                $this->info("Procesando archivo: {$file->getFilename()}");

                // Leer las líneas del archivo
                $lines = file($file->getPathname());

                foreach ($lines as $line) {
                    $data = explode('|', trim($line));
                    //$this->info(count($data));
                    if (count($data) === 6) {
                        if($data[4] == 'ACTIVO')
                        {
                            RucActivo::create([
                                'ruc' => $data[0].'-'.$data[2],
                                'codigo' => $data[0],
                                'nombre' => $data[1],
                                'tipo' => $data[2],
                                'identificador' => $data[3] ?: null,
                                'estado' => $data[4],
                            ]);
                        }
                    } else {
                        $this->warn("Línea con formato incorrecto en archivo {$file->getFilename()}: {$line}");
                    }
                }
            }
        }

        $this->info("Archivos procesados exitosamente.");
        return Command::SUCCESS;
    }
}
