<?php
if(!Auth::hasPrivileges('AUTH_MP_CONFIGURACION')) throw new Exception('No autorizado - AUTH_MP_CONFIGURACION');
$_T['maintitle']='CONFIGURACIONES';
$_T['maincontent'].='
<ul>';
if(Auth::hasPrivileges('AUTH_CONFIGURACION_USUARIOS')) {
    $_T['maincontent'].='
    <li><a href="?mod=usuarios/index">Usuarios</a></li>
    ';
}
if(Auth::hasPrivileges('AUTH_CONFIGURACION_USUARIOS_GRUPOS')) {
    $_T['maincontent'].='
    <li><a href="?mod=grupos/index">Grupos de Usuarios</a></li>
    ';
}
if(Auth::hasPrivileges('AUTH_CONFIGURACION_PRIVILEGIOS')) {
    $_T['maincontent'].='
    <li><a href="?mod=privilegios/index">Privilegios</a></li>
    ';
}

$_T['maincontent'].='
</ul>

';