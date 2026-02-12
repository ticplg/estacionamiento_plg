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
        Schema::table('historial_facturas', function (Blueprint $table) {
            $table->string('punto_venta')->default('APP');
            $table->boolean('correo_enviado')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historial_facturas', function (Blueprint $table) {
            //
        });
    }
};
