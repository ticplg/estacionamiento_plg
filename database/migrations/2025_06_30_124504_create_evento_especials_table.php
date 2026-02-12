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
        Schema::create('evento_especials', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_evento');
            $table->datetime('fecha_hora_inicio');
            $table->datetime('fecha_hora_fin');
            $table->string('locatario');
            $table->string('moneda_cierre');
            $table->string('nombre_exonerador');
            $table->double('monto_por_ticket', 20, 2);
            $table->bigInteger('cantidad_tickets');
            $table->datetime('fecha_hora_fin_exoneracion')->nullable();
            $table->bigInteger('cantidad_validaciones_hechas')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_especials');
    }
};
