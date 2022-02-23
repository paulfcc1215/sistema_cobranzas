<?php
require_once '../config.php';
try {
    Auth::enforcePrivileges(array('AUTH_PRODUCTOS_FORMULARIO_SAVE'));
    $db=Db::getInstance();
    $_M['grupo']=AutoModel::getInstance('formularios','grupo',$db);
    $_M['fila']=AutoModel::getInstance('formularios','fila',$db);
    $_M['componente']=AutoModel::getInstance('formularios','componente',$db);
    $_M['configuracion']=AutoModel::getInstance('formularios','configuracion',$db);

    $id_producto=$_POST['id_producto'];
    $db->startTransaction();
    // borrar todo
    foreach($_M['grupo']->getByAndCond(array('id_producto'=>$id_producto)) as $grupo) {
        foreach($_M['fila']->getByAndCond(array('id_grupo'=>$grupo->id_grupo)) as $fila) {
            foreach($_M['componente']->getByAndCond(array('id_fila'=>$fila->id_fila)) as $componente) {
                foreach($_M['configuracion']->getByAndCond(array('id_componente'=>$componente->id_componente)) as $configuracion) {
                    $configuracion->delete();
                }
                $componente->delete();
            }
            $fila->delete();
        }
        $grupo->delete();
    }
    // generar todo
    $data=json_decode($_POST['content'],true);
    if($data===false) throw new Exception('Data con formato incorrecto');
    $counts=array(
        'grupo'=>0,
        'fila'=>0,
        'componente'=>0,
    );
    foreach($data as $grupo_nombre=>$filas) {
        $counts['grupo']++;
        $row_grupo=$_M['grupo']->insert(array(
            'id_producto'=>$id_producto,
            'nombre'=>$grupo_nombre,
            'orden'=>$counts['grupo'],
        ));
        foreach($filas as $componentes) {
            $counts['fila']++;
            $row_fila=$_M['fila']->insert(array(
                'id_grupo'=>$row_grupo->id_grupo,
                'orden'=>$counts['fila']
            ));
            foreach($componentes as $componente) {
                $counts['componente']++;
                $row_componente=$_M['componente']->insert(array(
                    'id_fila'=>$row_fila->id_fila,
                    'class'=>$componente['componente_class'],
                    'nombre'=>$componente['nombre_campo'],
                    'orden'=>$counts['componente'],
                    'agrupable'=>($componente['agrupable']=='SI'?'1':'0'),
                ));
                
                foreach($componente['config'] as $k=>$v) {
                    $row_config=$_M['configuracion']->insert(array(
                        'id_componente'=>$row_componente->id_componente,
                        'nombre'=>$k,
                        'valor'=>$v
                    ));
                }
            }
        }
    }
    
    // Undo facilitie
    if(!defined('_INTERNALS_SKIP_SAVE_UNDO')) {
        $db->query('UPDATE formularios.undo SET current=\'0\' WHERE id_producto=\''.$db->escape($id_producto).'\'');
        $db->query('INSERT INTO formularios.undo
        (
        id_producto,fecha,contenido,current
        )
        VALUES
        (
            \''.$id_producto.'\',
            NOW(),
            \''.$db->escape(json_encode($data)).'\',
            \'1\'
        )');
    }
    
    echo '1';
    $db->commit();
}catch(Exception $e) {
    $db->rollback();
    echo '0:'.$e->getMessage();
    die();
}