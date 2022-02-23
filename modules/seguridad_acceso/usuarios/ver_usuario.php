<?php

	//get session
	$SM=SessionManager::getInstance();
	if(Auth::isSuper($SM->user['usr_logname'])){
		$grupos=Modelo_Grupos::getAll();
	}else{
		$grupos=Modelo_Grupos::getGruposPorUsuario($SM->user['id_usuario']);
	}

	//get perfiles
	foreach ($grupos as $key => $value) {
		$combo_grupos[$key]=$key.' - '.$value->descripcion;
	}
	//get perfil del usuairo
	$grupos_usuario = Modelo_Grupos::getGruposPorUsuario($_GET['usr_logname']);
	$usuario = new Modelo_Usuarios($_GET['id_usuario']);
	$combo_grupos = array();
	$combo_grupos_asignados = array();
	foreach ($grupos_usuario as $key => $value) {
		$combo_grupos_asignados[$key]=$key.' - '.$value->descripcion;
	}
	foreach ($grupos as $key => $value) {
		$combo_grupos[$key]=$key.' - '.$value->descripcion;
	}
	$combo_grupos = array_diff($combo_grupos,$combo_grupos_asignados);

	$_T['maintitle']='Administrar Usuarios - Actualizar Usuario';

	try {
		if (!$_POST['save']=='1') throw new Exception('');
		if (trim($_POST['identificacion'])=='') throw new Exception('Ingrese identificación del usuario');
		if (trim($_POST['nombre_completo'])=='') throw new Exception('Ingrese nombre completo de usuario');
		if (!preg_match("|^[a-zñA-ZÑ]+(\s*[a-zñA-ZÑ]*)*[a-zñA-ZÑ]+$|",trim($_POST['nombre_completo']))) throw new Exception('El nombre del usuario debe contener solo caracteres alfabéticos entre [A-Z]');
		if ($_POST['usr_logname']=='') throw new Exception('Ingrese usuario');
		if ($_POST['pass']=='') throw new Exception('Ingrese contraseña');
		$msg='';
		$cumple_clave=Auth::claveCumpleRequisitos($_POST['pass'],$msg);
		if(!$cumple_clave && false){
			throw new Exception($msg);
		}
		if (trim($_POST['grupos_seleccionados'])=='') throw new Exception('Seleccione por lo menos un grupo de usuario');
		if ($_POST['status']=='0'){
			//validar que NO ME AUTOELIMINE
			if ($usuario->usr_logname==Auth::getUsername()){
				throw new Exception('No se puede inactivar, el usuario está logueado en ésta sesión');
			}
			//validar que no se elimine el super_usuario
			if (Auth::isSuper($usuario->usr_logname)){
				throw new Exception('No se puede deshabilitar un Superusuario');
			}
			if (trim($_POST['razon_deshabilitado'])=='') {
				throw new Exception('Si deshabilita el usuario ingrese un motivo');
			}
		}else{
			$_POST['razon_deshabilitado']='';
			$usuario->razon_deshabilitado=trim($_POST['razon_deshabilitado']);
		}
		$grupos_seleccionados = explode(',',$_POST['grupos_seleccionados']);
		if (Modelo_Grupos::deletePorUsuario($_GET['usr_logname'])){
			if (Modelo_Grupos::createGruposUsuario($_GET['usr_logname'],$grupos_seleccionados)!=true){
				die('Error al asignar grupos al usuario');
			}
		}else{
			die('Error al asignar grupos al usuario');
		}

		if ($_POST['status']=='0'){
			//VERIFICAR QUE NO EXISTAN GRUPOS ASOCIADOS
			$grupos_usuario = Modelo_Grupos::getGruposPorUsuario($_GET['usr_logname']);
			$aux_err = array();
			if (!empty($grupos_usuario)){
				if (modelo_Grupos::deletePorUsuario($_GET['usr_logname'])!=true){
					die('Error al asignar grupos al usuario');
				}
			}
			$usuario->fecha_deshabilitado = date('Y-m-d H:i:s');
			$usuario->deshabilitado_por = Auth::getUsername();
			$usuario->razon_deshabilitado = trim($_POST['razon_deshabilitado']);
		}
		$usuario->identificacion = trim($_POST['identificacion']);
		$usuario->usr_logname = trim($_POST['usr_logname']);
		$usuario->nombre_completo = trim($_POST['nombre_completo']);
		$usuario->pass = trim($_POST['pass']);
		$usuario->status = trim($_POST['status']);
		$usuario->force_pw_change = trim($_POST['force_pw_change']);

		$_POST['pass']='***';
		Log::addLog('USUARIOS_EDITAR',__FILE__,array_merge($_POST,array('id_usuario'=>$_GET['id_usuario'])));
		$_T['maincontent'].='
			<div class="alert alert-success" role="alert">
				Se ha actualizado el Usuario "<b>'.$usuario->usr_logname.'</b>" satisfactoriamente!
			</div>
		';

	}catch(Exception $e) {
		Log::addLog('USUARIOS_VER',__FILE__,array('id_usuario'=>$_GET['id_usuario']));
		$_T['maincontent'].='
			Por favor indique información para la creación del Usuario.<br>
			<br><br>
			<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>2)).'">
				<input type="hidden" name="save" value="1">
		';
		if($e->getMessage()!='') $_T['maincontent'].='<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';

		$status_selected='1';
		if(!is_null($_POST['status'])){
			if($_POST['status']=='0') $status_selected='0';
		}else{
			if($usuario->status=='0') $status_selected='0';
		}
		$force_pw_change_selected='1';
		if(!is_null($_POST['force_pw_change'])){
			if($_POST['force_pw_change']=='0') $force_pw_change_selected='0';
		}else{
			if($usuario->force_pw_change=='0') $force_pw_change_selected='0';
		}
		//print_arr($combo_grupos_asignados);die;
		$_T['maincontent'].='
			<style>
				.table_form{
					width:70%;
				}
				.table_form td{
					border: 1px solid #A7D5E7;
					padding: 10px;
				}
				</style>
				<table class="table_form">
					<tr>
						<td colspan="2">
							<label>ID USUARIO:</label><br>
							<input type="text" class="form-control" readonly value="'.($usuario->id_usuario).'" >
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>IDENTIFICACION:</label><br>
							<input type="text" placeholder="Ingrese código único de identificación" class="form-control" maxlength="18" name="identificacion" value="'.(($_POST['identificacion']=='')?$usuario->identificacion:$_POST['identificacion']).'" >
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label>NOMBRE COMPLETO:</label><br>
							<input type="text" placeholder="Ingrese nombre completo del usuario" class="form-control" maxlength="100" name="nombre_completo" value="'.(($_POST['nombre_completo']=='')?$usuario->nombre_completo:$_POST['nombre_completo']).'" >
						</td>
					</tr>
					<tr>
						<td>
							<label>LOGIN:</label><br>
							<input type="text" placeholder="Ingrese usuario" class="form-control" name="usr_logname" value="'.(($_POST['usr_logname']=='')?$usuario->usr_logname:$_POST['usr_logname']).'">
						</td>
						<td>
							<label>CONTRASEÑA:</label>
							<input type="password" placeholder="Ingrese contraseña" class="form-control" name="pass" value="'.(($_POST['pass']=='')?$usuario->pass:$_POST['pass']).'">
						</td>
					</tr>
					<tr>
						<td>
							<label>FECHA CREADO:</label>
							<input type="text" class="form-control" name="fecha_agregado" readonly value="'.$usuario->fecha_agregado.'">
						</td>
						<td>
							<label>CREADO POR:</label>
							<input type="text" class="form-control" name="agregado_por" readonly value="'.$usuario->agregado_por.'">
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<div class="form-group" >
							<label>ASIGNACIÓN DE GRUPOS</label>
							<table style="width:100%;">
								<tr>
									<td style="width: 47%;">
										<select id="cmb_grupos" class="form-control" style="height:200px;" multiple>
											'.UI_Helper::array_to_options($combo_grupos,null,false).'
										</select>
									</td>
									<td style="width: 6%" align="center">
										<input type="button" name="btnAsignarGrupo" value="&gt;&gt;" onclick="agregarGrupo()"><br><br>
										<input type="button" name="btnQuitarGrupo" value="&lt;&lt;" onclick="quitarGrupo()">
									</td>
									<td style="width: 47%;">
										<input type="hidden" name="grupos_seleccionados" id="id_grupos_seleccionados" value="">
										<select id="id_grupo_usuario" class="form-control" style="height:200px;" multiple>
											'.UI_Helper::array_to_options($combo_grupos_asignados,null,false).'
										</select>
									</td>
								</tr>
							</table>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<label>EXIGIR CAMBIO DE CLAVE:</label>
							<select name="force_pw_change" class="form-control">
								<option value="1" '.($force_pw_change_selected=='1'?"selected":"").'>SI</option>
								<option value="0" '.($force_pw_change_selected=='0'?"selected":"").'>NO</option>
							</select>
						</td>
						<td>
							<label>ESTADO:</label>
							<select name="status" id="id_status" class="form-control" onchange="on_change_status();">
								<option value="1" '.($status_selected=="1"?"selected":"").'>Activo</option>
								<option value="0" '.($status_selected=="0"?"selected":"").'>Inactivo</option>
							</select><br>
							<div id="div_motivo_deshabilitado" >
								<label>MOTIVO DESHABILITADO:</label>
								<input type="text" class="form-control" name="razon_deshabilitado" id="id_razon_deshabilitado" value="'.$usuario->razon_deshabilitado.'">
							</div>
						</td>
					</tr>
				</table>
				<br>
				<button class="btn btn-primary">Guardar</button>
			</form>';

			$_T['bottom_jscript'].='

				$("document").ready(function(){
					var grupos_asignados = '.json_encode(implode(",", array_keys($combo_grupos_asignados))).';
					$("#id_grupos_seleccionados").val(grupos_asignados);
				});

				$("#div_motivo_deshabilitado").hide();
				function agregarGrupo(){
					var aa = new Array();
					$("#id_grupos_seleccionados").val("");
					var grupos_seleccionados=cmb_grupos.selectedOptions;
					var c = grupos_seleccionados.length;
					var x = document.getElementById("id_grupo_usuario");
					for (i = 0; i < c; i++) {
						x.add(grupos_seleccionados[0]);
					}
					var grupos_agregados=id_grupo_usuario.options;
					for(i=0; i<grupos_agregados.length;i++){
						aa.push(grupos_agregados[i].value);
					}
					document.getElementById("id_grupos_seleccionados").value=aa.join(\',\');
				}
				function quitarGrupo(){
					var aa = new Array();
					$("#id_grupos_seleccionados").val("");
					var grupos_seleccionados=id_grupo_usuario.selectedOptions;
					var x = document.getElementById("cmb_grupos");
					var c = grupos_seleccionados.length;
					for (i = 0; i < c; i++) {
						x.add(grupos_seleccionados[0]);
					}
					var grupos_agregados=id_grupo_usuario.options;
					for(i=0; i<grupos_agregados.length;i++){
						aa.push(grupos_agregados[i].value);
					}
					document.getElementById("id_grupos_seleccionados").value=aa.join(\',\');
				}
				function on_change_status(){
					if($("#id_status").val()=="0")
						$("#div_motivo_deshabilitado").show();
					else
						$("#div_motivo_deshabilitado").hide();
				}
			';

	}