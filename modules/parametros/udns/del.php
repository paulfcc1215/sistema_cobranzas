<?php
Auth::enforcePrivileges('AUTH_PLANIFICACION*');
$_AM['udns']=AutoModel::getInstance('estructura','udn',Db::getInstance());
$udn=$_AM['udns']->getById($_GET['id']);
if(!$udn) throw new Exception('UDN "'.$_GET['id'].'" inválida');

switch($_GET['step']) {
    case '2':
        try {
            $_T['maintitle']='Planificación de Operación - UDNs - Eliminar UDN';
            
            $udn->delete();
            
            $_T['maincontent']='<h2 style="color: green;">UDN Eliminada satisfactoriamente</h2>
            <hr>
            <a href="?mod=parametros/udns/index">Regresar</a>';
        }catch(Exception $e) {
            $error=$e->getMessage();
            goto lbl_default;
        }
    break;
    
    default:
        lbl_default:
        $_T['maintitle']='Planificación de Operación - UDNs - Eliminar UDN';
        if($error!='') {
            $_T['maincontent'].='<span style="color: maroon; font-weight: bold;">'.$error.'</span>';
        }
        $_T['maincontent'].='
        <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'" style="background-color: black; padding: 20px; font-size: 24px; color: yellow; border-radius: 10px; text-align: center;">
            Atención:
            <br>
            Desea eliminar la UDN "'.$udn->udn.'" con id "'.$udn->id_udn.'"
            <br><br>
            Esta operación es irreversible y eliminará todas las configuraciones, cargas, gestiones y registros asociadas a la misma.
            <br><br>
            Está seguro que desea ejecutar la operación?
            <br><br>
            <button class="btn btn-danger" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'\'">Eliminar</button>
            <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/udns/edit','id'=>$udn->id_udn)).'\'">Cancelar</button>
        </form>
        ';
    break;
}