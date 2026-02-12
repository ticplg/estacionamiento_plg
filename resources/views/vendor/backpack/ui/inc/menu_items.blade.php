{{-- This file is used for menu items by any Backpack v6 theme --}}

@if(backpack_user()->email != 'antoniag@paseolagaleria.com.py')
    <li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
    {{--@includeWhen(class_exists(\Backpack\DevTools\DevToolsServiceProvider::class), 'backpack.devtools::buttons.sidebar_item')--}}

    <x-backpack::menu-dropdown title="Autenticación" icon="la la-user-secret">
        <x-backpack::menu-dropdown-header title="Autenticación" />
        <x-backpack::menu-dropdown-item title="Usuarios" icon="la la-user" :link="backpack_url('user')" />
        {{--<x-backpack::menu-dropdown-item title="Roles" icon="la la-group" :link="backpack_url('role')" />
        <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />--}}
    </x-backpack::menu-dropdown>

    <x-backpack::menu-item title="Tickets" icon="la la-bar-chart" :link="backpack_url('registro-estacionamiento')" />
    {{--<x-backpack::menu-item title="Facturas y Pagos" icon="la la-question" :link="backpack_url('registro-estacionamiento-pago')" />--}}

    <x-backpack::menu-dropdown title="Finanzas" icon="la la-money">
        <x-backpack::menu-dropdown-header title="Finanzas" />
        <x-backpack::menu-dropdown-item title="Facturas" icon="la la-file" :link="backpack_url('historial-factura')" />
        <x-backpack::menu-dropdown-item title="Facturas Cajero Skydata" icon="la la-file" :link="backpack_url('factura-cajero-skydata')" />
        {{--<x-backpack::menu-dropdown-item title="Pagos" icon="la la-money" :link="backpack_url('transaction')" />--}}
    </x-backpack::menu-dropdown>

    <x-backpack::menu-dropdown title="Configuraciones" icon="la la-cogs">
        <x-backpack::menu-dropdown-header title="Configuraciones" />
        <x-backpack::menu-dropdown-item title="Configuraciones" icon="la la-cogs" :link="backpack_url('configuracion')" />
        <x-backpack::menu-dropdown-item title="Eventos" icon="la la-question" :link="backpack_url('evento-descuento')" />
        <x-backpack::menu-dropdown-item title="Eventos especiales" icon="la la-question" :link="backpack_url('evento-especial')" />
        {{--<x-backpack::menu-dropdown-item title="Pagos" icon="la la-money" :link="backpack_url('transaction')" />--}}
        <x-backpack::menu-dropdown-item title="Punto Venta" icon="la la-dollar" :link="backpack_url('punto-venta')" />
        <x-backpack::menu-dropdown-item title="Codigos Descuento" icon="la la-question" :link="backpack_url('descuento-estacionamiento')" />
        <x-backpack::menu-dropdown-item title="Cajeros Skydata" icon="la la-question" :link="backpack_url('cajero-skydata')" />
    </x-backpack::menu-dropdown>
@endif



{{--<x-backpack::menu-item title="Estacionamiento" icon="la la-cab" :link="backpack_url('espacio-estacionamiento')" />
<x-backpack::menu-item title="Marcaciones" icon="la la-history" :link="backpack_url('historial-marcacion')" />--}}
<x-backpack::menu-item title="Reporte" icon="la la-print" :link="backpack_url('reporte-registro-estacionamiento/create')" />

{{--
<x-backpack::menu-item title="Ticket porterias" icon="la la-question" :link="backpack_url('ticket-porteria')" />
<x-backpack::menu-item title="Ticket hotels" icon="la la-question" :link="backpack_url('ticket-hotel')" />--}}