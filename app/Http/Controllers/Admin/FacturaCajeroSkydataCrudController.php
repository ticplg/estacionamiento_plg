<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\FacturaCajeroSkydataRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class FacturaCajeroSkydataCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class FacturaCajeroSkydataCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    //use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
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
        CRUD::setModel(\App\Models\FacturaCajeroSkydata::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/factura-cajero-skydata');
        CRUD::setEntityNameStrings('factura Cajero skydata', 'factura Cajero Skydata');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    
    protected function setupListOperation()
    {
        $this->crud->enableDetailsRow();
        $this->crud->setDetailsRowView('vendor.backpack.crud.details_row.detalles_skydata');
        CRUD::setOperationSetting('lineButtonsAsDropdown', true);

        CRUD::addButtonFromModelFunction('line', 'descargar_pdf', 'descargar_pdf', 'end');

        CRUD::column('numero_factura')->type('text');
        CRUD::column('ruc_receptor')->type('text');
        CRUD::column('nombre_receptor')->type('text');
        CRUD::column('total')->type('number');
        CRUD::column('tipo_pago')->type('text');
        CRUD::column('fecha_emision')->type('date');

        // Filtros
        $this->crud->addFilter([
            'type' => 'select2',
            'name' => 'tipo_pago',
            'label' => 'Tipo de Pago'
        ],
        function() {
            return \App\Models\FacturaCajeroSkydata::distinct()->pluck('tipo_pago', 'tipo_pago')->filter()->toArray();
        },
        function($value) {
            $this->crud->addClause('where', 'tipo_pago', $value);
        });

        $this->crud->addFilter([
            'type'  => 'date_range',
            'name'  => 'fecha_emision',
            'label' => 'Fecha Emisión'
        ],
        false,
        function ($value) {
            $dates = json_decode($value);
            if ($dates->from && $dates->to) {
                $this->crud->addClause('whereBetween', 'fecha_emision', [$dates->from, $dates->to]);
            }
        });

        CRUD::filter('estado_factura')
        ->type('dropdown')
        ->label('Estado')
        ->values([
            'Aprobado' => 'Aprobado',
            'Pendiente' => 'Pendiente',
            'Rechazado' => 'Rechazado',
        ])
        ->whenActive(function ($value) {
            CRUD::addClause('where', 'estado_factura', $value);
        });
    }


    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(FacturaCajeroSkydataRequest::class);
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
