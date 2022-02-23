<?php
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
$db->prepare('get_id_cuenta_by_cuenta_proceso','SELECT get_id_cuenta_by_cuenta_proceso AS id_cuenta FROM public.get_id_cuenta_by_cuenta_proceso('.$data['id_proceso'].',$1)');
$db->prepare('carga_add_cuenta_actualizacion','SELECT cargas.carga_add_cuenta_actualizacion('.$carga->id_carga.','.$data['id_proceso'].',$1,$2,$3,$4,$5)');
$db->prepare('carga_seg_actualizaciones','INSERT INTO cargas.carga_seg_actualizaciones (cuenta,tipo_actualizacion,valor,fecha,id_carga) VALUES ($1,$2,$3,$4,'.$carga->id_carga.')');
$db->prepare('carga_seg_actualizaciones2','INSERT INTO cargas.carga_seg_actualizaciones (cuenta,tipo_actualizacion,valor,fecha,observacion,id_carga) VALUES ($1,$2,$3,$4,$5,'.$carga->id_carga.')');
$db->prepare('cuenta_get_valor_actual_by_id_cuenta','SELECT valor_actual FROM cuentas.cuenta WHERE id_cuenta=$1');

$i=0;
$BM->mark('inicio');
$start_load_timer=microtime(true);
foreach($ret as $cuenta) {
	if(is_null($cuenta)) continue;
	if(!is_object($cuenta))
		throw new Exception('El return value del Handler Actualizaciones debe ser una clase');
	if(!in_array('CargaModelo_Item_Abstract',class_parents($cuenta)))
		throw new Exception('El return value del Handler Actualizaciones debe ser una clase que extienda a CargaModelo_Item_Abstract');
	if(get_class($cuenta)!='CargaModelo_Item_Cuenta')
		throw new Exception('El return value del Handler Actualizaciones debe ser una clase tipo CargaModelo_Item_Cuenta');
	
	$cuenta->validate();
	$BM->mark('buscar_id_cuenta');
	$id_cuenta=$db->execute('get_id_cuenta_by_cuenta_proceso',array($cuenta->numero_cuenta));
	$BM->mark('buscar_id_cuenta');
	$id_cuenta=$id_cuenta->current()['id_cuenta'];
	// var_dump($id_cuenta);
	// die();
	if(is_null($id_cuenta)) continue;
	
	$va_inicial = ($db->execute('cuenta_get_valor_actual_by_id_cuenta',array($id_cuenta))->current())['valor_actual'];
	$va_con_actualizaciones = $va_inicial;
	foreach($cuenta->actualizaciones as $a) {
		$va_con_actualizaciones+=$a->valor;
		$db->execute('carga_add_cuenta_actualizacion',array(
			// in_id_cuenta
			$id_cuenta,
			// in_diferencia
			$a->valor,
			// in_tipo_actualizacion
			$a->tipo_actualizacion,
			// in_fecha_actualizacion
			$a->fecha_actualizacion.' '.$a->hora_actualizacion,
			// in_observacion
			'Carga '.$carga->id_carga.' realizada el '.date('Y-m-d').' por '.Auth::getUser()
		));
		
		$db->execute('carga_seg_actualizaciones',array(
			// cuenta
			$cuenta->numero_cuenta,
			// tipo_actualizacion
			$a->tipo_actualizacion,
			// valor
			$a->valor,
			// fecha
			$a->fecha_actualizacion.' '.$a->hora_actualizacion
		));
	}

	if(!is_null($cuenta->valor_actual) && $cuenta->valor_actual!=$va_con_actualizaciones) {
		$ajuste = $cuenta->valor_actual-$va_con_actualizaciones;
		$db->execute('carga_add_cuenta_actualizacion',array(
			// in_id_cuenta
			$id_cuenta,
			// in_diferencia
			$ajuste,
			// in_tipo_actualizacion
			'CORRECCION',
			// in_fecha_actualizacion
			date('Y-m-d H:i:s'),
			// in_observacion
			'Carga '.$carga->id_carga.' realizada el '.date('Y-m-d').' por '.Auth::getUser()
		));
		
		$db->execute('carga_seg_actualizaciones2',array(
			// cuenta
			$id_cuenta,
			// tipo_actualizacion
			'CORRECCION',
			// valor
			$ajuste,
			// fecha
			date('Y-m-d H:i:s'),
			// observacion
			'Valor actual no coincide con archivo subido luego de actualizaciones. Carga: '.$carga->id_carga.' Proceso: '.$data['id_proceso']
		));
		
	}
	
	$i++;
}
$BM->mark('inicio');
$db->commit();
$diff=microtime(true)-$start_load_timer;
$_T['maincontent']='<h1 style="color: green;"> Los datos han sido almacenados satisfactoriamente</h1><br>Tomé '.(($diff)/60).' minutos ('.$diff.' segundos)';
$_T['maincontent'].='<hr>'.$BM->resume();

