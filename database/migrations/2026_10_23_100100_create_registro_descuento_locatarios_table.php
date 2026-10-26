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
        Schema::create('registro_descuento_locatarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios_locatarios');
            $table->dateTime('fecha_registro');
            $table->string('identificador', 50)->index();
            $table->string('codigo_descuento', 20);
            $table->string('nombre')->nullable();
            $table->string('apellido')->nullable();
            $table->string('nro_factura')->nullable();
            $table->bigInteger('monto_ticket')->nullable();
            $table->bigInteger('monto_descuento')->nullable();
            $table->bigInteger('monto_final')->nullable();
            $table->smallInteger('response_status')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_descuento_locatarios');
    }
};
