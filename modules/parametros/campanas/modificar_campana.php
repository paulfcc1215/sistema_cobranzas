<?php

    if(!Auth::hasPrivileges('AUTH_PARAMETROS_CAMPANAS_MODIFICAR')) throw new Exception('No autorizado - AUTH_PARAMETROS_CAMPANAS_MODIFICAR');

    $_AM['udns']=AutoModel::getInstance('estructura','udn',DB::getInstance());
    $_AM['campanas']=AutoModel::getInstance('campanas','campana',DB::getInstance());
    $_AM['metadata_usable']=AutoModel::getInstance('metadata','metadata_usable',DB::getInstance());
    $_AM['metadata']=AutoModel::getInstance('metadata','metadata',DB::getInstance());

    $campana=$_AM['campanas']->getById($_GET['id']);
    if(!$campana) throw new Exception('Id de campaña inválido');
    if($_POST['configuraciones']=='') {
        $md=$_AM['metadata']->getByAndCond(array(
            'fk_tabla'=>'campana',
            'fk_valor'=>$campana->id_campana,
            'status'=>'1'
        ));
        $configs=array();
        foreach($md as $m) {
            if($m->is_array=='1') {
                $configs[$m->key]=explode(',',$m->value);
            }else{
                $configs[$m->key]=$m->value;
            }
        }
        $configs=base64_encode(json_encode($configs));
        $_POST['configuraciones']=$configs;
    }

    if($_POST['id_udn']=='') {
        $_POST['id_udn']=$campana->id_udn;
    }
    if($_POST['nombre_campana']=='') {
        $_POST['nombre_campana']=$campana->campana;
    }
    if($_POST['carpeta_control']=='') {
        $_POST['carpeta_control']=$campana->carpeta_control;
    }

    $_T['maintitle']='Parámetros del sistema - Campañas - Editar campaña';
    switch($_GET['step']) {
        case 'ajax':
            $ret=array(
                'success'=>true,
                'data'=>array()
            );
            try {
                switch($_GET['a']) {
                    case 'getInstrumentoClasses':
                        if(!preg_match('#^[a-zA-Z0-9_]+$#',$_POST['path'])) throw new Exception('Ruta inválida');
                        $dhdl=opendir(_BASE_INSTRUMENTOS_PATH.'/'.$_POST['path']);
                        while($ptr=readdir($dhdl)) {
                            if($ptr=='.' || $ptr=='..') continue;
                            if(preg_match('#^(Instrumento_.*?)\.class\.php$#',$ptr,$matches)) {
                                $ret['data'][]=$matches[1];
                            }
                        }
                    break;
                    
                }
            }catch(Exception $e) {
                $ret=array(
                    'success'=>false,
                    'data'=>$e->getMessage
                );
            }
            echo json_encode($ret);
            die();
        break;
        
        case '2':
            try {
                if($_POST['nombre_campana']=='')
                    throw new Exception('El nombre de campana no puede estar vacío');
                if(!is_dir(_BASE_USER_PATH.'/'.$_POST['carpeta_control']))
                    throw new Exception('Carpeta controladora invalida');
                    
                if($_POST['configuraciones']!='') {
                    $config=json_decode(base64_decode($_POST['configuraciones']),true);
                    if($config===false)
                        throw new Exception('El valor indicado en configuraciones es invalido');
                }else{
                    $config=array();
                }
                
                $db->startTransaction();
                $campana->id_udn=$_POST['id_udn'];
                $campana->campana=$_POST['nombre_campana'];
                $campana->carpeta_control=$_POST['carpeta_control'];
                
                foreach($_AM['metadata']->getByAndCond(array(
                    'fk_tabla'=>'campana',
                    'fk_valor'=>$campana->id_campana,
                    'status'=>'1'
                )) as $m) $m->delete();

                foreach($config as $k=>$v) {
                    if(is_array($v)) {
                        $is_array='1';
                        $v=implode(',',$v);
                    }else{
                        $is_array='0';
                    }
                    
                    $_AM['metadata']->insert(
                        array(
                            'fk_tabla'=>'campana',
                            'fk_valor'=>$campana->id_campana,
                            'key'=>$k,
                            'value'=>$v,
                            'is_array'=>$is_array,
                            'status'=>'1'
                        )
                    );
                }
                $db->commit();
                $_T['maincontent']='<h2 style="color: green;"> Se ha modificado la campaña satisfactoriamente</h2>
                <hr>
                <a href="?mod=parametros/campanas/index">Regresar</a>';
            }catch(Exception $e) {
                $db->rollback();
                $error=$e->getMessage();
                goto lbl_default;
            }
        break;
        
        default:
            lbl_default:
            $dhdl=opendir(_BASE_INSTRUMENTOS_PATH);
            while($ptr=readdir($dhdl)) {
                if($ptr=='.' || $ptr=='..') continue;
                if(is_dir(_BASE_INSTRUMENTOS_PATH.'/'.$ptr)) {
                    $instrumentos_options[]='<option value="'.$ptr.'"'.($_POST['instrumento_path']==$ptr?' selected="1"':'').'>'.$ptr.'</option>';
                }
            }
            if($error!='') {
                $_T['maincontent'].='<span style="color: maroon; font-weight: bold;">'.$error.'</span>';
            }
            $_T['top_jscript']='
                function updateInstrumentoClass(path) {
                    var cb=null;
                    if(typeof arguments[1] == "function") {
                        cb=arguments[1];
                    }
                    $.ajax({
                        "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getInstrumentoClasses')).'",
                        "method":"POST",
                        "data":{
                            "path":path
                        },
                        "success":function(d) {
                            try {
                                d=$.parseJSON(d);
                                if(!d) throw "Error en la respuesta del servidor";
                                if(!d.success) throw d.data;
                                d=d.data;
                                var options=new Array();
                                for(var i in d) {
                                    options.push("<option value=\'"+d[i]+"\'>"+d[i]+"</option>");
                                }
                                $("#id_instrumento_class").html(options.join(""));
                                if(cb != null) {
                                    console.log("Calling callback");
                                    cb(path);
                                }
                                
                            }catch(err) {
                                alert(err);
                            }
                        }
                        
                    });
                }
            ';
            $_T['maincontent'].='
            <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'" id="theForm">
            <input type="hidden" name="configuraciones" id="hidden_target">
            <div class="form-group">
                <label>Nombre de la UDN</label>
                <select class="form-control" name="id_udn">
                <option value="">Seleccione...</option>
            ';
            foreach($_AM['udns']->getAll() as $udn) {
                $_T['maincontent'].='<option value="'.$udn->id_udn.'"'.(($_POST['id_udn']==$udn->id_udn)?' selected="1"':'').'>'.$udn->id_udn.' - '.$udn->udn.'</option>';
            }
            $_T['maincontent'].='
                </select>
            </div>
            <div class="form-group">
                <label>Nombre de la Campaña</label>
                <input type="text" class="form-control" name="nombre_campana" placeholder="Nombre de la campaña" value="'.$_POST['nombre_campana'].'">
            </div>

            <div class="form-group">
                <label>Carpeta Controladores</label>
                <select class="form-control" name="carpeta_control">
                <option value="">Seleccione...</option>
            ';
            foreach(getCarpetasControles() as $c) {
                $_T['maincontent'].='<option value="'.$c.'"'.($_POST['carpeta_control']==$c?' selected="1"':'').'>'.$c.'</option>';
            }
            $_T['maincontent'].='
                </select>
            </div>

            <div class="form-group">
                <label>Metadata</label>
            ';
            
            
            $select=new UIComponents_ConfigSelector();
            $aux=array();
            foreach($_AM['metadata_usable']->getByAndCond(array('aplicable_a'=>'campana')) as $r) {
                $aux[]=$r->toArray();
            }
            $select->source_data=$aux;
            $select->form_id='theForm';
            $select->hidden_target='hidden_target';
            $select->value=$_POST['configuraciones'];
            
            $_T['maincontent'].=$select->draw();
            $_T['maincontent'].='
            </div>
    
    <button type="submit" class="btn btn-primary">Guardar</button>
    <button class="btn btn-danger" type="button" onclick="window.location=\'?mod=parametros/campanas/index\'">Cancelar</button> 
    <button class="btn btn-warning" type="button" onclick="window.location=\'?mod=parametros/procesos/index&id_camp='.$_GET['id'].'\'">Ver Procesos</button> 
            </form>
            <script>
            ';
            if($_POST['instrumento_class']!='') {
                $_T['maincontent'].='
                $(document).ready(function() {
                    updateInstrumentoClass($("[name=\'instrumento_path\']").val(),function() {
                        $("[name=\'instrumento_class\']").find("option").each(function(k,o) {
                            if(o.value=="'.$_POST['instrumento_class'].'") {
                                o.selected="1";
                                return false;
                            }
                        });
                    });
                });
                ';
            }
            $_T['maincontent'].='
            </script>
            ';
        break;
    }