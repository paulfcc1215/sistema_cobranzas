<?php
$db->prepare('get_id_cuenta_by_cuenta_proceso','SELECT get_id_cuenta_by_cuenta_proceso AS id_cuenta FROM public.get_id_cuenta_by_cuenta_proceso('.$data['id_proceso'].',$1)');
$db->prepare('new_gestion','SELECT gestiones.new_gestion($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14,$15,$16,$17)');

$db->startTransaction();
$i=0;
$BM->mark('inicio');
$start_load_timer=microtime(true);
foreach($ret as $gestion) {
	if(is_null($gestion)) continue;
	if(!is_object($gestion))
		throw new Exception('El return value del Handler Gestiones debe ser una clase');
	if(!in_array('CargaModelo_Item_Abstract',class_parents($gestion)))
		throw new Exception('El return value del Handler Gestiones debe ser una clase que extienda a CargaModelo_Item_Abstract');
	$gestion->validate();
	if(is_null($gestion->id_cuenta)) {
		$BM->mark('buscar_id_cuenta');
		$id_cuenta=$db->execute('get_id_cuenta_by_cuenta_proceso',array($gestion->cuenta));
		$BM->mark('buscar_id_cuenta');
		$id_cuenta=$id_cuenta->current()['id_cuenta'];
		if(is_null($id_cuenta))
			throw new Exception('No se logró conseguir el id de cuenta para la cuenta "'.$gestion->cuenta.'"');
		$gestion->id_cuenta=$id_cuenta;
	}
	$db->execute('new_gestion',array(
		// id_cuenta
		'id_cuenta'=>$gestion->id_cuenta,
		// fecha_inicio
		'fecha_inicio'=>$gestion->fecha_inicio,
		// telh_id
		'telh_id'=>$gestion->telh_id,
		// user_name
		'user_name'=>$gestion->user_name,
		// tel_number
		'tel_number'=>$gestion->tel_number,
		// id_tipificacion
		'id_tipificacion'=>$gestion->id_tipificacion,
		// fecha_fin
		'fecha_fin'=>($gestion->fecha_fin==''?null:$gestion->fecha_fin),
		// servidor
		'servidor'=>$gestion->servidor,
		// observacion
		'observacion'=>$gestion->observacion,
		// fecha_compromiso
		'fecha_compromiso'=>$gestion->fecha_compromiso,
		// monto_compromiso
		'monto_compromiso'=>$gestion->monto_compromiso,
		// ip_cliente
		'ip_cliente'=>$gestion->ip_cliente,
		// id_gestion_ref
		'id_gestion_ref'=>null,
		// email
		'email'=>$gestion->email,
		// latitud
		'latitud'=>$gestion->latitud,
		// longitud
		'longitud'=>$gestion->longitud,
		// direccion
		'direccion'=>$gestion->direccion,
	));
	
	$i++;
}
$BM->mark('inicio');
$db->commit();
$diff=microtime(true)-$start_load_timer;
$_T['maincontent']='<h1 style="color: green;"> Los datos han sido almacenados satisfactoriamente</h1><br>Tomé '.(($diff)/60).' minutos ('.$diff.' segundos)';
$_T['maincontent'].='<hr>'.$BM->resume();