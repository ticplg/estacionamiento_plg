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
            $table->bigInteger('factura_id')->default(0);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->bigInteger('factura_id')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
