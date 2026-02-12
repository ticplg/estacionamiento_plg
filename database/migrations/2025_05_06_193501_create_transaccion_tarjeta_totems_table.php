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
        Schema::create('transaccion_tarjeta_totems', function (Blueprint $table) {
            $table->id();
            $table->string('numero_boleta');
            $table->string('codigo_autorizacion');
            $table->string('codigo_comercio');
            $table->double('monto', 20, 2);
            $table->timestamps();
        });

        Schema::table('historial_facturas', function (Blueprint $table) {
            $table->bigInteger('transaccion_tarjeta_totem_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaccion_tarjeta_totems');
    }
};
