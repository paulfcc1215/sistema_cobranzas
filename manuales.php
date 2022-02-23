<?php
require 'config.php';
require 'auth.php';

$SM=SessionManager::getInstance();
$_T['showsidebar']=true;
$_T['projectname']='Comercial V2 - '.$SM->user['usr_logname'];
$_T['navbar_li'].='<li><a href="logout.php">Salir</a></li>';


try {
    try {
        if($_GET['mod']=='') throw new Exception('No se pudo ubicar el manual solicitado');
        $_GET['mod']=preg_replace('#[^a-zA-Z0-9_/]#','',$_GET['mod']);
        if(is_readable('manuales/'.$_GET['mod'].'.php')) {
            require 'manuales/'.$_GET['mod'].'.php';
        }else if(is_readable('manuales/'.$_GET['mod'].'.html')){
            $_T['maincontent']=file_get_contents('manuales/'.$_GET['mod'].'.html');
        }else{
            throw new Exception('No se pudo leer el manual solicitado');
        }
    }catch(Exception $e){
        $_T['maincontent'].='<h2 style="color: red;">'.$e->getMessage().'</h2>';
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
