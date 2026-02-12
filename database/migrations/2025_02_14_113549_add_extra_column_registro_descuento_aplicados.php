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
        Schema::table('registro_descuento_aplicados', function (Blueprint $table) {
            $table->bigInteger('zf_cliente_id')->nullable();
            $table->string('zf_tipo_plan')->nullable();
            $table->string('zf_cliente_nombre')->nullable();
            $table->string('zf_cliente_apellido')->nullable();
            $table->string('zf_cliente_cedula')->nullable();
            $table->date('fecha')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_descuento_aplicados', function (Blueprint $table) {
            //
        });
    }
};
