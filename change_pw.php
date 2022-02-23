<?php
require 'config.php';
//require 'auth.php';
$SM=SessionManager::getInstance(_SESSION_NAME);
if(!is_array($SM->user) || $SM->isLogged!==true) {
    $SM->destroy();
    header('Location: index.php');
    die();
}
$_AM['usuario']=AutoModel::getInstance('auth','auth_usuarios',DB::getInstance());


try {
    $usr=$_AM['usuario']->getById($SM->user['id_usuario']);
    if($_POST['step']!='2') throw new Exception('');
    if($_POST['password1']!=$_POST['password2']) throw new Exception('Las contraseñas indicadas no coinciden');
    if(!Auth::claveCumpleRequisitos($_POST['password1'],$msg)) {
        throw new Exception($msg);
    }
 
    $usr->pass=password_hash($_POST['password1'],PASSWORD_DEFAULT);
    $usr->force_pw_change='0';
    $usr->last_pw_change=date('Y-m-d H:i:s');
    $SM->destroy();
    
    Log::addLog('LOGIN_PW_CHANGE_SUCCESS',__FILE__,'');
    
    $_T['maincontent']='
    <div align="center">
    <h1 style="color: green;">El cambio de contraseña fue ejecutado satisfactoriamente</h1>
    Por favor utilice su nueva contraseña para ingresar<br>
    <a href="index.php">Haga click aquí</a>
    </div>
    ';
    require 'template/template.php';
    die();
    
    
    
}catch(Exception $e) {
    $_T['nombre_completo']=$usr->nombre_completo;
    $_T['errmsg']=$e->getMessage();
    require 'template/change_pw.php';
}

