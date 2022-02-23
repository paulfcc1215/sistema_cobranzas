<?php
    if(!Auth::hasPrivileges('AUTH_PARAMETROS_UDNS_INDEX')) throw new Exception('No autorizado - AUTH_PARAMETROS_UDNS_INDEX');

    $_T['maintitle']='Parámetros del sistema - UDNs';

    $_AM['udns']=AutoModel::getInstance('estructura','udn',Db::getInstance());
    $_AM['empresa']=AutoModel::getInstance('estructura','empresa',Db::getInstance());
    $udns=$_AM['udns']->getAll();


    switch($_GET['step']) {
        default:
            $_T['maincontent'].='
            <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/udns/nueva_udn')).'\'">Crear UDN</button>
            <br><br>
            <table class="table table-striped">
            <tr>
            <th>Id Udn</th><th>Empresa</th><th>Nombre UDN</th><th>Status</th></tr>
            ';
            foreach($udns as $udn) {
                $empresa=$_AM['empresa']->getById($udn->id_empresa);
                $_T['maincontent'].='<tr class="clickable" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/udns/modificar_udn','id'=>$udn->id_udn)).'\'">';
                $_T['maincontent'].='<td>'.$udn->id_udn.'</td>';
                $_T['maincontent'].='<td>'.$empresa->nombre.'</td>';
                $_T['maincontent'].='<td>'.$udn->udn.'</td>';
                $_T['maincontent'].='<td>'.($udn->status=='1'?'ACTIVA':'INACTIVA').'</td>';
                $_T['maincontent'].='</tr>';
            }
            $_T['maincontent'].='
            </table>';
        break;
    }