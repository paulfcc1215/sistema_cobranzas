<?php

    if(!Auth::hasPrivileges('AUTH_PARAMETROS_VER')) throw new Exception('No autorizado - AUTH_PARAMETROS_VER');

    switch($_GET['step']) {
        default:
            $_T['maintitle']='Parámetros del sistema';
            $_T['maincontent']='
            <ul>
            <li><a href="?mod=parametros/empresas/index">Empresas</a></li>
            <li><a href="?mod=parametros/udns/index">UDNs</a></li>
            <li><a href="?mod=parametros/campanas/index">Campañas</a></li>
            <li><a href="?mod=parametros/procesos/index">Procesos</a></li>
            <li><a href="?mod=parametros/scripts/index">Scripts</a></li>
            <!--<li><a href="?mod=parametros/sucursales/index">Sucursales</a></li>
            <li><a href="?mod=parametros/tipo_cartera/index">Tipo de cartera</a></li>
            <li><a href="?mod=parametros/edades_mora/index">Edades de mora</a></li>
            <li><a href="?mod=parametros/parametros_gestion/index">Parámetros de gestión</a></li>
            <li><a href="?mod=parametros/tipos_accion/index">Tipos de acciones</a></li>
            <li><a href="?mod=parametros/listas_trabajo/index">Listas de trabajo</a></li>-->
            <li><a href="?mod=parametros/direcciones/index">Gestión de direcciones</a></li>
            <li><a href="?mod=parametros/telefonos/index">Gestión de teléfonos</a></li>
            </ul>';
        break;
    }