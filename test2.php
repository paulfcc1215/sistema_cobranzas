<?php

require 'config.php';

// header('Content-Type: application/json; charset=utf-8');

$db=DB::getInstance();
$q = 'SELECT 
	pe.identificacion,
	c.id_cuenta,c.cuenta,c.valor_original,
	cn.*
	FROM cuentas.cuenta c
		JOIN cargas.carga_no_mapeada cn ON(cn.id_cuenta=c.id_cuenta)
		JOIN personas.persona pe ON(pe.id_persona=c.id_deudor)
		JOIN cargas.carga car ON(car.id_carga=c.id_carga)
		JOIN campanas.proceso p ON(c.id_proceso=p.id_proceso)
	WHERE 
		c.id_proceso=119 AND c.id_carga=2833
	LIMIT 1005';
	$cedulas_procesadas = array();
foreach ($db->query($q) as $row){
	
	// print_arr($row);
	if ($row['campo']=='fecha_emision_mes'){
		$fecha_emision = $row['valor'];
		continue;
	}
	if ($row['campo']=='fecha de facturacion'){
		$fecha_facturacion = $row['valor'];
		continue;
	}
	if (!is_null($fecha_facturacion) && !is_null($fecha_emision)){
		$fecha_emision = explode ('/',$fecha_emision);
		$fecha_emision = new DateTime($fecha_emision[2].'-'.$fecha_emision[1].'-'.$fecha_emision[0]);
		$fecha_facturacion = explode ('/',$fecha_facturacion);
		$fecha_facturacion = new DateTime($fecha_facturacion[2].'-'.$fecha_facturacion[1].'-'.$fecha_facturacion[0]);
		$intvl = $fecha_emision->diff($fecha_facturacion);
		$cartera[$row['identificacion']]['dias_vencidos'] = $intvl->days;
		if ($intvl->days>0 && $intvl->days<=30)
			$cartera[$row['identificacion']]['rango_dias_vencidos'] = '1. De 0 a 30 días';
		if ($intvl->days>30 && $intvl->days<=60)
			$cartera[$row['identificacion']]['rango_dias_vencidos'] = '2. De 31 a 60 días';
		if ($intvl->days>60 && $intvl->days<=180)
			$cartera[$row['identificacion']]['rango_dias_vencidos'] = '3. De 61 a 180 días';
		if ($intvl->days>180 && $intvl->days<=360)
			$cartera[$row['identificacion']]['rango_dias_vencidos'] = '4. De 181 a 360 días';
		if ($intvl->days>360 && $intvl->days<=720)
			$cartera[$row['identificacion']]['rango_dias_vencidos'] = '5. De 361 a 720 días';
		if ($intvl->days>720)
			$cartera[$row['identificacion']]['rango_dias_vencidos'] = '6. Mayor a 720 días';
		$fecha_facturacion=null;
		$fecha_emision=null;
	}
	$cartera[$row['identificacion']]['id_cuenta'] = $row['id_cuenta'];
	$cartera[$row['identificacion']]['identificacion'] = $row['identificacion'];
	$cartera[$row['identificacion']]['valor_original'] = $row['valor_original'];
	$cartera[$row['identificacion']][$row['campo']] = $row['valor'];
}
print_arr($cartera);
die();
echo json_encode(array_values($cartera));

die();

// require 'config.php';

// function get_cuenta($cuenta){
// 	GLOBAL $db;
// 	$q = 'SELECT * FROM cuentas.cuenta WHERE cuenta=\''.$cuenta.'\' AND id_proceso=108';
// 	$q0 = $db->query($q);
// 	return $db->fetchOne($q0);
// }

// function get_mail_from_repo($identificacion){
// 	GLOBAL $db_repo;
// 	$q = 'SELECT mail FROM repository_personas.correo_electronico WHERE pe_numero_identificacion=\''.$identificacion.'\'';
// 	$q0 = $db_repo->query($q);
// 	$mail = '';
// 	if ($db_repo->numRows($q0)==0) return $mail;
// 	$mail = $db_repo->fetchOne($q0);
// 	$mail = trim($mail['mail']);
// 	return $mail;
// }


// //CARGA GESTIONES JEP
// $db = DB::getInstance();
// $db_repo = DB::getInstance('repo');

// $db->prepare('get_cuenta','SELECT * FROM cuentas.cuenta WHERE id_proceso=$1 AND cuenta=$2');
// $db->prepare('get_cuentas_by_cedula','SELECT * FROM cuentas.cuenta JOIN personas.persona ON (persona.id_persona = cuenta.id_deudor) WHERE cuenta.id_proceso IN (107,108) AND persona.identificacion=$1');



// //get tipificaciones recappt
// foreach(getTipificacionesByCampana(15) as $t){
// 	$tipificaciones_recappt[$t['id_tipificacion']]=$t['descripcion'];
// }

// //get tipificaciones jep
// $q = 'SELECT * FROM	custom.jep_tipificaciones';
// foreach ($db->query($q) as $v){
// 	$tipificaciones_jep[$v['id_jep_tipificaciones']]=$v['tipificacion'];
// }

// $data = (file_get_contents('GESTIONES.txt'));
// $data = explode("\r\n",$data);
// foreach($data as &$d) {
// 	$d = explode("\t",$d);
// 	unset($d);
// }
// $head = array_shift($data);
// $lineN = 0;
// $errores=0;

// $idsLlamadas=array();
// $total = count($data);
// $dups=array();
// foreach($data as $aux) {
// 	$lineN++;
// 	$line=array();
// 	foreach($head as $k=>$v) {
// 		$line[$v] = trim($aux[$k]);
// 	}
	
// 	try {
// 		switch($line['tipificacion recapt']) {
// 			case 'MENSAJE A TERCEROS':
// 				$line['tipificacion recapt']='MSJ TERCERO';
// 			break;
			
// 			case 'PROMESA DE PAGO':
// 			case 'PROMESA_DE_PAGO':
// 				$line['tipificacion recapt']='PROMESA';
// 			break;
			
// 			case 'TERCEROS':
// 			case 'MENSAJE A  TERCEROS':
// 				$line['tipificacion recapt']='MSJ TERCERO';
// 			break;

// 			case 'VOLVER A LLAMAR':
// 				$line['tipificacion recapt']='NO CONTESTA';
// 			break;
// 		}
		
// 		switch($line['tipificacion jep']) {
// 			case 'NO CONTESTA':
// 				$line['tipificacion jep']='OTROS';
// 			break;
			
// 			case 'LLAMADA CONYUGE DEUDOR':
// 				$line['tipificacion jep']='LLAMADA CONYUGUE DEUDOR';
// 			break;

// 			case 'LLAMADA CONYUGE GARANTE':
// 				$line['tipificacion jep']='LLAMADA CONYUGUE GARANTE';
// 			break;
// 		}
		
// 		if(($line['CEDULA'])=='')
// 			throw new Exception('Sin cedula');

// 		if($line['fecha_compromiso']=='1900-01-00')
// 			$line['fecha_compromiso']='';
		
// 		if($line['fecha_gestion']=='1900-01-00')
// 			$line['fecha_gestion']='';

// 		if($line['fecha_gestion']=='')
// 			throw new Exception('Sin Fecha Gestion');


// 		if($line['id_llamada']=='')
// 			throw new Exception('Sin id llamada');
		
// 		if(($line['user_name'])=='')
// 			throw new Exception('Sin Username');
		
// 		if(($line['telefono_gestion'])=='')
// 			throw new Exception('Sin telefono gestion');

// 		if(($line['tipificacion recapt'])=='')
// 			throw new Exception('Sin tipificacion recapt');
		
// 		if(!in_array($line['tipificacion recapt'],$tipificaciones_recappt)) {
// 			echo ('Tipificacion recapt '.$line['tipificacion recapt'].' no existe');
// 			print_r($tipificaciones_recappt);
// 			die();
// 			throw new Exception('Tipificacion recapt '.$line['tipificacion recapt'].' no existe');
// 		}

// 		if($line['tipificacion jep']=='')
// 			throw new Exception('Sin tipificacion JEP');

// 		if(!in_array($line['tipificacion jep'],$tipificaciones_jep)) {
// 			echo ('Tipificacion jep "'.$line['tipificacion jep'].'" no existe');
// 			echo "\r\n"; print_r($tipificaciones_jep);
// 			die();
// 			throw new Exception('Tipificacion jep "'.$line['tipificacion jep'].'" no existe');
// 		}
		
// 		// fecha cedula cuenta observación
// 		$concat = $line['fecha_gestion'].$line['CEDULA'].$line['cuenta'].$line['observacion'].$line['telefono_gestion'];
// 		$md5 = md5($concat,true);
		
// 		foreach($dup as $dupr) {
// 			if($dupr[0]==$md5) {
// 				$cDups[$line['cuenta']]++;
// 				//throw new Exception('Registro duplicado en linea '.$dupr[1]);
// 				throw new Exception('Registro duplicado');
// 			}
// 		}
// 		$dup[]=array(
// 			$md5,
// 			$lineN
// 		);
		
// 		$special = false;
// 		$load = array();
// 		if(true || ($line['cuenta'])=='') {
// 			$q0 = $db->execute('get_cuentas_by_cedula',array($line['CEDULA']));	
// 			if($q0->numRows()==0) {
// 				throw new Exception('No tiene cuenta y cedula no existe');
// 			}
// 			foreach($q0 as $r) {
// 				$load[]=array(
// 					'id_cuenta'=>$r['id_cuenta'],
// 					'id_proceso'=>$r['id_proceso']
// 				);
// 				$special = true;
// 			}
// 		} else {
// 			if($line['tipo_gestion']=='credito') {
// 				$id_proceso=107;
// 			}else if($line['tipo_gestion']=='tdc') {
// 				$id_proceso=108;
// 			} else {
// 				throw new Exception('Tipo gestion es nulo');
// 			}
			
// 			$q0 = $db->execute('get_cuenta',array($id_proceso,$line['cuenta']));
// 			if($q0->numRows()==0) {
// 				throw new Exception('Cuenta no existe');
// 			} else if($q0->numRows()>1) {
// 				echo 'cuenta duplicada ve! - ';
// 				print_r($q0->current());
// 				die();
// 			}
// 			$id_cuenta = ($q0->current())['id_cuenta'];
		
// 			$load[]=array(
// 				'id_cuenta'=>$id_cuenta,
// 				'id_proceso'=>$id_proceso,
// 			);

// 		}
// 		//continue;

// 		/*
// 		if($line['cuenta'][0]=='0')
// 			throw new Exception('Cuenta inicia con 0');
// 		*/
		
// 		/*
// 		if(in_array($line['id_llamada'],$idsLlamadas))
// 			throw new Exception('Id llamada duplicado');
// 		$idsLlamadas[]=$line['id_llamada'];
// 		*/
		
// 		$mail = get_mail_from_repo(($line['CEDULA']));
		
// 		foreach($load as $l) {
// 			$row = array(
// 				'id_cuenta' => $l['id_cuenta'],
// 				'fecha_inicio' => '\''.($line['fecha_gestion']).'\'',
// 				'telh_id' => (($line['id_llamada']=='')?'NULL':'\''.$line['id_llamada'].'\''),
// 				'user_name' => (($line['user_name']=='')?'NULL':'\''.$line['user_name'].'\''),
// 				'tel_number' => (($line['telefono_gestion']=='')?'NULL':'\''.$line['telefono_gestion'].'\''),
// 				'id_tipificacion' => array_search(($line['tipificacion recapt']),$tipificaciones_recappt),
// 				'fecha_fin' => '\''.($line['fecha_gestion']).'\'',
// 				//'servidor' => ($special?'\'MIGRACION '.date('Y-m-d').' POR CEDULA\'':'\'MIGRACION '.date('Y-m-d').'\''),
// 				'servidor' => '\'MIGRACION '.date('Y-m-d').'\'',
// 				'observacion' => '\''.$db->escape($line['observacion']).'\'',
// 				'id_gestion_ref' => 'NULL',
// 				'fecha_compromiso' => (($line['fecha_compromiso']=='')?'NULL':'\''.$line['fecha_compromiso'].'\''),
// 				'monto_compromiso' => (($line['monto_compromiso']=='')?'NULL':'\''.$line['monto_compromiso'].'\''),
// 				'ip_cliente' => 'NULL',
// 				'email' => $mail==''?'null':'\''.$db->escape($mail).'\''
// 			);
// 			echo '('.$lineN.' / '.$total.') Insertando: ';
// 			print_r($row);
// 			echo "\r\n\r\n";
			
// 			$db->startTransaction();
// 			try{
// 				$q = 'INSERT INTO gestiones.gestion('.implode(',',array_keys($row)).')VALUES('.implode(',',$row).') RETURNING id_gestion';
// 				$q0 = $db->query($q);
// 				if (!$q0) throw new exception('Error_insert_gestion');
// 				$id_gestion = $db->fetchOne($q0)['id_gestion'];
				
// 				//insert into gestion_custom_fields
// 				$id_tip_jep = array_search(trim($line['tipificacion jep']),$tipificaciones_jep);
// 				$q = 'INSERT INTO gestiones.gestion_custom_fields(id_gestion,field,value)VALUES('.$id_gestion.',\'id_jep_tipificacion\',\''.$id_tip_jep.'\')';
// 				$q0 = $db->query($q);
// 				if (!$q0) throw new exception('Error_insert_gestion_jep');
// 				$result['GESTIONES_INSERTADAS']['count']++;
// 				// $result['GESTIONES_INSERTADAS'][]=trim($line[1]);
// 				$db->commit();
// 			}catch(Exception $ex){
// 				echo 'EROOOOORRR ----------'."\n";
// 				print_r($line);
// 				var_dump($ex);
// 				$db->rollback();
// 				die();
// 			}



// 		}
		
		
		
		
// 	}catch(Exception $e) {
// 		$errores++;
// 		$countErrors[$line['tipo_gestion']][$e->getMessage()]++;
// 		$l = array(
// 			$lineN,
// 			$line['cuenta'],
// 			$e->getMessage()
// 		);
// 		echo implode("\t",$l)."\r\n";
// 		//echo 'Linea '.$lineN.' > '.$e->getMessage();
// 		//echo "\r\n";
// 	}
// }

// print_r($countErrors);
// echo "\r\n";
// echo 'Total cuentas: '.$lineN."\n";
// echo 'Total errores: '.$errores."\n";
// print_r($cDups);
// die();
