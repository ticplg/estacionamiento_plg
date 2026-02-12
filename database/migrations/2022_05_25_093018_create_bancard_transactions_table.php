<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBancardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bancard_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('usuario_id')->unsigned()->index();
            $table->decimal('amount', 20, 2);
            $table->bigInteger('card_id');
            $table->string('currency', 10);
            $table->string('status');
            $table->string('subject'); //por el momento sólo está el caso de compra de vales
            $table->integer('subject_id')->unsigned()->index()->nullable(); //solo si el vale (u otra entidad) se crea efectivamente
            $table->text('request_data')->nullable();
            $table->text('response_data')->nullable();
            $table->string('conciliado')->nullable();
            $table->string('manual')->nullable();
            $table->string('ci')->nullable();
            $table->string('email')->nullable();
            $table->string('fullname')->nullable();
            $table->string('tipo_tarjeta')->nullable();
            $table->text('error')->nullable();
            $table->bigInteger('factura_id')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bancard_transactions');
    }
}
