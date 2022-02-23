<?php
require '../config.php';
try {
    $db=Db::getInstance();
    $ret=array(
        'success'=>true,
        'data'=>array()
    );
    switch($_REQUEST['what']) {
        default:
            throw new Exception('Debe indicar "what"');
        break;
        case 'provincias':
            $q0=$db->query('SELECT * FROM catalogos_otros.bp_provincias WHERE status=\'1\' ORDER BY provincia');
            foreach($q0 as $p) {
                $ret['data'][$p['id_provincia']]=$p['provincia'];
            }
        break;
        
        case 'ciudades':
            if($_REQUEST['provincia']=='') throw new Exception('Debe indicar "provincia"');
            if(!preg_match('#^\d+#',$_REQUEST['provincia'])) {
                $q0=$db->query('SELECT * FROM catalogos_otros.bp_provincias WHERE provincia=\''.$db->escape($_REQUEST['provincia']).'\'');
                $id_provincia=$q0->current()['id_provincia'];
            }else{
                $id_provincia=$_REQUEST['provincia'];
            }

            $query='SELECT * FROM catalogos_otros.bp_ciudades WHERE status=\'1\'';
            $query.=' AND id_provincia=\''.$id_provincia.'\'';
            $query.=' ORDER BY ciudad';
            $q0=$db->query($query);
            if($q0->numRows()==0)
                throw new Exception('No hay ciudades en la provincia indicada');
            foreach($q0 as $p) {
                $ret['data'][$p['id_ciudad']]=$p['ciudad'];
            }
        break;        

        case 'cantones':
            if($_REQUEST['provincia']=='') throw new Exception('Debe indicar "provincia"');
            if(!preg_match('#^\d+#',$_REQUEST['provincia'])) {
                $q0=$db->query('SELECT * FROM catalogos_otros.bp_provincias WHERE provincia=\''.$db->escape($_REQUEST['provincia']).'\'');
                $id_provincia=$q0->current()['id_provincia'];
            }else{
                $id_provincia=$_REQUEST['provincia'];
            }

            $query='SELECT * FROM catalogos_otros.bp_cantones WHERE status=\'1\'';
            $query.=' AND id_provincia=\''.$id_provincia.'\'';
            $query.=' ORDER BY canton';
            $q0=$db->query($query);
            if($q0->numRows()==0)
                throw new Exception('No hay cantones en la provincia indicada');
            foreach($q0 as $p) {
                $ret['data'][$p['id_canton']]=$p['canton'];
            }
        break;
        
        case 'parroquias':
            if($_REQUEST['canton']=='') throw new Exception('Debe indicar "canton"');
            if(!preg_match('#^\d+#',$_REQUEST['canton'])) {
                $q0=$db->query('SELECT * FROM catalogos_otros.bp_cantones WHERE canton=\''.$db->escape($_REQUEST['canton']).'\'');
                $id_canton=$q0->current()['id_canton'];
            }else{
                $id_canton=$_REQUEST['canton'];
            }

            $query='SELECT * FROM catalogos_otros.bp_parroquias WHERE status=\'1\'';
            $query.=' AND id_canton=\''.$id_canton.'\'';
            $query.=' ORDER BY parroquia';
            $q0=$db->query($query);
            if($q0->numRows()==0)
                throw new Exception('No hay parriquias en el canton indicado');
            foreach($q0 as $p) {
                $ret['data'][$p['id_parroquia']]=$p['parroquia'];
            }        
        break;
        
        
    }
    
    

}catch(Exception $e){
    $ret=array(
        'success'=>false,
        'error'=>$e->getMessage()
    );
}
echo json_encode($ret);
die();