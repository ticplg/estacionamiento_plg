<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\EventoDescuentoRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class EventoDescuentoCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EventoDescuentoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    //use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    //use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    //use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\EventoDescuento::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/evento-descuento');
        CRUD::setEntityNameStrings('evento', 'eventos');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
       // CRUD::setFromDb(); // set columns from db columns.
        CRUD::column([
            'type'      => 'text',
            'label'     => 'Nombre Evento',
            'name'      => 'nombre_evento',
        ]);

        CRUD::column([
            'type'      => 'number',
            'label'     => 'Disponibles',
            'name'      => 'cantidad_validaciones_disponibles',
        ]);

        CRUD::column([
            'type'      => 'number',
            'label'     => 'Utilizados',
            'name'      => 'cantidad_validaciones_hechas',
        ]);

        CRUD::column([
            'type'      => 'datetime',
            'label'     => 'Desde',
            'name'      => 'fecha_hora_inicio',
        ]);

        CRUD::column([
            'type'      => 'datetime',
            'label'     => 'Hasta',
            'name'      => 'fecha_hora_fin',
        ]);
        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(EventoDescuentoRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.

        CRUD::addField([
            'type'      => 'text',
            'label'     => 'Nombre Evento',
            'name'      => 'nombre_evento',
            'wrapper' => [
                'class' => 'form-group col-md-8',
            ],
        ]);

        CRUD::addField([
            'type'      => 'select2_from_array',
            'label'     => 'Tipo Evento',
            'name'      => 'tipo_evento',
            'options'   => ['jornada_completa' => 'Jornada Completa', 'media_jornada' => 'Media Jornada'],
            'default'   => 'jornada_completa',
            'wrapper' => [
                'class' => 'form-group col-md-4',
            ],
        ]);

        
        CRUD::addField([
            'type'      => 'datetime',
            'name'      => 'fecha_hora_inicio',
            'wrapper' => [
                'class' => 'form-group col-md-5',
            ],

        ]);

        CRUD::addField([
            'type'      => 'datetime',
            'name'      => 'fecha_hora_fin',
            'wrapper' => [
                'class' => 'form-group col-md-5',
            ],
        ]);

        CRUD::addField([
            'type'      => 'number',
            'name'      => 'cantidad_validaciones_disponibles',
            'label'      => 'Cantidad Validaciones',
            'wrapper' => [
                'class' => 'form-group col-md-2',
            ],
        ]);

        CRUD::addField([
            'type'      => 'boolean',
            'name'      => 'tarifado',
            'default'   => 1,
            'label'      => 'Tarifado (En caso de que el ticket tenga un costo según el tipo de evento)',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        
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
