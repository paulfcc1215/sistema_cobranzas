<?php

	error_reporting(0);
	
    require '/opt/www/cobranzas/config.php';

    $db = DB::getInstance();


	// truncate tabla dashboards.cartera_portoaguas
	$db->query('TRUNCATE TABLE dashboards.cartera_portoaguas');

	//get last process
    $q = 'SELECT max(id_proceso) AS id_proceso FROM campanas.proceso WHERE id_campana=17 AND status=\'1\'';
    $q0 = $db->query($q);
    $id_proceso = $db->fetchOne($q0)['id_proceso'];

	// quemado para mostrar dashboard mes seleccionado
	// $id_proceso = 157;

	$q = 'SELECT 
		pe.identificacion,c.id_carga,c.id_carga_valor_actual,
		c.id_cuenta,c.cuenta,c.valor_original,c.valor_actual,
		c.fecha_creacion,
		null AS ciudadela
    FROM cuentas.cuenta c
        JOIN personas.persona pe ON(pe.id_persona=c.id_deudor)
        JOIN campanas.proceso p ON(c.id_proceso=p.id_proceso)
    WHERE 
        c.id_proceso='.$id_proceso;

	try{

		$db->startTransaction();

		$q2 = 'SELECT id_carga,campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 AND id_carga=$2';
		$db->prepare('q2',$q2);
		$cartera = array();
        $r = $db->query($q);
        while ($qa0 = $db->fetchOne($r)){

			$q0 = $db->execute('q2',array($qa0['id_cuenta'],$qa0['id_carga']));
			$aux2 = array();
			while($row = $db->fetchOne($q0)){
				$aux2[str_replace(' ','_',$row['campo'])] = trim($row['valor']);
			}

			if ($aux2['fecha_de_facturacion']=='' || $aux2['fecha_de_facturacion']=='null' || is_null($aux2['fecha_de_facturacion'])){
				$result['0-30']++;
				$dias_vencidos=0;
				$rango_dias_vencidos = '1. De 0 a 30 días';
			}else{
				// fjjf - weruyf48jf3498
				// 11/10/2021 - agregado dos lineas para eliminar " 0.0.0.0" en las fechas de metadata porque no sé por que diablos siguen apareciendo
				$aux2['fecha_de_facturacion'] = str_replace(' 0.0.0.0','',$aux2['fecha_de_facturacion']);
				$aux2['fecha_emision_mes'] = str_replace(' 0.0.0.0','',$aux2['fecha_emision_mes']);
				// --------- fin weruyf48jf3498
				
				
				$fecha_creacion = date('Y-m-d',strtotime($qa0['fecha_creacion']));
				$fecha_facturacion = new DateTime(dmy2ymd($aux2['fecha_de_facturacion']));
				$fecha_asignacion = new DateTime($fecha_creacion);

				$intvl = $fecha_asignacion->diff($fecha_facturacion);

				$dias_vencidos = $intvl->days;
				if ($intvl->days>=0 && $intvl->days<=30){
					// $result['0-30']++;
					$rango_dias_vencidos = '1. De 0 a 30 días';
				}
				if ($intvl->days>30 && $intvl->days<=60){
					// $result['31-60']++;
					$rango_dias_vencidos = '2. De 31 a 60 días';
				}
				if ($intvl->days>60 && $intvl->days<=180){
					// $result['61-180']++;
					$rango_dias_vencidos = '3. De 61 a 180 días';
				}
				if ($intvl->days>180 && $intvl->days<=360){
					// $result['181-360']++;
					$rango_dias_vencidos = '4. De 181 a 360 días';
				}
				if ($intvl->days>360 && $intvl->days<=720){
					// $result['361-720']++;
					$rango_dias_vencidos = '5. De 361 a 720 días';
				}
				if ($intvl->days>720){
					// $result['>720']++;
					$rango_dias_vencidos = '6. Mayor a 720 días';
				}
			}

			$aux['dias_vencidos'] = $dias_vencidos;
			$aux['rango_dias_vencidos'] = $rango_dias_vencidos;
			$aux['id_cuenta'] = $qa0['id_cuenta'];
			$aux['identificacion'] = $qa0['identificacion'];
			$aux['valor_original'] = $qa0['valor_original'];
			$aux['fecha_asignacion'] = date('d/m/Y',strtotime($qa0['fecha_creacion']));

			$aux = array_merge($aux,$aux2);

			foreach ($aux as $k => &$v){
                $v = str_replace(array("'","\\","{","}","[","]","(",")","null"),"",$v);
            }
			// insert en tabla dashboards.cartera_portoaguas
            $q = 'INSERT INTO dashboards.cartera_portoaguas('.implode(',',array_keys($aux)).') VALUES(\''.implode('\',\'',$aux).'\')';
            $db->query($q);

		}
		$db->commit();
		
		// $f = fopen('dataset_cartera.txt', 'a');
		// fwrite($f,json_encode($cartera));
		// fwrite ($f, implode(',',array_keys($cartera[0]))."\n" );
		// foreach ($cartera as $l){
		// 	fwrite($f, implode(',',$l)."\n" );
		// }
		// fclose($f);
		// die();

	}catch(Exception $ex){
		$mail = new Helpers_Mail();
        $to = array(
            'paul.cedeno@recappt.com',
            'artura.villafuerte@grupocant.com'
        );
        $mail->add_attachment($file_zip);
        $subject = 'NOTIFICACION AUTOMATICA - DASHBOARD CARTERA PORTOAGUAS';
        $content = 'Ha ocurrido un error al generar data para consumo dashboard PORTOAGUAS.<br><br>Error: '.$ex->getMessage();
        $mail->sendMail($to,$subject,$content);
        $db->rollback();
	}

	echo 'Proceso ejecutado';
	die();


    function dmy2ymd($source) {
        $aux=preg_replace('#[\d]#','',$source);
        $sep=$aux[0];
        $source=explode($sep,$source);
        $ret=implode('-',array($source[2],sprintf("%02d",$source[1]),sprintf("%02d",$source[0])));
        return $ret;
        
    }