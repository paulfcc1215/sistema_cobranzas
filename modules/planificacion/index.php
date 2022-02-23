<?php
Auth::enforcePrivileges('AUTH_PLANIFICACION*');


switch($_GET['step']) {
    default:
        $_T['maintitle']='Planificación de Operación';
        $_T['maincontent']='
        <ul>
        <!--<li><a href="?mod=planificacion/empresas/index">Empresas</a></li>
        <li><a href="?mod=planificacion/udns/index">UDNs</a></li>
        <li><a href="?mod=planificacion/campanas/index">Campañas</a></li>
        <li><a href="?mod=planificacion/procesos/index">Procesos</a></li>
        <li><a href="?mod=bases/carga">Carga de Bases</a></li>-->
        </ul>
        ';
    break;
}