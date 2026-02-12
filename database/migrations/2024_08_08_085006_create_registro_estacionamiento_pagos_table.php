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
        Schema::create('registro_estacionamiento_pagos', function (Blueprint $table) {
            $table->id();
            $table->string('identificador');
            $table->double('price', 20, 2);
            $table->bigInteger('user_id');
            $table->string('user_name');
            $table->string('user_lastname');
            $table->date('fecha_pago');
            $table->time('hora_pago');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_estacionamiento_pagos');
    }
};
