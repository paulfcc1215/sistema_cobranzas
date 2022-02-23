<?php

    require '../../../config.php';
    $result = array(
        'status' => 'ok',
        'data' => null,
        'message' => ''
    );

    try{
        switch($_GET['action']){
            case 'get_campanas':
                $aux = array();
                foreach(getCampanasByUdn($_POST['id_udn']) as $c){
                    $aux[$c['id_campana']]=$c['campana'];
                }
                $result['data']=$aux;
            break;
            case 'get_procesos':
                $aux = array();
                foreach(getProcesoByCampana($_POST['id_campana']) as $c){
                    $aux[$c['id_proceso']]=$c['descripcion'];
                }
                $result['data']=$aux;
            break;
        }
    }catch(Exception $ex){
        $result['status'] = 'error';
        $result['message'] = $ex->getMessage();
    }

    echo json_encode($result);
    die();