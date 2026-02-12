<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\EspacioEstacionamientoRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class EspacioEstacionamientoCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class EspacioEstacionamientoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\EspacioEstacionamiento::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/espacio-estacionamiento');
        CRUD::setEntityNameStrings('Estacionamiento', 'estacionamientos');
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
        $this->crud->addColumns($this->getFieldsData(TRUE));

        CRUD::addButtonFromModelFunction('line', 'qr_code', 'qr_code', 'beginning');
        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    private function getFieldsData($show = TRUE)
    {
        return [
            [
                'label' => "Piso",
                'name' => "piso",
            ],
            [
                'label' => "Sector",
                'name' => "sector",
            ],
            [
                'label'     => "Referencias",
                'name'      => "referencias",
            ],
        ];
    }

    protected function setupShowOperation()
    {
        //CRUD::setFromDb(); // set columns from db columns.

        // Añadir las columnas de imagen
        CRUD::addColumn([
            'label' => "Piso",
            'name' => "piso",
        ]);
        
        CRUD::addColumn([
            'label' => "Sector",
            'name' => "sector",
        ]);

        CRUD::addColumn([
            'label'     => "Referencias",
            'name'      => "referencias",
        ]);
        CRUD::addColumn([
            'name' => 'qr_path',
            'label' => 'QR Code',
            'type' => 'image',
            'prefix' => 'storage/', // Ruta de tu storage, asegúrate que coincida
            'height' => '200px',
            'width' => '200px',
        ]);

        CRUD::addColumn([
            'name' => 'imagen_referencial',
            'label' => 'Imagen Referencial',
            'type' => 'image',
            'prefix' => 'storage/', // Ruta de tu storage, asegúrate que coincida
            'height' => '200px',
            'width' => '200px',
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
        CRUD::setValidation(EspacioEstacionamientoRequest::class);

        $this->crud->addFields(static::getFieldsArrayForConfiguracion());

    
        //CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    public static function getFieldsArrayForConfiguracion()
    {
        return 
        [
            [
                'label'     => "Piso",
                'type'      => "text",
                //'hint'      => "Name of merchant.",
                'name'      => "piso",
                'attributes'    => [
                    'class'         => 'form-control',
                ],
                'wrapper'   => [
                    'class'      => 
                    'form-group col-sm-6 mb-3'
                ],
            ],
            [
                'label'     => "Sector",
                'type'      => "text",
                //'hint'      => "Name of merchant.",
                'name'      => "sector",
                'attributes'    => [
                    'class'         => 'form-control',
                ],
                'wrapper'   => [
                    'class'      => 
                    'form-group col-sm-6 mb-3'
                ],
            ],
            [
                'label'     => "Referencias",
                'type'      => "textarea",
                //'hint'      => "Name of merchant.",
                'name'      => "referencias",
                'attributes'    => [
                    'class'         => 'form-control',
                ],
                'wrapper'   => [
                    'class'      => 
                    'form-group col-sm-12 mb-3'
                ],
            ],

            [
                'label' => 'Imagen Referencial',
                'name' => 'imagen_referencial',
                'type' => 'image',
                'crop' => true,
                //'aspect_ratio' => 0.5,
                'withFiles' => [
                    'disk' =>  'public',
                    'path' => 'img/referencias', // the path inside the disk where file will be stored
                ], 
            ],
        ];
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
