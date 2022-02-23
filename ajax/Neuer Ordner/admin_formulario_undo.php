<?php
require '../config.php';
try {
    Auth::enforcePrivileges(array('AUTH_PRODUCTOS_FORMULARIO_SAVE'));
    $db=Db::getInstance();
    $_M['grupo']=AutoModel::getInstance('formularios','grupo',$db);
    $_M['fila']=AutoModel::getInstance('formularios','fila',$db);
    $_M['componente']=AutoModel::getInstance('formularios','componente',$db);
    $_M['configuracion']=AutoModel::getInstance('formularios','configuracion',$db);
    define('_INTERNALS_SKIP_SAVE_UNDO',true);
    $before=$db->query('SELECT * FROM formularios.undo WHERE id_undo < (SELECT id_undo FROM formularios.undo WHERE "current"=\'1\' AND id_producto=\''.$db->escape($_GET['id']).'\') ORDER BY id_undo DESC LIMIT 1');
    $current=$db->query('SELECT * FROM formularios.undo WHERE "current"=\'1\' AND id_producto=\''.$db->escape($_GET['id']).'\'');
    $after=$db->query('SELECT * FROM formularios.undo WHERE id_undo > (SELECT id_undo FROM formularios.undo WHERE "current"=\'1\' AND id_producto=\''.$db->escape($_GET['id']).'\') ORDER BY id_undo ASC LIMIT 1');
    
    $state=array(
     'before'=>null,
     'current'=>null,
     'after'=>null
    );
    if($before->numRows()==1) $state['before']=$before->current();
    if($current->numRows()==1) $state['current']=$current->current();
    if($after->numRows()==1) $state['after']=$after->current();
    $target=null;
    switch($_GET['a']) {
        case 'u':
            $target=&$state['before'];
        break;
        
        case 'r':
            $target=&$state['after'];
        break;
    }
    if(is_null($target)) throw new Exception('No existen datos en el historico');
    $db->startTransaction();
    $db->query('UPDATE formularios.undo SET current=\'0\' WHERE id_producto=\''.$db->escape($_GET['id']).'\'');
    $db->query('UPDATE formularios.undo SET current=\'1\' WHERE id_undo='.$target['id_undo']);
    $_POST['id_producto']=$target['id_producto'];
    $_POST['content']=$target['contenido'];
    require 'admin_formulario_save.php';
    
}catch(Exception $e) {
    $db->rollback();
    echo '0:'.$e->getMessage();
    die();
}