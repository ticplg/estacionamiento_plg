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
        Schema::create('historial_marcacions', function (Blueprint $table) {
            $table->id();
            $table->string('user_app_id');
            $table->string('user_app_correo');
            $table->string('user_app_nombre');
            $table->uuid('id_ubicacion');
            $table->dateTime('fecha_registro');
            $table->dateTime('fecha_vence');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_marcacions');
    }
};
