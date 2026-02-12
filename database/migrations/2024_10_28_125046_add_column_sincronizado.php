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
            $table->boolean('factura_sincronizada')->default(0);
            $table->boolean('pago_sincronizado')->default(0);
            $table->string('mensaje_factura')->nullable();
            $table->string('mensaje_pago')->nullable();
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
