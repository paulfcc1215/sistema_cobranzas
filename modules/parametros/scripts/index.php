<?php

	if(!Auth::hasPrivileges('AUTH_PARAMETROS_SCRIPTS_VER')) throw new Exception('No autorizado - AUTH_PARAMETROS_SCRIPTS_VER');

	$db=DB::getInstance();

	$_T['maintitle'].='Parámetros del sistema - Scripts';
	$_T['maincontent'].='';

	switch ($_GET['step']) {
		
		default:

			$query = 'SELECT 
				u.id_udn,u.udn,c.id_campana,c.campana,p.id_proceso,p.descripcion as proceso,s.id_script,s.descripcion as descripcion_script,s.fecha_creacion,s.status 
			FROM "campanas"."scripts" s
				JOIN "campanas"."proceso" p USING(id_proceso) 
				JOIN campanas.campana c USING (id_campana) 
				JOIN estructura.udn u USING (id_udn)
			WHERE s.status=\'1\'';
			$scripts = $db->query($query)->fetchAll();

			$_T['maincontent'].= '
            <div class="row">
                <div class="col-12">
                    <button class="btn btn-primary" type="button" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/scripts/nuevo_script')).'\'">Crear Script</button><br><br>
                    <div class="card">
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
										<th>Id Script</th><th>Campaña - Proceso</th><th>Script</th><th>Fecha Creación</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>';
                                    foreach ($scripts as $row){
                                        $_T['maincontent'].= '<tr>
                                            <td>'.$row['id_script'].'</td>
                                            <td>'.$row['udn'].' / '.$row['campana']. ' / ' .$row['proceso'].'</td>
											<td>'.$row['descripcion_script'].'</td>
											<td>'.$row['fecha_creacion'].'</td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-xs" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/scripts/modificar_script','id'=>$row['id_script'])).'\'">Modificar</button>
                                            </td>
                                        </tr>';
                                    }
                                $_T['maincontent'].= '
                                </tbody>
                                <!--<tfoot>
                                    <tr>
										<th>Id Script</th><th>Campaña - Proceso</th><th>Script</th><th>Fecha Creación</th><th></th>
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