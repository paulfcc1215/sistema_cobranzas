<?php
$_AM['udn'] = AutoModel::getInstance('estructura','udn',Db::getInstance());
$_AM['campana'] = AutoModel::getInstance('campanas','campana',Db::getInstance());
print_arr($_AM);die;

$campana=$_AM['campana']->getById($_GET['id_camp']);
$udn=$_AM['udn']->getById($campana->id_udn);


switch($_GET['step']) {
    case '2':
        try {
            if(trim($_POST['nombre_proceso'])=='')
                throw new Exception('Debe indicar un nombre para el proceso');
			/*
            $config=json_decode((base64_decode($_POST['configuraciones'])),true);
            if($config==false)
                throw new Exception('Configuraciones inválidas');
			*/
            
            $db->startTransaction();
            $proceso=$_AM['proceso']->insert(array(
                'id_campana'=>$_GET['id_camp'],
                'descripcion'=>$_POST['nombre_proceso'],
                'fecha_apertura'=>'NOW()',
                'status'=> '1'
            ));
            
            foreach($config as $k=>$v) {
                $is_array='0';
                if(is_array($v)) {
                    $v=implode(',',$v);
                    $is_array='1';
                }
                $_AM['metadata']->insert(array(
                    'fk_tabla'=>'proceso',
                    'fk_valor'=>$proceso->id_proceso,
                    'key'=>$k,
                    'value'=>$v,
                    'is_array'=>$is_array,
                    'status'=> '1'
                ));
            }
            
            
            $db->commit();
            $_T['maintitle']='Planificación de Operación - Nuevo Proceso de Campaña';
            $_T['maincontent']='<h2 style="color: green;">Datos almacenados satisfactoriamente</h2>
            <hr>
            <a href="?mod=planificacion/procesos/index&id_camp='.$_GET['id_camp'].'">Finalizar<?a>
            ';
            

        
        }catch(Exception $e) {
            $error=$e->getMessage();
            goto lbl_default;
        }
    break;
    
    default:
        lbl_default:
        $_T['maintitle']='Planificación de Operación - Nuevo Proceso de Campaña';
        
        if($error!='') {
            $_T['maincontent']='<div style="margin-bottom: 10px; color: maroon; font-weight: bold; font-size: 18px;">'.$error.'</div>';
        }
        $_T['maincontent'].='
        <form method="POST" id="theForm" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'">
        <input type="hidden" name="configuraciones" id="hidden_target">
        <b>UDN: '.$udn->udn.'</b>
        <br>
        <b>Campaña: '.$campana->campana.'</b>
        <br><br>
        <div class="form-group">
            <label>Nombre del Proceso</label>
            <input type="text" name="nombre_proceso" class="form-control" value="'.$_POST['nombre_proceso'].'" placeholder="Indique el nombre del proceso...">
        </div>
        <div class="form-group">
        <b>Configuración del grupo:</b>
        ';
        
        $select=new UIComponents_ConfigSelector();
        $aux=array();
        foreach($_AM['metadata_usable']->getByAndCond(array('aplicable_a'=>'proceso')) as $r) {
            $aux[]=$r->toArray();
        }
        $select->source_data=$aux;
        $select->form_id='theForm';
        $select->hidden_target='hidden_target';
        $select->value=$_POST['configuraciones'];
        
        $_T['maincontent'].=$select->draw();
        $_T['maincontent'].='</div>
        <br>
        <button class="btn btn-primary">Guardar</button>
        </form>
        ';
        
    break;
}
