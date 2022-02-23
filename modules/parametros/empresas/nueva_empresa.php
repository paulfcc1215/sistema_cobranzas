<?php

    if(!Auth::hasPrivileges('AUTH_PARAMETROS_EMPRESAS_CREAR')) throw new Exception('No autorizado - AUTH_PARAMETROS_EMPRESAS_CREAR');
    $_AM['empresa']=AutoModel::getInstance('estructura','empresa',DB::getInstance());
    $_AM['metadata']=AutoModel::getInstance('metadata','metadata',DB::getInstance());

    switch($_GET['step']) {
        case '2':
            try {
                if($_POST['nombre_empresa']=='')
                    throw new Exception('El nombre de empresa no puede estar vacío');

                $db->startTransaction();
                $empresa=$_AM['empresa']->insert(array(
                    'nombre'=>$_POST['nombre_empresa']
                ));
                metadata_save($_POST['configuraciones'],'empresa',$empresa->id_empresa);
                $db->commit();
                $_T['maincontent']='<h2 style="color: green;"> Se ha guardado la empresa satisfactoriamente</h2>
                <hr>
                <a href="?mod=parametros/empresas/index">Regresar</a>';
            }catch(Exception $e) {
                $db->rollback();
                $error=$e->getMessage();
                goto lbl_default;
            }
        break;
        
        default:
            lbl_default:
            $_T['maintitle']='Planificación de Operación - Empresas - Nueva Empresa';
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
            if($_POST['configuraciones']!='') {
                $component->value=$_POST['configuraciones'];
            }
            
            $_T['maincontent'].=$component->draw();
            
            
            $_T['maincontent'].='
            </div>
    
            <button type="submit" class="btn btn-primary">Guardar</button>
            <button class="btn btn-danger" type="button" onclick="window.location=\'?mod=parametros/empresas/index\'">Cancelar</button> 
            </form>
            ';
        break;
    }