<?php

    if(!Auth::hasPrivileges('AUTH_PARAMETROS_CAMPANAS_INDEX')) throw new Exception('No autorizado - AUTH_PARAMETROS_CAMPANAS_INDEX');

    $_AM['campana']=AutoModel::getInstance('campanas','campana',Db::getInstance());
    $_AM['udn']=AutoModel::getInstance('estructura','udn',Db::getInstance());


    //get udns
    $udns = array();
    foreach ($_AM['udn']->getAll() as $udn){
        $udns[$udn->id_udn]=$udn->udn;
    }

    //get campañas
    $campanas=$_AM['campana']->getAll();

    $_T['maintitle']='Parámetros del sistema - Campañas';
    switch($_GET['step']) {
        default:

            $_T['maincontent'].= '
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/campanas/nueva_campana')).'\'">Crear Campaña</button><br><br>
                    <div class="card">
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Id</th><th>Campaña</th><th>Status</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>';
                                    foreach ($campanas as $row){
                                        $_T['maincontent'].= '<tr>
                                            <td>'.$row->id_campana.'</td>
                                            <td>'.$udns[$row->id_udn]. ' - ' .$row->campana.'</td>
                                            <td>'.($row->status=='1'?'ACTIVA':'INACTIVA').'</td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-xs" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/campanas/modificar_campana','id'=>$campana->id_campana)).'\'">Modificar</button>
                                            </td>
                                        </tr>';
                                    }
                                $_T['maincontent'].= '
                                </tbody>
                                <!--<tfoot>
                                    <tr>
                                        <th>Id</th><th>Campaña</th><th>Status</th><th></th>
                                    </tr>
                                </tfoot>-->
                            </table>
                        </div>
                    </div>
                </div>
            </div>';

        break;
    }


    $_T['bottom_jscript'] .= '
    $("#example1").DataTable({
        "responsive": true, 
        "lengthChange": false, 
        "autoWidth": false,
        "buttons": [
            "copy", 
            "csv", 
            "excel", 
            "pdf", 
            "print", 
            "colvis"
        ]
    }).buttons().container().appendTo(\'#example1_wrapper .col-md-6:eq(0)\');
    ';