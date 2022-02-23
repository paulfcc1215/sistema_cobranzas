<?php
Auth::enforcePrivileges('AUTH_PLANIFICACION*');
$_AM['udns']=AutoModel::getInstance('estructura','udn',Db::getInstance());
$_AM['empresa']=AutoModel::getInstance('estructura','empresa',Db::getInstance());
$udns=$_AM['udns']->getAll();

$option_empresas=array();
foreach($_AM['empresa']->getAll() as $v) {
    $option_empresas[]='<option value="'.$v->id_empresa.'">'.$v->id_empresa.' - '.$v->nombre.'</option>';
}

switch($_GET['step']) {
    case '2':
        try {
            if($_POST['id_empresa']=='')
                throw new Exception('Empresa inválida');
            if($_POST['udn']=='')
                throw new Exception('Debe indicar el nombre de la UDN');
            $_T['maintitle']='Planificación de Operación - UDNs - Nueva UDN';
			
            $q0=$db->query('SELECT * FROM estructura.udn WHERE LOWER(udn)=LOWER(\''.$db->escape($_POST['udn']).'\')');
            if($q0->numRows()!=0) throw new Exception('La UDN indicada ya existe');
            
			$db->startTransaction();
            $udn=$_AM['udns']->insert(array(
                'udn'=>$_POST['udn'],
                'id_empresa'=>$_POST['id_empresa']
            ),true);
			
			$st_name=udnGetStandardizedName($udn->id_udn);
			
			$db->commit();
            $_T['maincontent']='<h2 style="color: green;">UDN Creada satisfactoriamente</h2>
            <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array('step'),array('mod'=>'planificacion/udns/index')).'\'">Regresar</button>
            ';
        }catch(Exception $e) {
			if(!is_null($st_name) && $st_name!='') {
				foreach(explode(',',_UDN_FOLDER_TEMPLATE) as $f) {
					rmdir(_BASE_USER_PATH.'/udns/'.$st_name.'/'.$f);
				}
				rmdir(_BASE_USER_PATH.'/udns/'.$st_name);
			}
			
			$db->rollback();
            $error=$e->getMessage();
            goto lbl_default;
        }
    break;
    
    default:
        lbl_default:
        $_T['maintitle']='Planificación de Operación - UDNs - Nueva UDN';
        if($error!='') {
            $_T['maincontent'].='<span style="color: maroon; font-weight: bold;">'.$error.'</span>';
        }
        $_T['maincontent'].='
        <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'">
    <div class="form-group">
    <label for="udnname">Empresa</label>
    <select class="form-control" name="id_empresa">
    <option value="">Seleccione...</option>
    '.implode('',$option_empresas).'
    </select>
    </div>
    <div class="form-group">
    <label for="udnname">Nombre de la UDN</label>
    <input type="text" class="form-control" name="udn" placeholder="Nombre de la UDN">
    </div>
  <button type="submit" class="btn btn-primary">Guardar</button>
  <button class="btn btn-danger" type="button" onclick="window.location=\'?mod=planificacion/udns/index\'">Cancelar</button> 
        </form>
        ';
    break;
}