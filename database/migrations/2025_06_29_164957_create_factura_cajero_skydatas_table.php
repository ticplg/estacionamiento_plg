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
        Schema::create('factura_cajero_skydatas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_emision')->nullable();
            $table->string('ruc_emisor')->nullable();
            $table->string('nombre_emisor')->nullable();
            $table->string('ruc_receptor')->nullable();
            $table->string('nombre_receptor')->nullable();
            $table->string('total')->nullable();
            $table->string('iva_base')->nullable();
            $table->string('iva_liquidado')->nullable();
            $table->string('timbrado')->nullable();
            $table->string('numero_factura')->nullable();
            $table->string('cdc')->nullable();
            $table->longText('json_factura')->nullable();
            $table->longText('json_pago')->nullable();
            $table->longText('respuesta_servicio_factura')->nullable();
            $table->longText('respuesta_servicio_pago')->nullable();
            $table->string('tipo_pago')->nullable();
            $table->string('numero_boleta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factura_cajero_skydatas');
    }
};
