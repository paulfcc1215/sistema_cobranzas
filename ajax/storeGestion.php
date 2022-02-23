<?php
try {

	if (!preg_match('#^\d+$#',$_request['tipificacion'])) throw new Exception('La tipificación no es válida');
	$tipificacion = getTipificacion($_request['tipificacion']);

	// print_arr($tipificacion['_metadata']);
	// print_arr($_request);

	if ($tipificacion['_metadata']['es_email']){
		if ($_request['email']=='') throw new Exception('El correo no es válido');
	}else{
		if (!preg_match('#^09\d{8}$#',$_request['telefono']) && !preg_match('#^0[2-8]\d{7}$#',$_request['telefono'])){
			throw new Exception('El teléfono no es válido');
		}
	}

	if (empty($_request['id_cuentas'])){
		throw new Exception('Debe seleccionar al menos una cuenta en la que guardar la gestión');
	}

	//if($_request['id_cuentas'][0] == "3021601"){
		//throw new Exception(json_encode($tipificacion['_metadata'])); die();
	//}

	if ($tipificacion['_metadata']['es_promesa']) {
		if (!preg_match('#^\d{2}/\d{2}/\d{4}$#',$_request['fecha_promesa'])) throw new Exception('La fecha de promesa de pago es inválida (debe tener formato dia/mes/año)');
		$_request['fecha_promesa']=explode('/',$_request['fecha_promesa']);
		if (!checkdate($_request['fecha_promesa'][1],$_request['fecha_promesa'][0],$_request['fecha_promesa'][2])) throw new Exception('La fecha de promesa de pago es inválida ');
		$_request['fecha_promesa'] = implode('-',array($_request['fecha_promesa'][2],$_request['fecha_promesa'][1],$_request['fecha_promesa'][0]));
		
		if (!preg_match('#^\d+(\.\d{2})$#',$_request['monto_promesa'])) throw new Exception('El monto de la promesa no es válido. Debe ser de la forma entero.decimales');
		
	}else{
		// validation by emartinez. si es email debe permitir fecha de promesa
		if (!$tipificacion['_metadata']['es_email']){
			if ($_request['fecha_promesa']!='') throw new Exception('Si no hay promesa de pago, no puede indicar fecha de promesa');
			if ($_request['monto_promesa']!='') throw new Exception('Si no hay promesa de pago, no puede indicar monto de promesa');
		}else{
			if ($_request['monto_promesa']=='') $_request['monto_promesa']='0.0';
		}
	}

	// print_arr('paso validación');
	/**
    Se elimina la validacion de telh_id
    Mantis 0054275
    **/
    
	/*
    if(!preg_match('#^\d+$#',$_request['telh_id']) && $_request['tipificacion']!=17)
		throw new Exception('Id de llamada inválido (Debe ser solo números)');
    */
	
	$fecha_inicio=$_request['fecha_inicio'];
	$fecha_fin=date('Y-m-d H:i:s');
	$telh_id=$_request['telh_id'];
	$user_name=$_request['user_name'];
	$servidor=$_request['servidor'];
	
	// listos para guardar
	$first=true;
	$id_gestion_ref='null';
    
    // validamos si los procesos para las cuentas indicadas estan activos
    foreach($_request['id_cuentas'] as $id_cuenta) {
        $qa0=$db->query('SELECT "status" FROM campanas.proceso WHERE id_proceso=(SELECT id_proceso FROM cuentas.cuenta WHERE id_cuenta=\''.$db->escape($id_cuenta).'\')')->current();
        if($qa0['status']!='1')
            throw new Exception('Una o más de las cuentas seleccionadas se encuentra asignado a un proceso inactivo');
    }
	
	foreach($_request as $k=>$v) {
		if(!preg_match('#^custom_(.*?)$#',$k,$matches))
			continue;
		$custom_fields[$matches[1]]=$v;
	}
	
	
    $db->startTransaction();
	foreach($_request['id_cuentas'] as $id_cuenta) {
		// execute hook for custom fields
		// storeGestion_custom_fields_validations
		$cuenta=getCuenta($id_cuenta);
		$proceso = getProceso($cuenta['id_proceso']);
		$id_campana = $proceso['id_campana'];
		
		// fetch hooks for the campaign
		foreach($db->query('SELECT * FROM hooks.hooks WHERE id_campana='.$id_campana.' AND hook_type=\'storeGestion_custom_fields_validations\' AND "enabled"=\'1\' ORDER BY "order" ASC') as $hook) {
			//$hook['code']($_request,$custom_fields);
			eval($hook['code']);
		}
		
		$query='SELECT gestiones.new_gestion(
			-- id_cuenta
			'.$id_cuenta.',
			-- fecha_inicio
			\''.$fecha_inicio.'\',
			-- telh_id
			\''.$telh_id.'\',
			-- user_name
			\''.$user_name.'\',
			-- tel_number
			\''.$_request['telefono'].'\',
			-- id_tipificacion
			'.$_request['tipificacion'].',
			-- fecha_fin
			\''.$fecha_fin.'\',
			-- servidor
			\''.$servidor.'\',
			-- observacion
			\''.$db->escape($_request['observaciones']).'\',
			-- fecha_compromiso
			'.((($_request['fecha_promesa']!=''))?'\''.$_request['fecha_promesa'].'\'':'null').',
			-- monto_compromiso
			'.((($_request['fecha_promesa']!=''))?'\''.$_request['monto_promesa'].'\'':'null').',
			-- ip_cliente
			\''.$_SERVER['REMOTE_ADDR'].'\',
			-- id_gestion_ref
			'.$id_gestion_ref.',
			-- email
			\''.$_request['email'].'\'
			) AS id_gestion
		';
		
		$q0=$db->query($query);
		$idGestion = $q0->current()['id_gestion'];
		if($first) {
			$id_gestion_ref='\''.$q0->current()['id_gestion'].'\'';
			
			// alimentamos SIPLAM
			$persona=getPersona($cuenta['id_deudor']);
			$datos_gestion = array(
				substr($persona['identificacion'],0,10),
				$_request['telefono'],
				$tipificacion['_metadata']['tipificacion_siplam'],
				_SIPLAM_TOKEN,
				$cuenta['id_proceso'],
				'',
				'',
				$tipificacion['descripcion'],
				$tipificacion['descripcion'],
				
				$q0->current()['id_gestion'],
				$fecha_inicio,
				
				$user_name,
				$tipificacion['_metadata']['siplam_regestionable'],
			);
			//$spws=new SiPlaMWS('http://192.168.180.216:8080/ws_siplam/WSSiplam?wsdl');
			//$result = $spws->sendRequest('crearGestion', array('datos_gestion'=>$datos_gestion));
			
			
			$first=false;
		}
		
		// here we insert the custom fields into the database for each gestion
		foreach($custom_fields as $k=>$v) {
			// the custom fields information is tied to the idGestion
			$row = array(
				'id_gestion'=>$idGestion,
				'field'=>'\''.$db->escape($k).'\'',
				'value'=>'\''.$db->escape($v).'\''
			);
			$query2='INSERT INTO gestiones.gestion_custom_fields ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')';
			$db->query($query2);
			
		}
	}
    $db->commit();
	
	

}catch(Exception $e) {
	//$error=print_arr($_request,true).'<hr>'.print_arr($tipificacion,true).'<hr><pre>'.$query.'</pre><hr>'.$e->getMessage();
	$error=$e->getMessage();
	throw new Exception($error);
}
