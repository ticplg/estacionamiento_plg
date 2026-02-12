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
        Schema::create('ticket_hotels', function (Blueprint $table) {
            $table->id();
            $table->string('identificador');
            $table->string('nombre_huesped');
            $table->string('habitacion');
            $table->date('fecha_checkin');
            $table->time('hora_checkin');
            $table->date('fecha_checkout')->nullable();
            $table->time('hora_checkout')->nullable();
            $table->double('monto_en_checkout')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_hotels');
    }
};
