<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('hook_alias'); // Ejemplo: "SRPGY38076"
            $table->string('status')->nullable(); // Ejemplo: "failed"
            $table->string('response_code')->nullable(); // Ejemplo: "12"
            $table->string('response_description')->nullable(); // Ejemplo: "PLAN CUOTAS NO SOPORTADO POR EL COMERCIO(2118672505)"
            $table->integer('amount')->nullable(); // Ejemplo: 1500
            $table->string('currency')->nullable(); // Ejemplo: "GS"
            $table->integer('installment_number')->nullable(); // Ejemplo: 1
            $table->string('description')->nullable(); // Ejemplo: "Pago Estacionamiento"
            $table->bigInteger('ticket_number')->nullable(); // Ejemplo: 2118672505
            $table->string('authorization_code')->nullable(); // Puede ser nulo
            $table->string('commerce_name')->nullable(); // Ejemplo: "BLUE TOWER VENTURES PY S.A."
            $table->string('branch_name')->nullable(); // Ejemplo: "PLG-ONLINE"
            $table->timestamp('created_at')->useCurrent(); // Fecha de creación
            $table->string('bin')->nullable(); // Ejemplo: "0"
            $table->string('merchant_code')->nullable(); // Ejemplo: "0"
            $table->string('name')->nullable(); // Ejemplo: "test1"
            $table->string('lastname')->nullable(); // Ejemplo: "bancard"
            $table->string('card_last_numbers')->nullable(); // Ejemplo: "0031"
            $table->string('account_type')->nullable(); // Ejemplo: "TC"
            $table->string('qr_url')->nullable(); // Ejemplo: "TC"
            $table->index('hook_alias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
