<?php
    header('Content-Type: application/json; charset=utf-8');

    $connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=orangeDragon$2017";
    $db = pg_connect($connStr);

	//get last process
	$q = 'SELECT max(id_proceso) FROM campanas.proceso WHERE id_campana=17 AND status=\'1\'';
	$id_proceso = pg_fetch_assoc(pg_query($q))['max'];

	// quemado para mostrar dashboard de octubre
	//$id_proceso = 127;

	$q = 'SELECT 
		pe.identificacion,c.id_carga,c.id_carga_valor_actual,
		c.id_cuenta,c.cuenta,c.valor_original,c.valor_actual,
		c.fecha_creacion,
		null as ciudadela
    FROM cuentas.cuenta c
        JOIN personas.persona pe ON(pe.id_persona=c.id_deudor)
        JOIN campanas.proceso p ON(c.id_proceso=p.id_proceso)
    WHERE 
        c.id_proceso='.$id_proceso;
	try{
		$q2 = 'SELECT campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 ORDER BY id_carga ASC';
		pg_prepare($db,'q2',$q2);
		$cartera = array();
        $r = pg_query($q);
        while($qa0 = pg_fetch_assoc($r)){

			// if ($qa0['cuenta']!='2539203') continue;

			$q0 = pg_execute($db,'q2',array($qa0['id_cuenta']));
			$aux2 = array();
			while($row = pg_fetch_assoc($q0)){
				$aux2[$row['campo']] = trim($row['valor']);
			}

			if ($aux2['fecha de facturacion']=='' || $aux2['fecha de facturacion']=='null' || is_null($aux2['fecha de facturacion'])){
				$dias_vencidos=0;
				$rango_dias_vencidos = '1. De 0 a 30 días';
			}else{
				// fjjf - weruyf48jf3498
				// 11/10/2021 - agregado dos lineas para eliminar " 0.0.0.0" en las fechas de metadata porque no sé por que diablos siguen apareciendo
				$aux2['fecha de facturacion'] = str_replace(' 0.0.0.0','',$aux2['fecha de facturacion']);
				$aux2['fecha_emision_mes'] = str_replace(' 0.0.0.0','',$aux2['fecha_emision_mes']);
				// --------- fin weruyf48jf3498
				
				
				$fecha_creacion = date('Y-m-d',strtotime($qa0['fecha_creacion']));
				$fecha_facturacion = new DateTime(dmy2ymd($aux2['fecha de facturacion']));
				$fecha_asignacion = new DateTime($fecha_creacion);

				// print_r($fecha_facturacion);
				// echo '<br>';
				// print_r($fecha_asignacion);

				$intvl = $fecha_asignacion->diff($fecha_facturacion);

				$dias_vencidos = $intvl->days;
				if ($intvl->days>=0 && $intvl->days<=30)
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
			}

			// print_r($dias_vencidos);
			// print_r($rango_dias_vencidos);

			// die();
			
			$aux['dias_vencidos'] = $dias_vencidos;
			$aux['rango_dias_vencidos'] = $rango_dias_vencidos;
			$aux['id_cuenta'] = $qa0['id_cuenta'];
			$aux['identificacion'] = $qa0['identificacion'];
			$aux['valor_original'] = $qa0['valor_original'];
			$aux['fecha_asignacion'] = date('d/m/Y',strtotime($qa0['fecha_creacion']));

			$cartera[]=array_merge($aux,$aux2);
			// print_r($cartera);
			// die();

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

	echo json_encode(array_values($cartera));
	die();


    function dmy2ymd($source) {
        $aux=preg_replace('#[\d]#','',$source);
        $sep=$aux[0];
        $source=explode($sep,$source);
        $ret=implode('-',array($source[2],sprintf("%02d",$source[1]),sprintf("%02d",$source[0])));
        return $ret;
        
    }