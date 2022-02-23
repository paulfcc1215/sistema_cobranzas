<?php

	require '../../../config.php';

	header('Content-Type: application/json; charset=utf-8');

	$db=DB::getInstance();
	$q = 'SELECT 
		pe.identificacion,c.id_carga,c.id_carga_valor_actual,
		c.id_cuenta,c.cuenta,c.valor_original,c.valor_actual,
		null as ciudadela
		FROM cuentas.cuenta c
			JOIN personas.persona pe ON(pe.id_persona=c.id_deudor)
			JOIN campanas.proceso p ON(c.id_proceso=p.id_proceso)
		WHERE 
			c.id_proceso=(SELECT max(id_proceso) FROM campanas.proceso WHERE id_campana=17)';
	try{
		$db->prepare('q2','SELECT campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1');
		$cartera = array();
		foreach ($db->query($q) as $qa0){

			$q0 = $db->execute('q2',array($qa0['id_cuenta']));
			$aux2 = array();
			while($row = $db->fetchOne($q0)){
				$aux2[$row['campo']] = $row['valor'];
			}
			// print_arr($aux2);
			$fecha_emision = null;
			$fecha_facturacion = null;
			if ($aux2['fecha_emision_mes']=='null' || is_null($aux2['fecha_emision_mes'])){
				$aux2['fecha_emision_mes'] = null;
			}else{
				$fecha_emision = new DateTime(Helpers::dmy2ymd($aux2['fecha_emision_mes']));
			}
			if ($aux2['fecha de facturacion']=='null' || is_null($aux2['fecha de facturacion'])){
				$aux2['fecha de facturacion'] = null;
			}else{
				$fecha_facturacion = new DateTime(Helpers::dmy2ymd($aux2['fecha de facturacion']));
			}
			if (!is_null($fecha_emision) && !is_null($fecha_facturacion)){
				$intvl = $fecha_emision->diff($fecha_facturacion);
			}

			$dias_vencidos = $intvl->days;
			if ($intvl->days>0 && $intvl->days<=30)
				$rango_dias_vencidos = '1. De 0 a 30 días';
			if ($intvl->days>30 && $intvl->days<=60)
				$rango_dias_vencidos = '2. De 31 a 60 días';
			if ($intvl->days>60 && $intvl->days<=180)
				$rango_dias_vencidos = '3. De 61 a 180 días';
			if ($intvl->days>180 && $intvl->days<=360)
				$rango_dias_vencidos = '4. De 181 a 360 días';
			if ($intvl->days>360 && $intvl->days<=720)
				$rango_dias_vencidos = '5. De 361 a 720 días';
			if ($intvl->days>720)
				$rango_dias_vencidos = '6. Mayor a 720 días';
			
			$aux['dias_vencidos'] = $dias_vencidos;
			$aux['rango_dias_vencidos'] = $rango_dias_vencidos;
			$aux['id_cuenta'] = $qa0['id_cuenta'];
			$aux['identificacion'] = $qa0['identificacion'];
			$aux['valor_original'] = $qa0['valor_original'];

			$cartera[]=array_merge($aux,$aux2);

		}
		// print_arr($cartera);
		// die();

	}catch(Exception $ex){
		print_r($ex-getMessage());
		die();
	}

	echo json_encode(array_values($cartera));
	die();
