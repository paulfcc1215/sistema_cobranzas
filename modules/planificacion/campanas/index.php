<?php
Auth::enforcePrivileges('AUTH_PLANIFICACION*');
$_AM['campana']=AutoModel::getInstance('campanas','campana',Db::getInstance());
$campanas=$_AM['campana']->getAll();

switch($_GET['step']) {
    default:
        $_T['maintitle']='Planificación de Operación - Campañas';
        $_T['maincontent']='
        <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'planificacion/campanas/new')).'\'">Agregar</button>
        <br><br>
        <table class="table table-striped">
        <tr>
        <th>Id Udn</th><th>Nombre Campana</th><th>Status</th></tr>
        ';
        foreach($campanas as $campana) {
            $_T['maincontent'].='<tr class="clickable" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'planificacion/campanas/edit','id'=>$campana->id_campana)).'\'">';
            $_T['maincontent'].='<td>'.$campana->id_campana.'</td>';
            $_T['maincontent'].='<td>'.$campana->campana.'</td>';
            $_T['maincontent'].='<td>'.($campana->status=='1'?'ACTIVA':'INACTIVA').'</td>';
            $_T['maincontent'].='</tr>';
        }
        $_T['maincontent'].='
        </table>
        ';
    break;
}