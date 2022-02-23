<?php
Auth::enforcePrivileges('AUTH_PLANIFICACION*');
$_AM['empresa']=AutoModel::getInstance('estructura','empresa',Db::getInstance());
$empresas=$_AM['empresa']->getAll('id_empresa ASC');

switch($_GET['step']) {
    default:
        $_T['maintitle']='Planificación de Operación - Empresas';
        $_T['maincontent']='
        <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'planificacion/empresas/new')).'\'">Agregar</button>
        <br><br>
        <table class="table table-striped">
        <tr>
        <th>Id Empresa</th><th>Nombre Empresa</th><th>Status</th></tr>
        ';
        foreach($empresas as $empresa) {
            $_T['maincontent'].='<tr class="clickable" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'planificacion/empresas/edit','id'=>$empresa->id_empresa)).'\'">';
            $_T['maincontent'].='<td>'.$empresa->id_empresa.'</td>';
            $_T['maincontent'].='<td>'.$empresa->nombre.'</td>';
            $_T['maincontent'].='<td>'.($empresa->status=='1'?'ACTIVA':'INACTIVA').'</td>';
            $_T['maincontent'].='</tr>';
        }
        $_T['maincontent'].='
        </table>
        ';
    break;
}