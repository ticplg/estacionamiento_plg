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
        Schema::create('ticket_evento_descuentos', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("evento_id");
            $table->string("identificador");
            $table->datetime("fecha_hora_validacion");
            $table->datetime("fecha_hora_validacion_salida")->nullable();
            $table->string("decision");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_evento_descuentos');
    }
};
