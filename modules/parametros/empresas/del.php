<?php
Auth::enforcePrivileges('AUTH_PLANIFICACION*');
$_AM['empresa']=AutoModel::getInstance('estructura','empresa',Db::getInstance());
$empresa=$_AM['empresa']->getById($_GET['id']);
if(!$empresa) throw new Exception('Empresa "'.$_GET['id'].'" inválida');

switch($_GET['step']) {
    case '2':
        try {
            $_T['maintitle']='Planificación de Operación - Empresas - Eliminar Empresa';
            
            $empresa->delete();
            
            $_T['maincontent']='<h2 style="color: green;">Empresa Eliminada satisfactoriamente</h2>
            <hr>
            <a href="?mod=parametros/empresas/index">Regresar</a>';
        }catch(Exception $e) {
            $error=$e->getMessage();
            goto lbl_default;
        }
    break;
    
    default:
        lbl_default:
        $_T['maintitle']='Planificación de Operación - Empresas - Eliminar Empresa';
        if($error!='') {
            $_T['maincontent'].='<span style="color: maroon; font-weight: bold;">'.$error.'</span>';
        }
        $_T['maincontent'].='
        <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'" style="background-color: black; padding: 20px; font-size: 24px; color: yellow; border-radius: 10px; text-align: center;">
        Atención:
        <br>
        Desea eliminar la EMPRESA "'.$empresa->nombre.'" con id "'.$empresa->id_empresa.'"
        <br><br>
        Esta operación es irreversible y eliminará todas las configuraciones, cargas, gestiones, registros y datos asociadas a la misma.
        <br><br>
        Está seguro que desea ejecutar la operación?
        <br><br>
        <button class="btn btn-danger" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'\'">Eliminar</button>
        <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/empresas/edit','id'=>$empresa->id_empresa)).'\'">Cancelar</button>
        </form>
        ';
    break;
}