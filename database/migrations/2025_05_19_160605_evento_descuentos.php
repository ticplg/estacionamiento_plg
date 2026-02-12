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
        Schema::create('evento_descuentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_evento');
            $table->string('tipo_evento');
            $table->bigInteger('cantidad_validaciones_disponibles');
            $table->bigInteger('cantidad_validaciones_hechas')->default(0);
            $table->datetime('fecha_hora_inicio');
            $table->datetime('fecha_hora_fin');
            $table->datetime('fecha_hora_fin_exoneracion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_descuentos');
    }
};
