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
        Schema::create('ticket_evento_especial', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("evento_especial_id");
            $table->string("identificador");
            $table->datetime("fecha_hora_validacion");
            $table->datetime("fecha_hora_exoneracion")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_evento_especial');
    }
};
