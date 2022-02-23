<?php

	header('Content-Type: application/json; charset=utf-8');
	require '../../../../config.php';
    $db = DB::getInstance();

    // último proceso
    $q = 'SELECT max(id_proceso) FROM campanas.proceso WHERE id_campana=18 AND status=\'1\'';
    $q0 = $db->query($q);
    $id_proceso = $db->fetchOne($q0)['max'];
	// quemado para enero 2022
	// $id_proceso=158;


	//get ultima carga de actualización
	$q = 'SELECT max(id_carga) AS id_carga FROM cargas.carga WHERE id_proceso='.$id_proceso.' AND tipo_carga=\'actualizacion\'';
	$q0 = $db->query($q);
    $id_carga = $db->fetchOne($q0)['id_carga'];


	// get cartera
	$q = 'SELECT 
		pe.identificacion,c.id_carga,c.id_carga_valor_actual,
		c.id_cuenta,c.cuenta,c.valor_original,c.valor_actual,
		c.fecha_creacion
    FROM cuentas.cuenta c
        JOIN personas.persona pe ON(pe.id_persona=c.id_deudor)
        JOIN campanas.proceso p ON(c.id_proceso=p.id_proceso)
    WHERE 
        c.id_proceso='.$id_proceso;
	try{
		$q2 = 'SELECT id_carga,campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 AND id_carga='.$id_carga;
		$db->prepare('q2',$q2);

		$cartera = array();
        $r = $db->query($q);
        while($qa0 = $db->fetchOne($r)){
			$q0 = $db->execute('q2',array($qa0['id_cuenta']));
			$aux2 = array();
			// $first=true;
			while($row = $db->fetchOne($q0)){
				// if ($first){
				// 	$id_carga = $row['id_carga'];
				// 	$first = false;
				// }
				// if ($id_carga !== $row['id_carga']) break;
				$aux2[$row['campo']] = trim($row['valor']);
			}
			$aux['rango_dias_vencidos'] = $aux2['data'];
			$aux['unidad_de_negocio'] = $aux2['Unidad de Negocio'];
			$aux['estado'] = $aux2['Estado'];
			$aux['tarifa'] = $aux2['Tarifa'];
			$aux['tipo_cliente'] = $aux2['Tipo Cliente'];
			$aux['CIUDADELA'] = $aux2['Descripcion_Canton'];
			$aux['id_cuenta'] = $qa0['id_cuenta'];
			$aux['CUENTA'] = $qa0['cuenta'];
			$aux['identificacion'] = $qa0['identificacion'];
			$aux['valor_original'] = $qa0['valor_original'];
			$aux['fecha_asignacion'] = date('d/m/Y',strtotime($qa0['fecha_creacion']));
			$cartera[]=$aux;
		}
		// $file_handle = fopen('/tmp/cartera.txt', 'w');
		// fwrite($file_handle, implode(',',array_keys($cartera[0]))."\n" );
		// foreach ($cartera as $l){
		// 	fwrite($file_handle, implode(',',$l)."\n" );
		// }
		// fclose($file_handle);
		// die();
	}catch(Exception $ex){
		print_r($ex->getMessage());
		// fjjf - sdkjfh37rj
		// mayor informacion del error
		// corregido linea print_r($ex->getMessage()); | Antes estaba print_r($ex-getMessage());
		echo '<pre>';
		print_r($qa0);
		print_r($aux2);
		echo '</pre>';
		// ----- fin sdkjfh37rj
		die();
	}
	// print_arr(count($cartera));
	echo json_encode(array_values($cartera));
	die();