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
            $table->bigInteger('transaccion_tarjeta_id')->default(0);
            $table->bigInteger('transaccion_qr_id')->default(0);
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
