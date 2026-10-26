<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\UsuarioLocatarioRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UsuarioLocatarioCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UsuarioLocatarioCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\UsuarioLocatario::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/usuario-locatario');
        CRUD::setEntityNameStrings('usuario locatario', 'usuarios locatarios');
    }

    protected function setupListOperation()
    {
        CRUD::column('usuario')->type('text');
        CRUD::column('local')->type('text');
        CRUD::column('codigo_descuento')->type('text')->label('Código descuento');
        CRUD::column('estado')->type('select_from_array')->options($this->estados());
        CRUD::column('created_at')->type('datetime')->label('Creado');

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
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(UsuarioLocatarioRequest::class);

        CRUD::field('usuario')->type('email')->label('Usuario (email)');
        CRUD::field('password')->type('password')->label('Contraseña')
            ->hint($this->crud->getCurrentOperation() == 'update' ? 'Dejar vacío para mantener la actual.' : '');
        CRUD::field('local')->type('text');
        CRUD::field('codigo_descuento')->type('text')->label('Código descuento')
            ->hint('Código de validación de Skidata: APT.VAL.1901198.{código}');
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
