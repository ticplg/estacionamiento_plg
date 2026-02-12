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
        Schema::table('espacio_estacionamientos', function (Blueprint $table) {
            $table->string('qr_path')->change()->nullable();
            $table->string('imagen_referencial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('espacio_estacionamientos', function (Blueprint $table) {
            //
        });
    }
};
