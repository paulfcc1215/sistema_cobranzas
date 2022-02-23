<?php
    
    if(!Auth::hasPrivileges('AUTH_SEGURIDAD_VER')) throw new Exception('No autorizado - AUTH_SEGURIDAD_VER');
    
    //$_T['navigation'] = get_navigation(__FILE__);
    $_T['maintitle']='Seguridad y acceso';
    $_T['maincontent'].='
    <ul>';
    if(Auth::hasPrivileges('AUTH_SEGURIDAD_USUARIOS_INDEX')) {
        $_T['maincontent'].='<li><a href="?mod=seguridad_acceso/usuarios/index">Usuarios</a></li>';
    }
    if(Auth::hasPrivileges('AUTH_SEGURIDAD_PERFILES_INDEX')) {
        $_T['maincontent'].='<li><a href="?mod=seguridad_acceso/perfiles/index">Perfiles de usuario</a></li>';
    }
    if(Auth::hasPrivileges('AUTH_SEGURIDAD_PRIVILEGIOS_INDEX')) {
        $_T['maincontent'].='<li><a href="?mod=seguridad_acceso/privilegios/index">Privilegios</a></li>';
    }
    if(Auth::hasPrivileges('AUTH_SEGURIDAD_CONTROL_ACCESO_INDEX')) {
        //$_T['maincontent'].='<li><a href="?mod=seguridad_acceso/control_accesos/index">Control de accesos</a></li>';
    }
    if(Auth::hasPrivileges('AUTH_SEGURIDAD_CONTROL_ACCESO_INDEX')) {
        $_T['maincontent'].='<li><a href="?mod=seguridad_acceso/log_auditoria/index">Log de auditoria</a></li>';
    }
    $_T['maincontent'].='
    </ul>';