<?php

	if(!Auth::hasPrivileges('AUTH_SEGURIDAD_PERFILES_INDEX')) throw new Exception('No autorizado - AUTH_SEGURIDAD_PERFILES_INDEX');

	$grupos = Modelo_Grupos::getAll(true);
	//GET USUARIOS
	$usuarios = Modelo_Usuarios::getAll();

	$_T['maintitle']='<div class="titulo1"><p>Seguridad y acceso - Perfiles</p></div>';
	switch($_GET['step']) {
		
		default:

			$_T['maincontent'].= '
			<div class="row">
				<div class="col-12">
					<button class="btn btn-primary" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'seguridad_acceso/perfiles/nuevo_perfil')).'\'">Crear Perfil</button><br><br>
					<div class="card">
						<div class="card-body">
							<table id="example1" class="table table-bordered table-striped">
								<thead>
									<tr>
										<th>Descripción</th><th>Fecha Cración</th><th>Creado Por:</th><th>Estado</th><th></th>
									</tr>
								</thead>
								<tbody>';
									foreach ($grupos as $id => $row){
										$_T['maincontent'].= '<tr>
											<td>'.$id.' - '.$row->descripcion.'</td>
											<td>'.$row->fecha_agregado.'</td>
											<td>'.$usuarios[$row->creado_por]->usr_logname.'</td>
											<td>'.($row->status==1?'Activo':'Inactivo').'</td>
											<td>
												<button type="button" class="btn btn-success btn-xs" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array('mod'),array('mod'=>'seguridad_acceso/perfiles/modificar_perfil','id_grupo'=>$id)).'\'">Modificar</button>
											</td>
										</tr>';
									}
								$_T['maincontent'].= '
								</tbody>
								<!--<tfoot>
									<tr>
										<th>Descripción</th><th>Privilegio</th><th>Fecha Creación</th><th>Estado</th><th></th>
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