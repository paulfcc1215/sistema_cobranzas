<?php
try {
    $session=SessionManager::getInstance(_SESSION_NAME);
    if(!Auth::isLogged()) {
        if($_POST['username']!='' && $_POST['password']!='') {
            $isLogged=Auth::login($_POST['username'],$_POST['password'],$msg);
            if(!$isLogged) {
                Log::addLog('LOGIN_FAIL',__FILE__,array('username'=>$_POST['username'],'error'=>$msg));
                throw new Exception($msg);
            }else{
                Log::addLog('LOGIN',__FILE__,array('username'=>$_POST['username']));
            }
        }
        if(!Auth::isLogged()) throw new Exception('');
    }

    if($session->user['force_pw_change']=='1') {
        Log::addLog('LOGIN_PW_CHANGE_REDIR',__FILE__,'');
        header('Location: change_pw.php');
        die();
    }
    

}catch(Exception $e) {
    $_T['userval']=$_POST['username'];
    $_T['errmsg']=$e->getMessage();
    require 'template/login.php';    
    die();
}
