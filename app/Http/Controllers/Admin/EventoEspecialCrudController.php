<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\EventoEspecialRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class EventoEspecialCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EventoEspecialCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
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
        CRUD::setModel(\App\Models\EventoEspecial::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/evento-especial');
        CRUD::setEntityNameStrings('evento especial', 'evento especials');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column([
            'type'      => 'text',
            'label'     => 'Nombre Evento',
            'name'      => 'nombre_evento',
        ]);

        CRUD::column([
            'type'      => 'number',
            'label'     => 'Disponibles',
            'name'      => 'cantidad_tickets',
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
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */

    protected function setupCreateOperation()
    {
        CRUD::setValidation(EventoEspecialRequest::class);

        CRUD::addField([
            'type'      => 'text',
            'label'     => 'Nombre del Evento',
            'name'      => 'nombre_evento',
            'wrapper'   => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'type'      => 'datetime',
            'label'     => 'Fecha Desde',
            'name'      => 'fecha_hora_inicio',
            'wrapper'   => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'type'      => 'datetime',
            'label'     => 'Fecha Hasta',
            'name'      => 'fecha_hora_fin',
            'wrapper'   => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'type'      => 'text',
            'label'     => 'Locatario',
            'name'      => 'locatario',
            'wrapper'   => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'type'      => 'select2_from_array',
            'label'     => 'Moneda de Cierre',
            'name'      => 'moneda_cierre',
            'options'   => [
                'dolares'   => 'Dólares',
                'guaranies' => 'Guaraníes',
            ],
            'allows_null' => false,
            'default'     => 'guaranies',
            'wrapper'     => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'type'      => 'text',
            'label'     => 'Nombre del Exonerador',
            'name'      => 'nombre_exonerador',
            'wrapper'   => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'type'      => 'number',
            'label'     => 'Monto por Ticket',
            'name'      => 'monto_por_ticket',
            //'attributes'=> ["step" => "0.01"],
            'wrapper'   => ['class' => 'form-group col-md-6'],
            'prefix'    => '$ / GS.'
        ]);

        CRUD::addField([
            'type'      => 'number',
            'label'     => 'Cantidad de Tickets',
            'name'      => 'cantidad_tickets',
            'wrapper'   => ['class' => 'form-group col-md-6'],
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
