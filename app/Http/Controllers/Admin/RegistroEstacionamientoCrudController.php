<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\RegistroEstacionamientoRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RegistroEstacionamientoCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RegistroEstacionamientoCrudController extends CrudController
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
        CRUD::setModel(\App\Models\RegistroEstacionamiento::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/registro-estacionamiento');
        CRUD::setEntityNameStrings('registro estacionamiento', 'registro estacionamientos');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        //CRUD::setFromDb(); // set columns from db columns.
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
            'name'  => "created_at",
            'type'  => "datetime",
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

        CRUD::filter('Fecha')
        ->type('date_range')
        ->date_range_options([
            'timePicker' => false // example: enable/disable time picker
        ])
        ->whenActive(function($value) {
            $dates = json_decode($value);
            CRUD::addClause('where', 'created_at', '>=', $dates->from);
            CRUD::addClause('where', 'created_at', '<=', $dates->to);
        });


    }

    protected function setupShowOperation()
    {
        $this->crud->addColumn([
            'label' => "Identificador",
            'name'  => "identificador",
        ]);
        
        $this->crud->addColumn([
            'label' => "Id Usuario App",
            'name'  => "user_app_id",
        ]);

        $this->crud->addColumn([
            'label' => "Nombre",
            'name'  => "user_first_name",
        ]);

        $this->crud->addColumn([
            'label' => "Apellido",
            'name'  => "user_last_name",
        ]);

        $this->crud->addColumn([
            'label' => "Fecha Lectura",
            'name'  => "created_at",
            'type' => "datetime"
        ]);

        $this->crud->addColumn([
            'label' => "Monto",
            'name'  => "price",
            'type' => "number"
        ]);

        $this->crud->addColumn([
            'label' => "Duracion",
            'name'  => "parking_duration",
            'type' => "text"
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
        CRUD::setValidation(RegistroEstacionamientoRequest::class);
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
