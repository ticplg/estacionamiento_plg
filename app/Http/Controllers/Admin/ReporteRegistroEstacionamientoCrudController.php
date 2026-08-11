<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ReporteRegistroEstacionamientoRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ReporteRegistroEstacionamientoCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ReporteRegistroEstacionamientoCrudController extends CrudController
{
    //use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {store as traitStore; }
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
        CRUD::setModel(\App\Models\ReporteRegistroEstacionamiento::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/reporte-registro-estacionamiento');
        CRUD::setEntityNameStrings('reporte estacionamiento', 'reporte estacionamiento');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // set columns from db columns.

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
        CRUD::setValidation(ReporteRegistroEstacionamientoRequest::class);
        //CRUD::setFromDb(); // set fields from db columns.
        CRUD::addField([
            'type'      => 'select2_from_array',
            'label'     => 'Formato Reporte',
            'name'      => 'tipo_reporte',
            'options'   => 
            [
                'reporte_lectura_pagos' => 'Tickets Facturados', 
                'reporte_cajero_skydata' => 'Tickets Facturados Skydata', 
                'reporte_pagos_pendiente_factura' => 'Pagos sin Factura Asociada','facturas_rechazadas_sifen' =>'Facturas Rechazadas SIFEN',
                'reporte_validados_zf' => 'Tickets Validados Zona Fit', 
                'reporte_salon_eventos' => 'Tickets Salon de Eventos', 
                'reporte_descuento_proveedores' => 'Descuentos Proveedores',
                'reporte_lectura_tickets' => 'Tickets Leidos via APP', 
                'reporte_ticket_hotel' => 'Tickets Hotel', 
                'reporte_descuento_cines' => 'Descuentos Cines',
            ],
            'default'   => 'reporte_lectura_pagos',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        
        CRUD::addField([
            'type'      => 'date',
            'name'      => 'fecha_desde',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],

        ]);

        CRUD::addField([
            'type'      => 'date',
            'name'      => 'fecha_hasta',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

    }

    public function store()
    {
        $response = $this->traitStore();
        $reporte_id = $this->crud->entry->id;
        return redirect('/procesar_reporte/?id=' . $reporte_id .'&redirect=/admin/reporte-registro-estacionamiento/create&url_reporte=/reporte_estacionamiento/'.$this->crud->entry->id);

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
