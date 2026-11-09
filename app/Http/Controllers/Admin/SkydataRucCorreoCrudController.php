<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\SkydataRucCorreoRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * ABM de RUC y correo electrónico para las facturas de los cajeros Skydata.
 *
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SkydataRucCorreoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\SkydataRucCorreo::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/skydata-ruc-correo');
        CRUD::setEntityNameStrings('RUC y correo Skydata', 'RUC y correos Skydata');
    }

    protected function setupListOperation()
    {
        CRUD::column('ruc')->type('text')->label('RUC');
        CRUD::column('razon_social')->type('text')->label('Razón social');
        CRUD::column('email')->type('email')->label('Correo electrónico');
        CRUD::column('estado')->type('select_from_array')->options($this->estados());
        CRUD::column('updated_at')->type('datetime')->label('Actualizado');

        CRUD::filter('estado')
        ->type('dropdown')
        ->label('Estado')
        ->values($this->estados())
        ->whenActive(function ($value) {
            CRUD::addClause('where', 'estado', $value);
        });
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::column('created_at')->type('datetime')->label('Creado');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(SkydataRucCorreoRequest::class);

        CRUD::field('ruc')->type('text')->label('RUC')->hint('Con guion y dígito verificador, ej. 80012345-6');
        CRUD::field('razon_social')->type('text')->label('Razón social');
        CRUD::field('email')->type('email')->label('Correo electrónico');
        CRUD::field('estado')->type('select_from_array')->options($this->estados())->default('A')->allows_null(false);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    private function estados()
    {
        return ['A' => 'Activo', 'I' => 'Inactivo'];
    }
}
