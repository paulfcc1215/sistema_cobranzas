<?php
	
	if(!Auth::hasPrivileges('AUTH_SEGURIDAD_USUARIOS_INDEX')) throw new Exception('No autorizado - AUTH_SEGURIDAD_USUARIOS_INDEX');

	$usuarios = Modelo_Usuarios::getAll(true);

	$_T['maintitle']='Seguridad y acceso - Usuarios';
	switch($_GET['step']) {
		default:

			$_T['maincontent'].= '
			<div class="row">
				<div class="col-12">
					<button class="btn btn-primary" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'seguridad_acceso/usuarios/nuevo_usuario')).'\'">Crear Usuario</button><br><br>
					<div class="card">
						<div class="card-body">
							<table id="example1" class="table table-bordered table-striped">
								<thead>
									<tr>
										<th>Identificación</th><th>Nombres</th><th>Usuario</th><th>Fecha Creación</th><th>Creado Por</th><th>Perfil</th><th></th>
									</tr>
								</thead>
								<tbody>';
									foreach ($usuarios as $id => $row){
										if ($row->status!=1) continue;
										$perfiles = array();
										foreach (Modelo_Grupos::getGruposPorUsuario($row->usr_logname) as $objGrupo){
											if ($objGrupo->status=='1'){
												$perfiles[]=$objGrupo->descripcion;
											}
										}
										$_T['maincontent'].= '<tr>
											<td>'.$row->identificacion.'</td>
											<td>'.$row->nombre_completo.'</td>
											<td>'.$row->usr_logname.'</td>
											<td>'.$row->fecha_agregado.'</td>
											<td>'.$row->agregado_por.'</td>
											<td>'.implode(',<br>',$perfiles).'</td>
											<td>
												<button type="button" class="btn btn-success btn-xs" onclick="window.location=\'?'.Helpers::arr_to_url($_GET,array('mod'),array('mod'=>'seguridad_acceso/usuarios/modificar_usuario','id_usuario'=>$id)).'\'">Modificar</button>
											</td>
										</tr>';
									}
								$_T['maincontent'].= '
								</tbody>
								<!--<tfoot>
									<tr>
										<th>Identificación</th><th>Nombres</th><th>Usuario</th><th>Fecha Creación</th><th>Creado Por</th>
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