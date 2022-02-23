<?php

	if(!Auth::hasPrivileges('AUTH_PARAMETROS_SCRIPTS_CREAR')) throw new Exception('No autorizado - AUTH_PARAMETROS_SCRIPTS_CREAR');

	$_T['maintitle']='Parámetros del sistema - Scripts - Crear Script';

	$db=DB::getInstance();
	try {

		if(!$_POST['save']=='1') throw new Exception('');
		if(trim($_POST['proceso'])=='') throw new Exception('Seleccione Proceso');
		if(trim($_POST['descripcion'])=='') throw new Exception('Ingrese descripción');
		if(trim($_POST['contenido'])=='') throw new Exception('Ingrese Script');

		$db->query('INSERT INTO campanas.scripts(id_proceso,descripcion,script)VALUES('.$_POST['proceso'].',\''.$_POST['descripcion'].'\',\''.$_POST['contenido'].'\')');

		$_T['maincontent'].='<div class="alert alert-success" role="alert">Script registrado correctamente!</div>
			<hr>
			<a href="?mod=parametros/scripts/index">Regresar a Scripts</a>';

	}catch(Exception $e) {

		//get udns
		$udns=array();
		foreach ($db->query('SELECT * FROM estructura.udn WHERE status=\'1\'')->fetchAll() as $udn) {
			$udns[$udn['id_udn']]=$udn['id_udn'].' - '.$udn['udn'];
		}
		//get campanas
		$campanas=array();
		foreach ($db->query('SELECT * FROM campanas.campana WHERE status=\'1\'')->fetchAll() as $campana) {
			$campanas[$campana['id_udn']][$campana['id_campana']]=$campana['id_campana'].' - '.$campana['campana'];
		}
		//get procesos
		$procesos=array();
		foreach ($db->query('SELECT * FROM campanas.proceso WHERE status=\'1\'')->fetchAll() as $proceso) {
			$procesos[$proceso['id_campana']][$proceso['id_proceso']]=$proceso['id_proceso'].' - '.$proceso['descripcion'];
		}

		
		$_T['maincontent'].='
		<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>2)).'">
			<input type="hidden" name="save" value="1">';
			if($e->getMessage()!='') $_T['maincontent'].='<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';

		$_T['maincontent'].='
			<style>
				.table_form{
					width:100%;
				}
				.table_form td{
					border: 1px solid #A7D5E7;
					padding: 10px;
				}
			</style>
			<table>
				<tr>
					<td>
						<table class="table_form">
							<tr>
								<td>
									<label>UDN:</label>
									<select id="udn" class="form-control" onchange="change_udn()">'.UI_Helper::array_to_options($udns,$_POST['udn'],true).'</select>
								</td>
								<td>
									<label>Campaña:</label>
									<select id="campana" class="form-control" onchange="change_campana()">
										<option value="">Seleccione...</option>
									</select>
								</td>
								<td>
									<label>Proceso:</label>
									<select name="proceso" id="proceso" class="form-control" onchange="change_proceso()">
										<option value="">Seleccione...</option>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan="3">
									<label>Descripción de Script:</label>
									<input name="descripcion" value="'.$_POST['descripcion'].'" placeholder="Descripción" class="form-control"></input>
								</td>
							</tr>
							<tr>
								<td colspan="3">
									<label>Script:</label>
									<textarea class="ckeditor" name="contenido">'.($_POST['contenido']==''?$qa0['contenido']:$_POST['contenido']).'</textarea><br>
								</td>
							</tr>
						</table>
					</td>
					<td width="20px"></td>
					<td>
						<div id="campos"></div>
					</td>
				</tr>
			</table>
			
			<br>
			<button class="btn btn-primary">Guardar</button>
		</form>
		
		<script>
			var campanas = '.json_encode($campanas).';
			var procesos = '.json_encode($procesos).';

			function change_udn(){
				$("#campana").empty();
				$("#campana").append("<option value=\"\">Seleccione...</option>");
				$("#proceso").empty();
				$("#proceso").append("<option value=\"\">Seleccione...</option>");
				if ($("#udn").val()!=""){
					$.each(campanas[$("#udn").val()],function(i,o){
						$("#campana").append("<option value=\""+i+"\">"+o+"</option>");
					})
				}
			}

			function change_campana(){
				$("#proceso").empty();
				$("#proceso").append("<option value=\"\">Seleccione...</option>");
				if ($("#campana").val()!=""){
					$.each(procesos[$("#campana").val()],function(i,o){
						$("#proceso").append("<option value=\""+i+"\">"+o+"</option>");
					})
				}
			}

			function change_proceso(){
				$("#campos").empty();
				$.ajax({
					"url":"?mod=parametros/scripts/ajax",
					"method":"POST",
					"data":{
						"id_proceso": $("#proceso").val()
					},
					"success":function(d) {
						try {
							d=$.parseJSON(d);
							console.log(d);
							if(!d) throw "Error en la respuesta del servidor";
							$("#campos").append("<h3>Campos utilizables</h3>");
							$.each(d,function(i,o){
								$("#campos").append("[[%"+o+"%]]");
								$("#campos").append("<br>");
							})
						}catch(err) {
						}
					}
				});
			}

		</script>';
	}