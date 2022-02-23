<?php

	require '../../../../config.php';
    $db = DB::getInstance();

    header('Content-Type: application/json; charset=utf-8');

	// IDS CAMPAÑAS
	// 15 CREDITOS
	// 16 TDC

	// get proceso vigente CREDITOS	
	$q = 'SELECT MAX(proceso.id_proceso) AS id_proceso FROM cobranzas.campanas.proceso WHERE id_campana=15 AND status=\'1\'';
    $q0 = $db->query($q);
    $id_proceso_creditos = $db->fetchOne($q0)['id_proceso'];
	

    // get proceso vigente TDC
    $q = 'SELECT MAX(proceso.id_proceso) AS id_proceso FROM cobranzas.campanas.proceso WHERE id_campana=16 AND status=\'1\'';
    $q0 = $db->query($q);
    $id_proceso_TDC = $db->fetchOne($q0)['id_proceso'];


	//Cambio manual
	// $id_proceso_creditos = 164;
	// $id_proceso_TDC = 165;
	

	$cartera = array();
	// $procesos_TDC_aux = array();
	// foreach ($procesos_TDC as $value) {
	// 	$procesos_TDC_aux[] = $value['id_proceso'];
	// }
	//$id_proceso_TDC = implode(',', $procesos_TDC_aux);


	try{

		// get cartera
		$q = 'SELECT 
				p.identificacion,
				c.id_carga,c.id_carga_valor_actual,
				c.id_cuenta,c.cuenta,c.valor_original,c.valor_actual,c.fecha_creacion,
				pr.id_campana,
				ca.campana, 
				pr.descripcion as periodo
			FROM cuentas.cuenta c
				JOIN personas.persona p ON(p.id_persona=c.id_deudor)
				JOIN campanas.proceso pr ON(pr.id_proceso=c.id_proceso)
				JOIN campanas.campana ca ON(ca.id_campana=pr.id_campana)
			WHERE c.id_proceso IN('.$id_proceso_creditos.','.$id_proceso_TDC.')';

		$db->prepare('q2','SELECT campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 ORDER BY id_carga DESC');
		
		$q0 = $db->query($q);
		// $cuentas_0_60 = array();
		while($qa0 = $db->fetchOne($q0)){

			//get datos no mapeados
			$q1 = $db->execute('q2',array($qa0['id_cuenta']));
			$aux2 = array();
			while($row = $db->fetchOne($q1)){
				if ($row['campo']=='Profesion'){
					$row['campo']='profesion';
				}
				if ($row['campo']=='profesion' && trim($row['valor'])==''){
					$row['valor']='DESCONOCIDO';
				}
				$aux2[$row['campo']] = trim($row['valor']);
			}
			// id_carga = 3229
			// print_arr($qa0);
			// print_arr($aux2);
			// die();

			//calculo de días vencidos para creditos
			if ($qa0['id_campana']==15){
				if ($aux2['Numero cuotas vencidas']=='' || $aux2['Numero cuotas vencidas']==0){
					$dias_vencidos = 0;
					// $cuentas_0_60[]=$qa0['cuenta'];
				}else{
					// if ($aux2['Numero cuotas vencidas']<3){
					// 	$cuentas_0_60[]=$qa0['cuenta'];
					// }
					$dias_vencidos = $aux2['Numero cuotas vencidas']*30;
				}
			}

			//calculo de días vencidos para TDC
			if ($qa0['id_campana']==16){
				if ($aux2['DIAS VENCIDOS']!=''){
					$dias_vencidos = $aux2['DIAS VENCIDOS'];
				}else{
					if ($aux2['PAGOS VENCIDOS']=='' || $aux2['PAGOS VENCIDOS']==0){
						$dias_vencidos = 0;
					}else{
						$dias_vencidos = $aux2['PAGOS VENCIDOS']*30;
					}
				}
			}
			
			if ($dias_vencidos<=60)
				$rango_dias_vencidos = '1. De 1 a 60 días';
			if ($dias_vencidos>60 && $dias_vencidos<=90)
				$rango_dias_vencidos = '2. De 61 a 90 días';
			if ($dias_vencidos>90 && $dias_vencidos<=120)
				$rango_dias_vencidos = '3. De 91 a 120 días';
			if ($dias_vencidos>120 && $dias_vencidos<=150)
				$rango_dias_vencidos = '4. De 121 a 150 días';
			if ($dias_vencidos>150 && $dias_vencidos<=180)
				$rango_dias_vencidos = '5. De 151 a 180 días';
			if ($dias_vencidos>180 && $dias_vencidos<=240)
				$rango_dias_vencidos = '6. De 181 a 240 días';
			if ($dias_vencidos>240 && $dias_vencidos<=360)
				$rango_dias_vencidos = '7. De 241 a 600 días';
			if ($dias_vencidos>360 && $dias_vencidos<=720)
				$rango_dias_vencidos = '8. De 361 a 720 días';
			if ($dias_vencidos>720)
				$rango_dias_vencidos = '9. Mayor a 720 días';
			
			$aux['CUENTA'] = $qa0['cuenta'];
			$aux['dias_vencidos'] = $dias_vencidos;
			$aux['rango_dias_vencidos'] = $rango_dias_vencidos;
			$aux['id_cuenta'] = $qa0['id_cuenta'];
			$aux['identificacion'] = $qa0['identificacion'];
			$aux['valor_original'] = $qa0['valor_original'];
			$aux['fecha_asignacion'] = date('d/m/Y',strtotime($qa0['fecha_creacion']));
			$aux['campana'] = $qa0['campana'];
			$aux['periodo'] = $qa0['periodo'];

			$cartera[]=array_merge($aux,$aux2);
			// print_arr($cartera);
			// die();
		}
		// echo json_encode($cartera);
		// die();
		
		// $file_handle = fopen('/tmp/cartera.txt', 'w');
		// fwrite($file_handle, implode(',',array_keys($cartera[0]))."\n" );
		// foreach ($cartera as $l){
		// 	fwrite($file_handle, implode(',',$l)."\n" );
		// }
		// fclose($file_handle);
		// die();

	}catch(Exception $ex){
		print_r($ex->getMessage());
		echo '<pre>';
		print_r($qa0);
		print_r($aux2);
		echo '</pre>';
		// ----- fin sdkjfh37rj
		die();
	}
	



	echo json_encode($cartera);
	die();