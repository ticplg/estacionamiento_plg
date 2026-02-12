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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('forma_pago')->nullable();
            $table->string('monto')->nullable();
            $table->string('numero_ticket')->nullable();
            $table->string('codigo_autorizacion')->nullable();
            $table->bigInteger('user_app_id')->nullable();
            $table->string('nombre')->nullable();
            $table->string('estado')->nullable();
            $table->string('mensaje')->nullable();
            $table->string('documento')->nullable();
            $table->bigInteger('referencia_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
