<?php

    if(!Auth::hasPrivileges('AUTH_PARAMETROS_EMPRESAS_MODIFICAR')) throw new Exception('No autorizado - AUTH_PARAMETROS_EMPRESAS_MODIFICAR');
    $_AM['empresa']=AutoModel::getInstance('estructura','empresa',DB::getInstance());
    $_AM['metadata']=AutoModel::getInstance('metadata','metadata',DB::getInstance());

    $empresa=$_AM['empresa']->getById($_GET['id']);
    if(!$empresa) throw new Exception('Empresa inválida');
    switch($_GET['step']) {
        case '2':
            try {
                if($_POST['nombre_empresa']=='')
                    throw new Exception('El nombre de empresa no puede estar vacío');

                
                
                $db->startTransaction();
                $empresa=$_AM['empresa']->getById($_GET['id']);
                $empresa->nombre=$_POST['nombre_empresa'];
                metadata_save($_POST['configuraciones'],'empresa',$empresa->id_empresa);
                
                $db->commit();
                $_T['maincontent']='<h2 style="color: green;"> Se ha modificado la empresa satisfactoriamente</h2>
                <hr>
                <a href="?mod=parametros/empresas/index">Regresar</a>';
            }catch(Exception $e) {
                $db->rollback();
                $error=$e->getMessage();
                goto lbl_default;
            }
        break;
        
        default:
            $_POST['nombre_empresa']=$empresa->nombre;
            lbl_default:
            $_T['maintitle']='Planificación de Operación - Empresas - Editar Empresa';
            if($error!='') {
                $_T['maincontent'].='<span style="color: maroon; font-weight: bold;">'.$error.'</span>';
            }
            $_T['maincontent'].='
            <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'" id="theForm">
            <input type="hidden" name="configuraciones" id="hidden_target">
            <div class="form-group">
                <label>Nombre de la Campaña</label>
                <input type="text" class="form-control" name="nombre_empresa" placeholder="Nombre de la empresa" value="'.$_POST['nombre_empresa'].'">
            </div>
            <div class="form-group">
                <label>Configuraciones</label>
            ';
            
            $component=new UIComponents_ConfigSelector();
            $component->source_data=metadata_load_usable('empresa');
            $component->form_id='theForm';
            $component->hidden_target='hidden_target';
            $component->value=metadata_load_config_string('empresa',$_GET['id']);
            
            $_T['maincontent'].=$component->draw();
            
            
            $_T['maincontent'].='
            </div>
    
            <button type="submit" class="btn btn-primary">Guardar</button>
            <button class="btn btn-danger" type="button" onclick="window.location=\'?mod=parametros/empresas/index\'">Cancelar</button> 
            <button class="btn btn-warning" type="button" onclick="window.location=\'?mod=parametros/empresas/del&id='.$_GET['id'].'\'">Eliminar</button> 
            
            </form>
            ';
        break;
    }