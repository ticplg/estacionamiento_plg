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
        Schema::create('descuento_proveedors', function (Blueprint $table) {
            $table->id();
            $table->string('identificador');
            $table->string('nombre');
            $table->string('chapa');
            $table->string('evento');
            $table->datetime('fecha_hora_validacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descuento_proveedors');
    }
};
