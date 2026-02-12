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
        Schema::table('registro_descuento_aplicados', function (Blueprint $table) {
            $table->longText('body')->nullable();
            $table->longText('response')->nullable();
            $table->string('response_status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_descuento_aplicados', function (Blueprint $table) {
            //
        });
    }
};
