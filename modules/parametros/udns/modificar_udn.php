<?php
    if(!Auth::hasPrivileges('AUTH_PARAMETROS_UDNS_MODIFICAR')) throw new Exception('No autorizado - AUTH_PARAMETROS_UDNS_MODIFICAR');
    $_AM['udns']=AutoModel::getInstance('estructura','udn',Db::getInstance());
    $udn=$_AM['udns']->getById($_GET['id']);
    if(!$udn) throw new Exception('UDN "'.$_GET['id'].'" inválida');
    $_AM['empresa']=AutoModel::getInstance('estructura','empresa',Db::getInstance());
    $option_empresas=array();
    if($_POST['id_empresa']=='') $_POST['id_empresa']=$udn->id_empresa;

    foreach($_AM['empresa']->getAll() as $v) {
        if($v->id_empresa==$_POST['id_empresa']) {
            $selected=true;
        }else{
            $selected=false;
        }
        $option_empresas[]='<option value="'.$v->id_empresa.'"'.($selected?' selected="1"':'').'>'.$v->id_empresa.' - '.$v->nombre.'</option>';
    }


    switch($_GET['step']) {
        case '2':
            try {
                if($_POST['id_empresa']=='')
                    throw new Exception('Debe indicar una empresa');
                if($_POST['udn']=='')
                    throw new Exception('Debe indicar un nombre para la udn');
                    
                $_T['maintitle']='Planificación de Operación - UDNs - Editar UDN';
                $q0=$db->query('SELECT * FROM estructura.udn WHERE LOWER(udn)=LOWER(\''.$db->escape($_POST['udn']).'\') AND id_udn<>'.$udn->id_udn);
                if($q0->numRows()!=0) throw new Exception('La UDN indicada ya existe');
                
                $udn->udn=$_POST['udn'];
                $udn->id_empresa=$_POST['id_empresa'];
                $_T['maincontent']='<h2 style="color: green;">UDN Editada satisfactoriamente</h2>
                <hr>
                <a href="?mod=parametros/udns/index">Regresar</a>';
            }catch(Exception $e) {
                $error=$e->getMessage();
                goto lbl_default;
            }
        break;
        
        default:
            lbl_default:
            $_T['maintitle']='Planificación de Operación - UDNs - Editar UDN';
            if($error!='') {
                $_T['maincontent'].='<span style="color: maroon; font-weight: bold;">'.$error.'</span>';
            }
            $_T['maincontent'].='
            <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'">
                <label for="udnname">Empresa</label>
                <select class="form-control" name="id_empresa">
                    <option value="">Seleccione...</option>
                    '.implode('',$option_empresas).'
                </select>
                <div class="form-group">
                    <label for="udnname">Nombre de la UDN</label>
                    <input type="text" class="form-control" name="udn" placeholder="Nombre de la UDN" value="'.$udn->udn.'">
                </div>
                <button class="btn btn-warning" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/udns/del','id'=>$udn->id_udn)).'\'">Eliminar UDN Completa</button>
                <br><br>
                <button type="submit" class="btn btn-primary">Guardar</button>
                <button class="btn btn-danger" type="button" onclick="window.location=\'?mod=parametros/udns/index\'">Cancelar</button> 
            </form>';
        break;
    }