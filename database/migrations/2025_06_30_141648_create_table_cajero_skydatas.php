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
        Schema::create('cajero_skydatas', function (Blueprint $table) {
            $table->id();
            $table->string('punto_expedicion');
            $table->string('nombre_cajero');
            $table->timestamps();
        });

        Schema::table('factura_cajero_skydatas', function (Blueprint $table) {
            $table->bigInteger('cajero_id')->nullable();
            $table->string('estado_factura')->default('Pendiente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cajero_skydatas', function (Blueprint $table) {
            //
        });
    }
};
