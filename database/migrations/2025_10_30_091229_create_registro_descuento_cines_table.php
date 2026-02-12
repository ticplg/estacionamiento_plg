<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registro_descuento_cines', function (Blueprint $table) {
            $table->id();
            $table->string('identificador');
            $table->datetime('fecha_registro');
            $table->datetime('fecha_exoneracion')->nullable();
            $table->longText('respuesta_servicio')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_descuento_cines');
    }
};
