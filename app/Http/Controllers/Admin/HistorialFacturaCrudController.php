<?php

namespace App\Http\Controllers\Admin;

use DB;
use Illuminate\Http\Request;
use App\Models\HistorialFactura;
use App\Models\QRTransaction;
use App\Models\BancardTransaction;
use App\Models\RegistroEstacionamientoPago;
use App\Http\Requests\HistorialFacturaRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class HistorialFacturaCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class HistorialFacturaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
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
        CRUD::setModel(\App\Models\HistorialFactura::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/historial-factura');
        CRUD::setEntityNameStrings('factura', 'facturas');
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
        $this->crud->setDetailsRowView('vendor.backpack.crud.details_row.detalles');

        CRUD::setOperationSetting('lineButtonsAsDropdown', true);

        CRUD::addButtonFromModelFunction('line', 'descargar_pdf', 'descargar_pdf', 'end');
        CRUD::addButtonFromModelFunction('line', 'descargar_xml', 'descargar_xml', 'end');
        CRUD::addButtonFromModelFunction('line', 'volver_enviar', 'volver_enviar', 'end');

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

        $this->crud->addColumn([
            'label' => "Número Factura",
            'name'  => "numero_factura",
        ]);

        $this->crud->addColumn([
            'label' => "Nombre Factura",
            'name'  => "razon_social_convert",
        ]);

        $this->crud->addColumn([
            'label' => "Documento",
            'name'  => "documento",
        ]);
        
        $this->crud->addColumn([
            'label' => "Fecha Factura",
            'name'  => "fecha_factura",
            'type'  => "date",
        ]);

        $this->crud->addColumn([
            'label' => "Monto Factura",
            'name'  => "monto_factura",
            'type'  => "number",
        ]);

        $this->crud->addColumn([
            'label' => "Estado",
            'name'  => "estado_factura",
            'type'  => "text",
        ]);
    }

    public function setupShowOperation()
    {
        
        CRUD::removeButton('delete');
        //CRUD::removeButton('show');
        CRUD::removeButton('update');

        $this->crud->addColumn([
            'label' => "Identificador",
            'name'  => "identificador",
        ]);

        $this->crud->addColumn([
            'label' => "Numero Factura",
            'name'  => "numero_factura",
        ]);

        $this->crud->addColumn([
            'label' => "Nombre Factura",
            'name'  => "razon_social_convert",
        ]);

        $this->crud->addColumn([
            'label' => "Documento",
            'name'  => "documento",
        ]);
        
        $this->crud->addColumn([
            'label' => "Fecha Factura",
            'name'  => "fecha_factura",
            'type'  => "date",
        ]);

        $this->crud->addColumn([
            'label' => "Monto Factura",
            'name'  => "monto_factura",
            'type'  => "number",
        ]);

        $this->crud->addColumn([
            'label' => "Código Autorización Bancard",
            'name'  => "authorization_number",
        ]);

        $this->crud->addColumn([
            'label' => "Número Ticket Bancard",
            'name'  => "ticket_number",
        ]);

        $this->crud->addColumn([
            'label' => "Asiento Factura",
            'name'  => "mensaje_factura",
        ]);

        $this->crud->addColumn([
            'label' => "Asiento Pago",
            'name'  => "mensaje_pago",
        ]);

        $this->crud->addColumn([
            'name' => 'link',
            'label' => 'Factura',
            'type' => 'custom_html',
            'value' => function ($entry) {
                return $entry->path_factura 
                    ? '<a href="https://parkingplg.paseolagaleria.com.py/' . $entry->path_factura . '" target="_blank" class="btn btn-sm btn-primary">Descargar Factura</a>'
                    : '<span class="text-muted">Sin enlace</span>';
            },
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
        CRUD::setValidation(HistorialFacturaRequest::class);

        CRUD::addField([
            'label'                 => "Usuario App (Email, Nombre Apellido, Nro. Documento)",
            'type'                  => "select2_from_ajax",
            'name'                  => 'usuario_id', // columna donde se guarda el dato
            'entity'                => false,     // sin relación
            'model'                 => "App\\Models\\Cliente", // Debes especificar el modelo manualmente
            'attribute'             => "name",
            'data_source'           => url("/admin/search/cliente"),
            'minimum_input_length'  => 0,
            'attributes'            => [
                'class' => 'form-control',
            ],
            'wrapper' => [
                'class' => 'form-group col-sm-6 mb-3'
            ],
        ]);

        CRUD::addField([
            'label'                 => "Transaccion",
            'type'                  => "select2_from_ajax",
            'name'                  => 'transaccion_id', // columna donde se guarda el dato
            'entity'                => false,     // sin relación
            'model'                 => "App\\Models\\BancardTransaction", // Debes especificar el modelo manualmente
            'attribute'             => "fecha_monto",
            'data_source'           => url("/admin/search/transacciones"),
            'minimum_input_length'  => 0,
            'include_all_form_fields' => true, 
            'attributes'            => [
                'class' => 'form-control',
            ],
            'wrapper' => [
                'class' => 'form-group col-sm-6 mb-3'
            ],
        ]);

        CRUD::addField([
            'label'                 => "Transaccion",
            'type'                  => "select2_from_ajax",
            'name'                  => 'transaccion_id', // columna donde se guarda el dato
            'entity'                => false,     // sin relación
            'model'                 => "App\\Models\\BancardTransaction", // Debes especificar el modelo manualmente
            'attribute'             => "fecha_monto",
            'data_source'           => url("/admin/search/transacciones"),
            'minimum_input_length'  => 0,
            'include_all_form_fields' => true, 
            'attributes'            => [
                'class' => 'form-control',
            ],
            'wrapper' => [
                'class' => 'form-group col-sm-6 mb-3'
            ],
        ]);

        CRUD::addField([
            'label'                 => "Nombre para la Factura",
            'type'                  => "text",
            'name'                  => 'nombre_factura', // columna donde se guarda el dato
            'attributes'            => [
                'class' => 'form-control',
            ],
            'wrapper' => [
                'class' => 'form-group col-sm-6 mb-3'
            ],
        ]);

        CRUD::addField([
            'label'                 => "RUC para la Factura",
            'type'                  => "text",
            'name'                  => 'ruc_factura', // columna donde se guarda el dato
            'attributes'            => [
                'class' => 'form-control',
            ],
            'wrapper' => [
                'class' => 'form-group col-sm-6 mb-3'
            ],
        ]);




    }

    public function store(Request $request)
    {
        $transaccion_id = explode('-',$request->transaccion_id)[0];
        $tipo = explode('-',$request->transaccion_id)[1];

        $factura = new HistorialFactura;
        
        if($tipo == 'QR')
        {
            $transaccion = QRTransaction::find($transaccion_id);
            $authorization_number = $transaccion->authorization_code;
            $ticket_number = $transaccion->ticket_number;
            
            $factura->transaccion_qr_id = $transaccion->ticket_number;
            $factura->tipo_tarjeta  = ($transaccion->account_type == 'TC') ? 'credit' : 'debit';

        }else
        {
            $transaccion = BancardTransaction::find($transaccion_id);
            $authorization_number = json_decode($transaccion->response_data)->confirmation->authorization_number;
            $ticket_number = json_decode($transaccion->response_data)->confirmation->ticket_number;

            $factura->transaccion_tarjeta_id =  $request->transaccion_id;
            $factura->tipo_tarjeta  = $transaccion->tipo_tarjeta;
        }


        
        $factura->fecha_factura = date('Y-m-d');
        $factura->documento = $request->ruc_factura;
        $factura->razon_social = $request->nombre_factura;
        $factura->identificador = '0';
        $factura->monto_factura = $transaccion->amount;
        $factura->user_id = $transaccion->usuario_id;
        $factura->authorization_number = $authorization_number;
        $factura->ticket_number  = $ticket_number;
        $factura->usuario_sistema_id  = backpack_user()->id;
        $factura->save();
   
        $registro = new RegistroEstacionamientoPago;
        $registro->identificador = $factura->id;
        $registro->price = $factura->monto_factura;
        $registro->user_id = $factura->user_id;
        $registro->user_name = 'SIN DATOS';
        $registro->user_lastname = 'SIN DATOS';
        $registro->fecha_pago = $factura->fecha_factura;
        $registro->hora_pago = date('H:i:s', strtotime($factura->created_at));
        $registro->save();
        
        $factura->identificador = $factura->id;
        $factura->save();

        $transaccion->factura_id = $factura->id;
        $transaccion->save();
        \Alert::success('Factura creada exitosamente.')->flash();
        return redirect(backpack_url('historial-factura'));
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

    public function search_cliente(Request $request)
    {
        $q = $request->q;

        $subquery1 = DB::table('bancard_transactions as a')
            ->select(
                'a.ci as id',
                DB::raw("UPPER(CONCAT(a.fullname, ' - ', a.ci)) as name")
            )
            ->where('a.status', 'Success')
            ->where('a.factura_id', 0)
            ->where(function ($query) use ($q) {
                $query->where('a.fullname', 'LIKE', "%{$q}%")
                    ->orWhere('a.email', 'LIKE', "%{$q}%")
                    ->orWhere('a.ci', 'LIKE', "%{$q}%");
            });

        $subquery2 = DB::table('transactions as a')
            ->select(
                'a.ci as id',
                DB::raw("UPPER(CONCAT(a.fullname, ' - ', a.ci)) as name")
            )
            ->where('a.status', 'confirmed')
            ->where('a.factura_id', 0)
            ->where(function ($query) use ($q) {
                $query->where('a.fullname', 'LIKE', "%{$q}%")
                    ->orWhere('a.email', 'LIKE', "%{$q}%")
                    ->orWhere('a.ci', 'LIKE', "%{$q}%");
            });

        $results = $subquery1
            ->union($subquery2)
            ->get();

        return $results;
    }

    public function transacciones(Request $request)
    {
        $form = backpack_form_input();
        $results = [];

        if (!empty($form['usuario_id'])) {
            $usuarioCi = $form['usuario_id'];

            $query1 = DB::table('transactions as a')
                ->select(
                    DB::raw("CONCAT(a.id, '-QR') as id"),
                    DB::raw("CONCAT(DATE_FORMAT(a.created_at, '%d/%m/%Y'), ' - ', FORMAT(a.amount, 0, 'de_DE'), ' - QR') as fecha_monto")
                )
                ->where('a.status', 'confirmed')
                ->where('a.factura_id', 0)
                ->where('a.ci', $usuarioCi);

            $query2 = DB::table('bancard_transactions as a')
                ->select(
                    DB::raw("CONCAT(a.id, '-TARJETA') as id"),
                    DB::raw("CONCAT(DATE_FORMAT(a.created_at, '%d/%m/%Y'), ' - ', FORMAT(a.amount, 0, 'de_DE'), ' - TARJETA') as fecha_monto")
                )
                ->where('a.status', 'Success')
                ->where('a.factura_id', 0)
                ->where('a.ci', $usuarioCi);

            $results = DB::table(DB::raw("({$query1->union($query2)->toSql()}) as transacciones"))
                ->mergeBindings($query1)
                ->orderBy('fecha_monto', 'asc')
                ->get();
        }

        return $results;
    }



    public function reenviarMegaprint($id)
    {
        /*$factura = Factura::findOrFail($id);
        $factura->estado_factura = 'Pendiente';
        $factura->save();*/

        \Alert::add('error', 'No es posible realizar esta acción')->flash();
        return \Redirect::to('/admin/historial-factura');

        return redirect()->back()->with('warning', 'No es posible realizar esta acción, favor verificar con el departamento técnico');
        return redirect()->back()->with('success', 'La factura fue marcada para reenvío.');
    }

}
