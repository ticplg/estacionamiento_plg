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
        Schema::create('ruc_activos', function (Blueprint $table) {
            $table->id();
            $table->string('ruc')->index();
            $table->string('codigo')->index();
            $table->string('nombre');
            $table->unsignedInteger('tipo');
            $table->string('identificador')->nullable();
            $table->string('estado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ruc_activos');
    }
};
