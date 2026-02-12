<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EspacioEstacionamiento extends Model
{
    use CrudTrait;
    use HasFactory, HasUuids;

    protected $fillable = [
        'id',
        'piso',
        'sector',
        'referencias',
        'imagen_referencial',
        'qr_path', // Asegúrate de que la columna qr_path esté en tu tabla
    ];

    protected $appends = ['imagen_referencial_url'];

    protected $hidden = ['qr_path', 'created_at', 'updated_at', 'imagen_referencial'];

    protected static function boot()
    {
        parent::boot();

        // Evento para generar el QR después de crear el modelo
        static::created(function ($espacio) {
            $espacio->generateQr();
        });

        // Evento para generar el QR después de actualizar el modelo
        static::updated(function ($espacio) {
            $espacio->generateQr();
        });
    }

    /**
     * Genera y guarda el código QR en el storage y actualiza el modelo.
     */
    public function generateQr()
    {
        // Definir la información que deseas codificar en el QR, en este caso, el atributo `id`
        $data = $this->id;
    
        // Generar el QR con el tamaño especificado de 2000x2000 px y un borde
        $qrCode = QrCode::format('png')
            ->size(1800) // El QR code en sí será un poco más pequeño
            ->margin(1) // Margen que actuará como borde (ajusta este valor para aumentar o reducir el borde)
            ->generate($data);
    
        // Crear una imagen en blanco de 2000x2000 px para el borde
        $canvas = imagecreatetruecolor(2000, 2000);
        $white = imagecolorallocate($canvas, 255, 255, 255); // Color de fondo blanco
        imagefill($canvas, 0, 0, $white);
    
        // Convertir el código QR a una imagen
        $qrImage = imagecreatefromstring($qrCode);
    
        // Calcular las coordenadas para centrar el QR en la imagen de 2000x2000
        $x = (2000 - imagesx($qrImage)) / 2;
        $y = (2000 - imagesy($qrImage)) / 2;
    
        // Colocar el código QR en el centro de la imagen con borde
        imagecopy($canvas, $qrImage, $x, $y, 0, 0, imagesx($qrImage), imagesy($qrImage));
    
        // Guardar la imagen resultante en el storage
        ob_start();
        imagepng($canvas);
        $output = ob_get_clean();
    
        $fileName = 'qrcodes/' . $this->id . '.png';
        Storage::disk('public')->put($fileName, $output);
    
        // Liberar memoria
        imagedestroy($canvas);
        imagedestroy($qrImage);
    
        // Actualizar la columna qr_path
        $this->qr_path = $fileName;
        $this->saveQuietly(); // Utiliza saveQuietly para evitar un bucle infinito de eventos
    }

    public function qr_code($crud = false)
    {
        // Crear el enlace de descarga con los atributos adecuados
        $downloadLink = '<a download="' . $this->id . '" class="btn btn-sm btn-link" href="/storage/' . $this->qr_path . '" data-toggle="tooltip"><i class="las la-download"></i> Descargar QR Code</a>';
        return $downloadLink;
    }

    public function getImagenReferencialUrlAttribute()
    {
        return $this->imagen_referencial ? Storage::disk('public')->url($this->imagen_referencial) : null;
    }
    
}
