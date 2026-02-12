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
        Schema::table('transaccion_tarjeta_totems', function (Blueprint $table) {
            $table->string("pan")->nullable();
            $table->string("nombre_cliente")->nullable();
            $table->string("nombre_tarjeta")->nullable();
            $table->string("issuerId")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaccion_tarjeta_totems', function (Blueprint $table) {
            //
        });
    }
};
