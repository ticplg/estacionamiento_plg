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
        Schema::create('registro_estacionamientos', function (Blueprint $table) {
            $table->id();
            $table->string('identificador');
            $table->bigInteger('user_app_id');
            $table->string('user_first_name');
            $table->string('user_last_name');
            $table->date('fecha_lectura');
            $table->time('hora_lectua');
            $table->double('price', 20);
            $table->string('parking_duration');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_estacionamientos');
    }
};
