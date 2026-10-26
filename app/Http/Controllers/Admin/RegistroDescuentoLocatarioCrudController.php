<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class RegistroDescuentoLocatarioCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class RegistroDescuentoLocatarioCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\RegistroDescuentoLocatario::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/registro-descuento-locatario');
        CRUD::setEntityNameStrings('descuento locatario', 'descuentos locatarios');
    }

    protected function setupListOperation()
    {
        CRUD::orderBy('fecha_registro', 'desc');

        CRUD::column('fecha_registro')->type('datetime')->label('Fecha');
        CRUD::column('usuario')->type('relationship')->attribute('usuario')->label('Usuario');
        CRUD::column('local')->type('closure')->label('Local')->function(function ($entry) {
            return optional($entry->usuario)->local;
        });
        CRUD::column('identificador')->type('text')->label('Ticket');
        CRUD::column('codigo_descuento')->type('text')->label('Código');
        CRUD::column('nombre')->type('text');
        CRUD::column('apellido')->type('text');
        CRUD::column('nro_factura')->type('text')->label('Nro. factura');
        CRUD::column('monto_ticket')->type('number')->label('Monto ticket')->thousands_sep('.');
        CRUD::column('monto_descuento')->type('number')->label('Descuento')->thousands_sep('.');
        CRUD::column('monto_final')->type('number')->label('Monto final')->thousands_sep('.');
        CRUD::column('response_status')->type('text')->label('Estado Skidata')
            ->wrapper([
                'element' => 'span',
                'class' => function ($crud, $column, $entry) {
                    return $entry->response_status == 200 ? 'badge bg-success' : 'badge bg-danger';
                },
            ]);

        $this->crud->addFilter([
            'type' => 'select2',
            'name' => 'usuario_id',
            'label' => 'Usuario'
        ],
        function() {
            return \App\Models\UsuarioLocatario::orderBy('usuario')->pluck('usuario', 'id')->toArray();
        },
        function($value) {
            $this->crud->addClause('where', 'usuario_id', $value);
        });

        $this->crud->addFilter([
            'type'  => 'date_range',
            'name'  => 'fecha_registro',
            'label' => 'Fecha'
        ],
        false,
        function ($value) {
            $dates = json_decode($value);
            if ($dates->from && $dates->to) {
                $this->crud->addClause('whereBetween', 'fecha_registro', [$dates->from . ' 00:00:00', $dates->to . ' 23:59:59']);
            }
        });

        CRUD::filter('response_status')
        ->type('dropdown')
        ->label('Estado Skidata')
        ->values([
            'aplicado' => 'Aplicado',
            'fallido' => 'Fallido',
        ])
        ->whenActive(function ($value) {
            if ($value == 'aplicado') {
                CRUD::addClause('where', 'response_status', 200);
            } else {
                CRUD::addClause('where', function ($query) {
                    $query->where('response_status', '!=', 200)->orWhereNull('response_status');
                });
            }
        });

        CRUD::filter('identificador')
        ->type('text')
        ->label('Ticket')
        ->whenActive(function ($value) {
            CRUD::addClause('where', 'identificador', 'LIKE', '%' . $value . '%');
        });
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();

        CRUD::column('response_body')->type('textarea')->label('Respuesta Skidata')->escaped(true);
    }
}
