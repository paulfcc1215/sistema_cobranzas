<?php

    if(!Auth::hasPrivileges('AUTH_PARAMETROS_PROCESOS_CREAR')) throw new Exception('No autorizado - AUTH_PARAMETROS_PROCESOS_CREAR');

    $_AM['udn'] = AutoModel::getInstance('estructura','udn',Db::getInstance());
    $_AM['campana'] = AutoModel::getInstance('campanas','campana',Db::getInstance());

    foreach ($_AM['campana']->getAll() as $c){
        $aux = $c->getData();
        $campanas[$aux['id_campana']] = $_AM['udn']->getById($aux['id_udn'])->getData()['udn']. ' - '. $aux['campana'];
    }
    $_T['maintitle']='Parámetros del sistema - Procesos - Nuevo proceso';
    switch($_GET['step']) {
        case '2':
            try {
                if(trim($_POST['campana'])=='') throw new Exception('Seleccione Campaña');
                if(trim($_POST['nombre_proceso'])=='') throw new Exception('Debe indicar un nombre para el proceso');
                $db->startTransaction();
                $_AM['proceso'] = AutoModel::getInstance('campanas','proceso',Db::getInstance());
                $_POST['estado']=='on'?$status='1':$status='0';
                $proceso=$_AM['proceso']->insert(array(
                    'id_campana'=>$_POST['campana'],
                    'descripcion'=>$_POST['nombre_proceso'],
                    'fecha_apertura'=>'NOW()',
                    'status'=> $status
                ));
                $db->commit();
                $_T['maincontent']='<h2 style="color: green;">Datos almacenados satisfactoriamente</h2>
                <hr>
                <a href="?mod=parametros/procesos/index">Finalizar<?a>';
            
            }catch(Exception $e) {
                $error=$e->getMessage();
                goto lbl_default;
            }
        break;
        
        default:
            lbl_default:
            
            if($error!='') {
                $_T['maincontent']='<div style="margin-bottom: 10px; color: maroon; font-weight: bold; font-size: 18px;">'.$error.'</div>';
            }
            $_T['maincontent'].='
            <form method="POST" id="theForm" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'">
                <div class="form-group">
                    <label>Campaña:</label>
                    <select name="campana" class="form-control">'.UI_Helper::array_to_options($campanas,$_POST['campana'],true).'</select>
                </div>
                <div class="form-group">
                    <label>Nombre del Proceso:</label>
                    <input type="text" name="nombre_proceso" class="form-control" value="'.$_POST['nombre_proceso'].'" placeholder="Indique el nombre del proceso...">
                </div>
                <div class="form-group">
                    <label>Fecha de creación:</label>
                    <input type="text" name="fecha_creacion" class="form-control" value="'.($_POST['fecha_creacion']==''?date('Y-m-d H:i:s'):$_POST['fecha_creacion']).'" readonly>
                </div>
                <div class="checkbox">
                    <input type="checkbox" name="estado" data-toggle="toggle" data-on="Activo" data-off="Inactivo" data-onstyle="success" data-width="90" data-height="34" checked>
                </div>
                <br>
                <button class="btn btn-primary">Guardar</button>
            </form>
            ';
            
        break;
    }
