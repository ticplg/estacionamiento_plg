<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RegistroEstacionamientoPagoRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RegistroEstacionamientoPagoCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RegistroEstacionamientoPagoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    //use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    //use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    //use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\RegistroEstacionamientoPago::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/registro-estacionamiento-pago');
        CRUD::setEntityNameStrings('registro estacionamiento pago', 'registro estacionamiento pagos');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addColumn([
            'label' => "Identificador",
            'name'  => "identificador",
        ]);

        $this->crud->addColumn([
            'label' => "Usuario APP",
            'name'  => "usuario_app",
        ]);
        
        $this->crud->addColumn([
            'label' => "Fecha Lectura",
            'name'  => "fecha_lectura",
            'type'  => "date",
        ]);

        $this->crud->addColumn([
            'label' => "Tiempo",
            'name'  => "parking_duration",
        ]);

        $this->crud->addColumn([
            'label' => "Precio",
            'name'  => "price",
            'type'  => "number",
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(RegistroEstacionamientoPagoRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
