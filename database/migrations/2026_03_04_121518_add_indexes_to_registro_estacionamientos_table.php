<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToRegistroEstacionamientosTable extends Migration
{
    public function up()
    {
        Schema::table('registro_estacionamientos', function (Blueprint $table) {

            // índice compuesto optimizado para tus consultas
            $table->index(
                ['fecha_lectura', 'user_app_id', 'price', 'identificador'],
                'registro_busqueda_idx'
            );

            // índice adicional útil si se busca por user
            $table->index('user_app_id', 'registro_es_user_idx');

        });
    }

    public function down()
    {
        Schema::table('registro_estacionamientos', function (Blueprint $table) {

            $table->dropIndex('registro_busqueda_idx');
            $table->dropIndex('registro_es_user_idx');

        });
    }
}