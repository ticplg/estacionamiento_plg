<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('skydata_ruc_correos', function (Blueprint $table) {
            $table->id();
            // Mismo formato que factura_cajero_skydatas.ruc_receptor (ej. 80012345-6)
            $table->string('ruc', 20)->unique();
            $table->string('razon_social')->nullable();
            $table->string('email');
            $table->char('estado', 1)->default('A');
            $table->timestamps();
        });

        // Rol que solo puede entrar al ABM de RUC y correo de Skydata
        DB::table(config('permission.table_names.roles', 'roles'))->updateOrInsert(
            ['name' => \App\Http\Middleware\RestringirOperadorRucSkydata::ROL, 'guard_name' => 'web'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skydata_ruc_correos');

        DB::table(config('permission.table_names.roles', 'roles'))
            ->where('name', \App\Http\Middleware\RestringirOperadorRucSkydata::ROL)
            ->where('guard_name', 'web')
            ->delete();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
