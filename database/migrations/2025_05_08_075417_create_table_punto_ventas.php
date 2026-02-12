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
        Schema::create('punto_ventas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_punto_venta');
            $table->string('codigo_establecimiento');
            $table->string('codigo_sucursal');
            $table->string('numero_inicial_factura');
            $table->string('numero_siguiente');
            $table->boolean('estado')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('punto_ventas');
    }
};
