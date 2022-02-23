<?php

    if(!Auth::hasPrivileges('AUTH_PARAMETROS_EMPRESAS_INDEX')) throw new Exception('No autorizado - AUTH_PARAMETROS_EMPRESAS_INDEX');

    $_AM['empresa']=AutoModel::getInstance('estructura','empresa',Db::getInstance());
    $empresas=$_AM['empresa']->getAll('id_empresa ASC');

    $_T['maintitle']='Parámetros del sistema - Empresas';

    switch($_GET['step']) {
        default:

            $_T['maincontent'].= '
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/empresas/nueva_empresa')).'\'">Crear Empresa</button><br><br>
                    <div class="card">
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Id Empresa</th><th>Nombre Empresa</th><th>Status</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>';
                                    foreach ($empresas as $row){
                                        $_T['maincontent'].= '<tr>
                                            <td>'.$row->id_empresa.'</td>
                                            <td>'.$row->nombre.'</td>
                                            <td>'.($row->status=='1'?'ACTIVA':'INACTIVA').'</td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-xs" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/empresas/modificar_empresa','id'=>$row->id_empresa)).'\'">Modificar</button>
                                            </td>
                                        </tr>';
                                    }
                                $_T['maincontent'].= '
                                </tbody>
                                <!--<tfoot>
                                    <tr>
                                        <th>Id Empresa</th><th>Nombre Empresa</th><th>Status</th>
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