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
        Schema::create('historial_facturas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->double('monto_factura', 20, 2);
            $table->date('fecha_factura');
            $table->string('documento');
            $table->string('razon_social');
            $table->string('authorization_number');
            $table->string('ticket_number');
            $table->string('identificador');
            $table->longText('path_factura')->nullable();
            $table->string('path_xml')->nullable();
            $table->integer('status_mega_print')->nullable();
            $table->string("mega_print_id_operacion")->nullable();
            $table->dateTime("mega_print_fec_proc")->nullable();
            $table->string("mega_print_dig_val")->nullable();
            $table->string("mega_print_est_res")->nullable();
            $table->string("mega_print_aut")->nullable();
            $table->string("timbrado")->nullable();
            $table->date("fecha_inicio_timbrado")->nullable();
            $table->string("numero_factura")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_facturas');
    }
};
