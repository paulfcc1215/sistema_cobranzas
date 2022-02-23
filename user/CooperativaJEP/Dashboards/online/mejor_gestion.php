<?php

	require '../../../../config.php';
	header('Content-Type: application/json; charset=utf-8');

	/*
	[
		{
			"nombre_completo": "GEOVANY RAMOS / MASIVOS",
			"id_gestion": "3706864",
			"id_cuenta": "1896757",
			"fecha_inicio": "2021-10-07 13:17:00",
			"telh_id": "",
			"user_name": "gramos",
			"tel_number": "0998599573",
			"id_tipificacion": "260",
			"fecha_fin": null,
			"servidor": null,
			"observacion": "ENVIO IVR - 0998599573",
			"id_gestion_ref": null,
			"fecha_compromiso": null,
			"monto_compromiso": null,
			"ip_cliente": null,
			"email": null,
			"tipificacion": "IVR",
			"contactabilidad": "NO CONTACTADO",
			"tipo_contactabilidad": "NINGUNO",
			"identificacion": "1704309887",
			"campana": "COBRANZAS CREDITOS JEP"
		}
	]
	*/

	function fixFormat($d) {
		$fields=array(
			'nombre_completo',
			'id_gestion',
			'id_cuenta',
			'fecha_inicio',
			'telh_id',
			'user_name',
			'tel_number',
			'id_tipificacion',
			'fecha_fin',
			'servidor',
			'observacion',
			'id_gestion_ref',
			'fecha_compromiso',
			'monto_compromiso',
			'ip_cliente',
			'email',
			'tipificacion',
			'contactabilidad',
			'tipo_contactabilidad',
			'identificacion',
			'campana',
			'pagos',
			'total_gestiones',
			'id_campana'
		);
		$aux=array();
		foreach($fields as $f) {
			$aux[$f]=$d[$f];
		}
		return $aux;
	}

	$db = DB::getInstance();

	// IDS CAMPAÑAS
	// 15 CREDITOS
	// 16 TDC

	// get proceso vigente CREDITOS
    $q = 'SELECT MAX(proceso.id_proceso) AS id_proceso
    FROM cobranzas.campanas.proceso AS proceso 
    WHERE proceso.id_campana=15';
    $q0 = $db->query($q);
    $id_proceso_creditos = $db->fetchOne($q0)['id_proceso'];

    // get proceso vigente TDC
    $q = 'SELECT MAX(proceso.id_proceso) AS id_proceso
    FROM cobranzas.campanas.proceso AS proceso 
    WHERE proceso.id_campana=16';
    $q0 = $db->query($q);
	$id_proceso_TDC = $db->fetchOne($q0)['id_proceso'];
    

    


	//Cambio manual
	// $id_proceso_creditos = 164;
	// $id_proceso_TDC = 165;




	// get campaana names
	$campNames = array();
	foreach($db->query('SELECT * FROM campanas.campana WHERE id_campana IN (15,16)') as $c) {
		$campNames[$c['id_campana']]=$c['campana'];
	}

	// create temporary table for pagos
	$pagosTmpTbl = 't'.uniqid();
	$db->query('CREATE TEMPORARY TABLE '.$pagosTmpTbl.' (
		id_cuenta int,
		diferencia float,
		tipo_actualizacion text,
		identificacion text,
		id_proceso int,
		id_campana int,
		id serial not null primary key
	)');

	// $db->prepare('getPagos','SELECT ABS(SUM(diferencia)) AS pagos FROM '.$pagosTmpTbl.' WHERE identificacion=$1 AND id_campana=$2');
	$db->prepare('getPagos','SELECT id,ABS((diferencia)) AS pagos FROM '.$pagosTmpTbl.' WHERE identificacion=$1 AND id_campana=$2 AND id_cuenta=$3');

	$db->query('INSERT INTO '.$pagosTmpTbl.'  SELECT
			id_cuenta,diferencia,tipo_actualizacion,p.identificacion,pr.id_proceso,pr.id_campana
		FROM cuentas.cuenta_actualizacion ca
			JOIN cargas.carga u USING (id_carga)
			JOIN campanas.proceso pr USING (id_proceso)
			JOIN cuentas.cuenta c USING (id_cuenta)
			JOIN personas.persona p ON (c.id_deudor = p.id_persona)
		WHERE ca.id_carga IN (
			SELECT id_carga FROM cargas.carga WHERE id_proceso IN ('.$id_proceso_creditos.','.$id_proceso_TDC.')
		)
		AND tipo_actualizacion=\'PAGO\'
	');

	$db->query('CREATE INDEX ON '.$pagosTmpTbl.' USING BTREE("identificacion")');


	// get gestiones
	$query = 'SELECT
		CASE
			WHEN usuario.nombre_completo = \'GEOVANY RAMOS\' THEN \'GEOVANY RAMOS / MASIVOS\'
			ELSE  usuario.nombre_completo
		END AS nombre_completo,
		gestion.*, tipificacion.descripcion as tipificacion,
		CASE
			WHEN tipificacion_metadata.contactabilidad = \'1\' THEN \'CONTACTADO\'
			ELSE \'NO CONTACTADO\'
		END AS contactabilidad,
		tipificacion.peso as t_peso,
		tipificacion_metadata.tipo_contactabilidad,
		persona.identificacion,
		campana.campana,
		campana.id_campana

		FROM
		cobranzas.gestiones.gestion AS gestion
		JOIN cobranzas.auth.auth_usuarios AS usuario ON gestion.user_name = usuario.usr_logname
		JOIN cobranzas.cuentas.cuenta AS cuenta ON cuenta.id_cuenta = gestion.id_cuenta

		JOIN cobranzas.personas.persona AS persona ON persona.id_persona = cuenta.id_deudor

		JOIN cobranzas.campanas.proceso AS proceso ON proceso.id_proceso = cuenta.id_proceso
		JOIN cobranzas.campanas.campana AS campana ON campana.id_campana = proceso.id_campana
		JOIN cobranzas.gestiones.tipificacion AS tipificacion ON tipificacion.id_tipificacion = gestion.id_tipificacion
		JOIN cobranzas.gestiones.tipificacion_metadata AS tipificacion_metadata ON tipificacion_metadata.id_tipificacion = tipificacion.id_tipificacion
		
		WHERE 
		
		proceso.id_proceso IN ('.$id_proceso_creditos.','.$id_proceso_TDC.')
		-- AND persona.identificacion=\'0928939453\'
		ORDER BY identificacion, t_peso DESC, gestion.fecha_inicio DESC';

	$currentId = null;
	$gestionesGrp = array();
	$result=array();
	foreach($db->query($query) as $gestion) {
		$gestionesGrp[$gestion['id_campana']][$gestion['identificacion']][$gestion['id_cuenta']][] = $gestion;
	}
	unset($ptr);


	$aux_p = 0;
	$numGestiones=array();
	$refs=array();
	foreach($gestionesGrp as $id_campana=>$gestionesXCedulaCuenta) {
		foreach($gestionesXCedulaCuenta as $cedula=>$gestionesXCuenta) {
			foreach($gestionesXCuenta as $id_cuenta=>$gestiones) {
				$numGestiones[$cedula]+=count($gestiones);
				$mejorGestion = $gestiones[0];
				$mejorGestion['pagos']=0;
				$q0=$db->execute('getPagos',array($mejorGestion['identificacion'],$mejorGestion['id_campana'],$id_cuenta));
				foreach($q0 as $q) {
					$mejorGestion['pagos']+=$q['pagos'];
					$db->query('DELETE FROM '.$pagosTmpTbl.' WHERE id='.$q['id']);
				}
				$mejorGestion['total_gestiones']=0;
				
				$aux = fixFormat($mejorGestion);
				$refs[$cedula][]=&$aux;
				$result[]=&$aux;
				unset($aux);
			}
		}
	}



	foreach($numGestiones as $cedula=>$nG) {
		$kPtr=0;
		$numRefs = count($refs[$cedula]);
		for($nG;$nG>0;$nG--) {
			$refs[$cedula][$kPtr]['total_gestiones']++;
			$kPtr++;
			if($kPtr==$numRefs)
				$kPtr=0;
		}
	}


	echo json_encode($result);