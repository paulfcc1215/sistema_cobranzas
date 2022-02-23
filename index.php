<?php
require 'config.php';
require 'auth.php';

$SM=SessionManager::getInstance();
$_T['showsidebar']=true;
$_T['sidebarcontent'].='
<ul>';

if(Auth::hasPrivileges('AUTH_GESTIONAR')) {
    $_T['sidebarcontent'].='<li><a href="dispatcher.php?user_name='.$SM->user['usr_logname'].'">Gestionar</a></li>';
}

if(Auth::hasPrivileges('AUTH_SEGURIDAD_VER')) {
    $_T['sidebarcontent'].='<li><a href="?mod=seguridad_acceso/index"><b>Seguridad y Acceso</b></a></li>';
}

if(Auth::hasPrivileges('AUTH_PARAMETROS_VER')) {
    $_T['sidebarcontent'].='<li><a href="?mod=parametros/index"><b>Parámetros del Sistema</b></a></li>';
}

if(Auth::hasPrivileges('AUTH_CARGAS_TRABAJO_CARGAR')) {
    $_T['sidebarcontent'].='<li><a href="?mod=bases/carga"><b>Cargas de Trabajo</b></a></li>';
}

if(Auth::hasPrivileges('AUTH_MINERIA')) {
    $_T['sidebarcontent'].='<li><a href="?mod=mineria/index">Minería de Datos</a></li>';
}

if(Auth::hasPrivileges('AUTH_PLANIFICACION')) {
    //$_T['sidebarcontent'].='<li><a href="?mod=planificacion/index">Planificación</a></li>';
}

if(Auth::hasPrivileges('AUTH_CONFIG')) {
    $_T['sidebarcontent'].='
    <li><a href="?mod=config/index">Configuración</a></li>
    ';
}

if(Auth::hasPrivileges('AUTH_SCRIPTS')) {
    //$_T['sidebarcontent'].='<li><a href="?mod=scripts/index">Scripts</a></li>';
}

if(Auth::hasPrivileges('AUTH_REPORTES_INDEX')) {
    $_T['sidebarcontent'].='
    <li><a href="?mod=reportes/index">Reportes</a></li>
    ';
}


    $_T['sidebarcontent'].='
    <li><a href="?mod=toolbox/index">Toolbox</a></li>
    ';

$_T['sidebarcontent'].='<li><a href="logout.php">Salir</a></li>';

$_T['sidebarcontent'].='
</ul>';

$_T['projectname']='Cobranzas - '.$SM->user['usr_logname'];
if($_GET['mod']!='') {
    $_GET['mod']=preg_replace('#[^a-zA-Z0-9_/]#','',$_GET['mod']);
    if(is_readable('manuales/'.$_GET['mod'].'.php') || is_readable('manuales/'.$_GET['mod'].'.html')) {
        $_T['navbar_li'].='<li><a href="manuales.php?mod='.$_GET['mod'].'" target="_blank"><img src="template/assets/help-s.png" width="20" style="position: relative; top: -2px; left: 10px;"></a></li>';
    }
}

$_T['navbar_li'].='<li><a href="logout.php">Salir</a></li>';


try {
    if($_GET['mod']!='') {
        $_GET['mod']=preg_replace('#[^a-zA-Z0-9_/]#','',$_GET['mod']);

        if(!is_readable('modules/'.$_GET['mod'].'.php')) {
            $_T['maincontent']='<h1>El módulo "'.$_GET['mod'].'" no existe</h1>';
        }else{
            // hacemos $db disponible para todos los modulos
            $db=Db::getInstance();
            // cargamos archivo lib.php en la carpeta del modulo, si existe
            $path=explode('/',$_GET['mod']);
            array_pop($path);
            if(is_readable('modules/'.implode('/',$path).'/lib.php')) require 'modules/'.$path[0].'/lib.php';

            require 'modules/'.$_GET['mod'].'.php';
        }
        
    }else{
        $_T['maincontent']='Bienvenido!';
    }
}catch(Exception $e) {
    echo '
    <style>
    body {
        font-family: Tahoma;
        background-color: #FFD4D4;
        color: red;
        font-size: 18px;
    }
    </style>
    <body>
    <h2>Excepción no controlada</h2>';
    echo '<pre>';
    echo $e->getMessage();
    echo '</pre>';
    echo '</body>';
    die();
    
    
}

        
require 'template/template.php';
