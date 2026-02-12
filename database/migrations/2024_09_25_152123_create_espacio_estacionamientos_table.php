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
        Schema::create('espacio_estacionamientos', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('piso');
            $table->string('sector')->nullable();
            $table->longText('referencias');
            $table->string('qr_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('espacio_estacionamientos');
    }
};
