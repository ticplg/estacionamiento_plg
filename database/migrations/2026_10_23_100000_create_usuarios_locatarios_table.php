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
        Schema::create('usuarios_locatarios', function (Blueprint $table) {
            $table->id();
            $table->string('usuario')->unique();
            $table->string('password');
            $table->string('local');
            // Código de validación de Skidata: APT.VAL.1901198.{codigo_descuento}
            $table->string('codigo_descuento', 20);
            $table->char('estado', 1)->default('A');
            // Hash sha256 del token vigente; se regenera en cada login
            $table->string('api_token', 64)->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios_locatarios');
    }
};
