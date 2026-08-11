<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\FacturaCajeroSkydataRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class FacturaCajeroSkydataCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    
    public function setup()
    {
        CRUD::setModel(\App\Models\FacturaCajeroSkydata::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/factura-cajero-skydata');
        CRUD::setEntityNameStrings('factura Cajero skydata', 'factura Cajero Skydata');
    }

    protected function setupListOperation()
    {
        $this->crud->enableDetailsRow();
        $this->crud->setDetailsRowView('vendor.backpack.crud.details_row.detalles_skydata');
        
        CRUD::setOperationSetting('lineButtonsAsDropdown', true);

        // REGISTRO DE AMBOS BOTONES
        CRUD::addButtonFromModelFunction('line', 'descargar_pdf', 'descargar_pdf', 'end');
        CRUD::addButtonFromModelFunction('line', 'descargar_xml', 'descargar_xml', 'end');

        CRUD::column('numero_factura')->type('text');
        CRUD::column('ruc_receptor')->type('text');
        CRUD::column('nombre_receptor')->type('text');
        CRUD::column('total')->type('number');
        CRUD::column('tipo_pago')->type('text');
        CRUD::column('fecha_emision')->type('date');

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

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(FacturaCajeroSkydataRequest::class);
        CRUD::setFromDb();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}