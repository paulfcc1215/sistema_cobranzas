<?php
	//$BM->mark('inicio');
	// PROCESO NORMAL DE CARGA
	$db->startTransaction();
	// creamos la carga
	$carga=$_AM['carga']->insert(
		array(
			'id_proceso'=>$db->escape($_POST['id_proceso']),
			'descripcion'=>$db->escape($_POST['descripcion_carga']),
			'fecha_carga'=>'NOW()',
			'usuario'=>Auth::getUser(),
			'status'=>'1',
			'tipo_carga'=>$ret->getTipoCarga()
		)
	);


    // persona
	// {in_id_carga,in_id_proceso,in_tipo_identificacion,in_identificacion,in_primer_nombre,in_segundo_nombre,in_primer_apellido,in_segundo_apellido}
	$db->prepare('persona','SELECT cargas.carga_new_persona('.$carga->id_carga.','.$proceso->id_proceso.',$1::personas.enum_tipo_identificacion,$2,$3,$4,$5,$6) AS id_persona');

	// medios_contacto
	// {in_id_carga INT,in_id_persona INT,in_tipo_medio medios_contacto.enum_tipo_medio_contacto,in_contenido TEXT}
	$db->prepare('medio_contacto','SELECT cargas.new_medio_contacto('.$carga->id_carga.',$1,$2::medios_contacto.enum_tipo_medio_contacto,$3) AS id_medio_contacto');

	// carga_new_cuenta
	// {in_id_carga,in_id_proceso,in_cuenta,in_id_responsable}
	$db->prepare('cuenta','SELECT cargas.carga_new_cuenta('.$carga->id_carga.','.$carga->id_proceso.',$1,$2) AS id_cuenta');

	// carga_cuenta_set_valor_original
	// {in_id_carga,in_id_cuenta,in_valor_orignal}
	$db->prepare('cuenta_set_valor','SELECT cargas.carga_cuenta_set_valor_original('.$carga->id_carga.',$1,$2)');

	// cuenta_set_valor_actual
	$db->prepare('cuenta_set_valor_actual','UPDATE cuentas.cuenta SET valor_actual=$2,fecha_valor_actual=NOW(),id_carga_valor_actual='.$carga->id_carga.' WHERE id_cuenta=$1');

	// carga_add_cuenta_actualizacion
	// {in_id_carga,in_id_proceso,in_id_cuenta,in_diferencia,in_tipo_actualizacion,in_fecha_actualizacion}
	$db->prepare('carga_add_cuenta_actualizacion','SELECT cargas.carga_add_cuenta_actualizacion('.$carga->id_carga.','.$carga->id_proceso.',$1,$2,$3::cuentas.enum_tipo_actualizacion,$4::timestamp without time zone,$5)');
	$db->prepare('carga_seg_actualizaciones','INSERT INTO cargas.carga_seg_actualizaciones (cuenta,tipo_actualizacion,valor,fecha,observacion,id_carga) VALUES ($1,$2,$3,$4,$5,'.$carga->id_carga.')');
	
	// carga_add_telefono
	// {in_id_carga,in_id_persona,in_tipo_telefono,in_telefono}
	$db->prepare('carga_add_telefono','SELECT cargas.carga_add_telefono('.$carga->id_carga.',$1,$2::"medios_contacto"."enum_tipo_telefono",$3,$4)');

	// carga_set_cuenta_deudor
	// {in_id_carga,in_id_proceso,in_id_cuenta,in_id_persona}
	$db->prepare('carga_set_cuenta_deudor','SELECT cargas.carga_set_cuenta_deudor('.$carga->id_proceso.',$1,$2)');

	// carga_set_responsables
	// {in_id_carga INT,in_id_proceso INT,in_id_cuenta INT,in_ids_personas carga_add_responsable_in_tuple[]}
	//	Composite type "cargas.carga_add_responsable_in_tuple"
	//		  Column      |  Type   | Modifiers
	//	------------------+---------+-----------
	//	 id_persona       | integer |
	//	 tipo_responsable | text    |
	$db->prepare('carga_set_responsables','SELECT * FROM cargas.carga_set_responsables('.$carga->id_carga.','.$carga->id_proceso.',$1,$2::cargas.carga_add_responsable_in_tuple[])');

	$db->prepare('empty_cuenta_responsables','DELETE FROM cuentas.cuenta_responsable WHERE id_cuenta=$1 AND tipo_responsable<>\'DEUDOR\'');
	
	$db->prepare('seguimiento_cuentas','INSERT INTO cargas.carga_seg_cuentas (numero_cuenta,identificacion,primer_nombre,segundo_nombre,primer_apellido,segundo_apellido,valor,id_carga) VALUES ($1,$2,$3,$4,$5,$6,$7,'.$carga->id_carga.')');

	// carga direccion
	$db->prepare('carga_direccion','INSERT INTO medios_contacto.direcciones(id_persona,tipo_direccion,hash) VALUES ($1,$2,$3) RETURNING id_direccion');
	// carga direccion data
	$db->prepare('carga_direccion_data','INSERT INTO medios_contacto.direcciones_data(id_direccion,id_tipo_ubicacion,valor) VALUES ($1,$2,$3)');

	// otros datos
	$db->prepare('otros_datos','INSERT INTO cargas.carga_no_mapeada (id_carga,id_cuenta,campo,valor,"order") VALUES ('.$carga->id_carga.',$1,$2,$3,$4)');
	$consolidado_cuentas=array();

	foreach($ret as $nRec=>$rec) {
		if(is_null($rec)) continue;
		$_carga = array();
		$consolidado_cuentas[]=$rec['cuenta']->numero_cuenta;
		//$BM->mark('record');
		// CREACION DE PERSONAS
		// persona responsable
		if(is_null($rec['cuenta']->persona_responsable)){
			throw new Exception('La cuenta '.$rec['cuenta']->numero_cuenta.' no tiene persona responsable!');
		}
		$BM->mark('persona_responsable_persona');
		$_carga['ids']['persona_responsable']=$db->execute('persona',array(
			$rec['cuenta']->persona_responsable->tipo_identificacion,
			$rec['cuenta']->persona_responsable->identificacion,
			$rec['cuenta']->persona_responsable->primer_nombre,
			$rec['cuenta']->persona_responsable->segundo_nombre,
			$rec['cuenta']->persona_responsable->primer_apellido,
			$rec['cuenta']->persona_responsable->segundo_apellido,
		))->current();
		$BM->mark('persona_responsable_persona');
		
		// medio contacto persona responsable
		foreach($rec['cuenta']->persona_responsable->medios_contacto as $mc) {
			$BM->mark('persona_responsable_medio_contacto');
			$_carga['ids']['persona_responsable']['medios_contacto'][]=$db->execute('medio_contacto',
				array(
					$_carga['ids']['persona_responsable']['id_persona'],
					$mc->tipo,
					$mc->contenido
				)
			)->current()['id_medio_contacto'];
			$BM->mark('persona_responsable_medio_contacto');
		}
		
		// telefono persona responsable
		foreach($rec['cuenta']->persona_responsable->telefonos as $telefono) {
			$BM->mark('persona_responsable_telefono');
			$_carga['ids']['persona_responsable']['telefonos'][]=$db->execute('carga_add_telefono',array(
				$_carga['ids']['persona_responsable']['id_persona'],
				$telefono->tipo,
				$telefono->numero,
				$telefono->origen
			))->current()['carga_add_telefono'];
			$BM->mark('persona_responsable_telefono');
		}
								
		// direccion persona responsable
		foreach($rec['cuenta']->persona_responsable->direcciones as $direccion) {

			$BM->mark('persona_responsable_direccion');
			
			$aux_dir = $direccion->direccion;
			ksort($aux_dir);
			$aux_dir = implode('',$aux_dir);
			$aux_dir = preg_replace('#[^A-za-z0-9]#','',$aux_dir);
			$aux_dir = crc32($aux_dir);

			// get direcciones cliente
			$q = 'SELECT * FROM medios_contacto.direcciones WHERE id_persona='.$_carga['ids']['persona_responsable']['id_persona'].' AND hash=\''.$aux_dir.'\'';
			$q0 = $db->query($q);

			if ($db->numRows($q0)==0){
				$_carga['ids']['persona_responsable']['direcciones'][] = $last = $db->execute('carga_direccion',array(
					$_carga['ids']['persona_responsable']['id_persona'],
					$direccion->tipo_direccion,
					$aux_dir
				))->current()['id_direccion'];
				// direcciones data
				foreach ($direccion->direccion as $tipo_ubicacion => $tipo_ubi_data) {
					$db->execute('carga_direccion_data',array(
						$last,
						$tipo_ubicacion,
						$tipo_ubi_data
					));
				}
			}
			$BM->mark('persona_responsable_direccion');

		}
		
		// personas adicionales
		foreach($rec['cuenta']->otras_personas as $persona) {
			$BM->mark('persona_adicional_persona');
			// personas adicionales - persona
			$aux=array();
			$aux['id_persona']=$db->execute('persona',array(
				$persona['persona']->tipo_identificacion,
				$persona['persona']->identificacion,
				$persona['persona']->primer_nombre,
				$persona['persona']->segundo_nombre,
				$persona['persona']->primer_apellido,
				$persona['persona']->segundo_apellido,
			))->current()['id_persona'];
			$BM->mark('persona_adicional_persona');
			
		
			
			// personas adicionales - medio contacto
			foreach($persona['persona']->medios_contacto as $mc) {
				$BM->mark('persona_adicional_medio_contacto');
				$aux['medios_contacto'][]=$db->execute('medio_contacto',
					array(
						$aux['id_persona'],
						$mc->tipo,
						$mc->contenido
					)
				)->current()['id_medio_contacto'];
				$BM->mark('persona_adicional_medio_contacto');
			}
			

			
			// personas adicionales - telefonos
			foreach($persona['persona']->telefonos as $tel) {
				$BM->mark('persona_adicional_telefono');
				$aux['telefonos'][]=$db->execute('carga_add_telefono',array(
					$aux['id_persona'],
					$tel->tipo,
					$tel->numero,
					$tel->origen
				))->current()['carga_add_telefono'];
				$BM->mark('persona_adicional_telefono');
			}
			$aux['tipo']=$persona['tipo'];
			$_carga['ids']['otras_personas'][]=$aux;
		}
		
		// creamos la cuenta
		// cuenta
		$BM->mark('cuenta_create');
		$_carga['ids']['cuenta']['id']=$db->execute('cuenta',array(
			$rec['cuenta']->numero_cuenta,
			$_carga['ids']['persona_responsable']['id_persona']
		))->current();
		$_carga['ids']['cuenta']['id']=strtr($_carga['ids']['cuenta']['id']['id_cuenta'],array('('=>'',')'=>''));
		// separamos valores retornados de <id_cuenta,is_new>
		$_carga['ids']['cuenta']['id']=explode(',',$_carga['ids']['cuenta']['id']);
		// convertimos a booleano el is_new
		$_carga['ids']['cuenta']['is_new']=($_carga['ids']['cuenta']['id'][1]==1);
		// asignamos id_cuenta
		$_carga['ids']['cuenta']['id']=$_carga['ids']['cuenta']['id'][0];
		$BM->mark('cuenta_create');

		// traemos la cuenta
		$BM->mark('cuenta_get_by_id');
		$_carga['cuenta']=$_AM['cuenta']->getById($_carga['ids']['cuenta']['id']);
		$BM->mark('cuenta_get_by_id');

		// seteamos a la persona responsable
		$BM->mark('cuenta_persona_responsable');
		$db->execute('carga_set_cuenta_deudor',array(
			$_carga['ids']['cuenta']['id'],
			$_carga['ids']['persona_responsable']['id_persona']
		));
		$BM->mark('cuenta_persona_responsable');
		
		
		// almacenamos las actualizaciones
		$diff_acum=0;
		foreach($rec['cuenta']->actualizaciones as $actualizacion) {
			$BM->mark('cuenta_actualizacion');
			$db->execute('carga_add_cuenta_actualizacion',array(
				$_carga['ids']['cuenta']['id'],
				$actualizacion->valor,
				$actualizacion->tipo_actualizacion,
				$actualizacion->fecha_actualizacion.' '.$actualizacion->hora_actualizacion,
				'Actualizacion de cartera. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
			));
			
			$db->execute('carga_seg_actualizaciones',array(
				// cuenta
				//$rec['cuenta']->numero_cuenta,
				$_carga['ids']['cuenta']['id'],
				// tipo_actualizacion
				$actualizacion->tipo_actualizacion,
				// valor
				$actualizacion->valor,
				// fecha
				$actualizacion->fecha_actualizacion.' '.$actualizacion->hora_actualizacion,
				// observacion
				'Actualizacion de cartera. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
			));
			
			
			$diff_acum+=$actualizacion->valor;
			$BM->mark('cuenta_actualizacion');
		}
		
		
		if(is_null($_carga['cuenta']->valor_original) || $_carga['ids']['cuenta']['is_new']) {
			$BM->mark('cuenta_set_valor_original');
			// si la cuenta es nueva, se forza el valor actual a lo que viene en el archivo
			$db->execute('cuenta_set_valor',array($_carga['ids']['cuenta']['id'],$rec['cuenta']->valor_actual));
			$db->execute('cuenta_set_valor_actual',array($_carga['ids']['cuenta']['id'],($rec['cuenta']->valor_actual+$diff_acum)));
			$BM->mark('cuenta_set_valor_original');
		}else{
			// la cuenta no es nueva
			if($rec['cuenta']->valor_actual != $_carga['cuenta']->valor_actual) {
				// si no coincide valor actual en base con valor actual en archivo
				$va_luego_de_actualizaciones=$_carga['cuenta']->valor_actual+$diff_acum;
				if($va_luego_de_actualizaciones != $rec['cuenta']->valor_actual) {
					// valor actual luego de actualizaciones sigue sin coincidir con lo que esta en la base de datos
					$diff=$rec['cuenta']->valor_actual-$va_luego_de_actualizaciones;
					// se debe agregar una correccion para poder hacer que todo coincida
					$db->execute('carga_add_cuenta_actualizacion',array(
						$_carga['ids']['cuenta']['id'],
						$diff,
						'CORRECCION',
						date('Y-m-d h:i:s'),
						'Valor actual no coincide con archivo subido luego de actualizaciones. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso

					));

					$db->execute('carga_seg_actualizaciones',array(
						// cuenta
						$rec['cuenta']->numero_cuenta,
						// tipo_actualizacion
						'CORRECCION',
						// valor
						$diff,
						// fecha
						date('Y-m-d h:i:s'),
						// observacion
						'Valor actual no coincide con archivo subido luego de actualizaciones. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
					));					
				}
			}else{
				// el valor actual en base coincide con lo que vino en el archivo
				if(count($rec['cuenta']->actualizaciones)>0) {
					// si es que hay actualizaciones, de ley hay que crear la correccion
					$db->execute('carga_add_cuenta_actualizacion',array(
						$_carga['ids']['cuenta']['id'],
						$diff_acum*(-1),
						'CORRECCION',
						date('Y-m-d h:i:s'),
						'Valor actual coincide con lo que hay en archivo. Pero hay actualizaciones. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
					));

					$db->execute('carga_seg_actualizaciones',array(
						// cuenta
						$rec['cuenta']->numero_cuenta,
						// tipo_actualizacion
						'CORRECCION',
						// valor
						$diff_acum*(-1),
						// fecha
						date('Y-m-d h:i:s'),
						// observacion
						'Valor actual coincide con lo que hay en archivo. Pero hay actualizaciones. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
					));					
					
					
				}
			}
		}
		
		
		// se setean las personas relacionadas
		// {"(8,\"GARANTE\")","(9,\"AYUDANTE\")","(10,\"CONTACTO\")"}
		$BM->mark('cuenta_otras_personas');
		$aux=array();
		foreach($_carga['ids']['otras_personas'] as $persona2) {
			$aux[]='"('.$persona2['id_persona'].',\\"'.$persona2['tipo'].'\\")"';
		}


		if(!empty($aux)) {
			$db->execute('carga_set_responsables',array(
				$_carga['ids']['cuenta']['id'],
				'{'.implode(',',$aux).'}'
			));
		}else{
			// no hay responsables adicionales en el archivo
			// se limpian de la base
			$db->execute('empty_cuenta_responsables',array($_carga['ids']['cuenta']['id']));
		}
		$BM->mark('cuenta_otras_personas');
		
		// se almacenan los "otros datos"
		$BM->mark('otros_datos');
		/*
		$od_order=0;
		foreach($rec['otros_datos'] as $k=>$v) {
			$od_order++;
			$db->execute('otros_datos',array(
				$_carga['ids']['cuenta']['id'],
				$k,
				$v,
				$od_order
			));
		}
		*/
		if(!empty($rec['otros_datos'])) {
			$aux=array();
			$query='INSERT INTO cargas.carga_no_mapeada (id_carga,id_cuenta,campo,valor,"order") VALUES ';
			$od_order=0;
			foreach($rec['otros_datos'] as $k=>$v) {
				$od_order++;
				$aux[]=implode(',',array(
					$carga->id_carga,
					$_carga['ids']['cuenta']['id'],
					'\''.$db->escape($k).'\'',
					'\''.$db->escape($v).'\'',
					$od_order
				));
			}
			$query.='('.implode('),(',$aux).')';
			$db->query($query);
		}
		$BM->mark('otros_datos');
		//$BM->mark('record');
		$BM->mark('seguimiento_cuentas');
		
		$db->execute('seguimiento_cuentas',array(
			// numero_cuenta
			$rec['cuenta']->numero_cuenta,
			// identificacion
			$rec['cuenta']->persona_responsable->identificacion,
			// primer_nombre
			$rec['cuenta']->persona_responsable->primer_nombre,
			// segundo_nombre
			$rec['cuenta']->persona_responsable->segundo_nombre,
			// primer_apellido
			$rec['cuenta']->persona_responsable->primer_apellido,
			// segundo_apellido
			$rec['cuenta']->persona_responsable->segundo_apellido,
			// valor
			$rec['cuenta']->valor_actual
		));
	}
	// obtenemos el listado de las cuentas que no vinieron en el archivo
	// evaluamos cuales cuentas no llegaron en el archivo
	$cuentas_en_db_no_en_archivo=get_cuentas_en_db_no_en_archivo($consolidado_cuentas,$proceso->id_proceso);
	if(!empty($cuentas_en_db_no_en_archivo)) {
		foreach($cuentas_en_db_no_en_archivo as $c) {
			break;
			// se aplica la correccion *(-1) del valor actual para encerar
			$db->execute('carga_add_cuenta_actualizacion',array(
				$_carga['ids']['cuenta']['id'],
				$c['valor_actual']*(-1),
				'CORRECCION',
				date('Y-m-d h:i:s'),
				'Cuenta no vino en el archivo de carga. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
			));
			
			$db->execute('carga_seg_actualizaciones',array(
				// cuenta
				$rec['cuenta']->numero_cuenta,
				// tipo_actualizacion
				'CORRECCION',
				// valor
				$c['valor_actual']*(-1),
				// fecha
				date('Y-m-d h:i:s'),
				// observacion
				'Cuenta no vino en el archivo de carga. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
			));					
			
			
		}
	}	

	// ya se cargaron todos los datos
	// procedemos a almacenar el archivo completo original
    // if(empty($ret->getFiles())) {
        // throw new Exception('El cargador no devolvió los archivos.');
    // }
	foreach($ret->getFiles() as $file) {
		$data=file_get_contents($file['filepath']);
		if($data===false)
			throw new Exception('Error al leer "'.$file['filepath'].'"');
		$row=array(
			'id_carga'=>$carga->id_carga,
			'nombre_archivo'=>'\''.$db->escape($file['filename']).'\'',
			'md5'=>'\''.md5($data).'\'',
			'raw_data'=>null,
			'original_size'=>strlen($data),
			'compressed_size'=>null,
			'md5_compressed'=>null,
			'tipo'=>'\'DATA\''
		);
		$data=gzcompress($data,9);
		$row['raw_data']='\''.$db->escape_bytea($data).'\'';
		$row['compressed_size']=strlen($data);
		$row['md5_compressed']='\''.md5($data).'\'';
		
		
		$query='INSERT INTO cargas.carga_data ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')';
		$db->query($query);
	}

	$BM->mark('raw_files');
	// cargamos el procesamiento
	$uid=decrypt($_POST['details_uid']);
	$dhdl=opendir(_TMP_UPLOAD_FOLDER);
	if(!$dhdl) throw new Exception('Error al abrir tmp folder');
	$list=array();
	while($ptr=readdir($dhdl)) {
		if($ptr=='.' || $ptr=='..') continue;
		$aux=explode('_',$ptr);
		if($aux[0]==$uid) $list[]=$ptr;
	}
	$tempfile=tempnam('/tmp','zip');
	$zip=new ZipArchive();
	$zip->open($tempfile,ZipArchive::CREATE);
	foreach($list as $l) {
		$aux=explode('_',$l);
		unset($aux[0]);
		$zip->addFile(_TMP_UPLOAD_FOLDER.'/'.$l,implode('_',$aux));
	}
	$zip->close();
	foreach($list as $l) {
		unlink(_TMP_UPLOAD_FOLDER.'/'.$l,implode('_',$aux));
	}

	$data=file_get_contents($tempfile);
	$row=array(
		'id_carga'=>$carga->id_carga,
		'nombre_archivo'=>'\'detalles.zip\'',
		'md5'=>'\''.md5($data).'\'',
		'raw_data'=>null,
		'original_size'=>strlen($data),
		'compressed_size'=>null,
		'md5_compressed'=>null,
		'tipo'=>'\'DETALLES\''
	);
	$data=gzcompress($data,9);
	$row['raw_data']='\''.$db->escape_bytea($data).'\'';
	$row['compressed_size']=strlen($data);
	$row['md5_compressed']='\''.md5($data).'\'';					
	$query='INSERT INTO cargas.carga_data ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')';
	$db->query($query);

	// $db->rollback();
	$db->commit();
	$BM->mark('raw_files');
	//$BM->mark('inicio');
	$diff=microtime(true)-$start_timer;
	$_T['maincontent']='<h1 style="color: green;"> Los datos han sido almacenados satisfactoriamente</h1><br>Tomé '.(($diff)/60).' minutos ('.$diff.' segundos)';
	$_T['maincontent'].='<hr>'.$BM->resume();