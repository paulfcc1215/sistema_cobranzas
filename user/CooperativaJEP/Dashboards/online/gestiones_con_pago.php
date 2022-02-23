<?php

    require '../../../../config.php';
    $db = DB::getInstance();
    header('Content-Type: application/json; charset=utf-8');

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




    
	// create a temporary table with all gestiones
	$tmpTblGestiones = 't'.uniqid();
	$tmpTblPagos = 't'.uniqid();
	$tmpTblNumGestiones = 't'.uniqid();
	
    // get gestiones
    $q = 'CREATE TEMPORARY TABLE '.$tmpTblGestiones.' AS SELECT
        CASE
            WHEN usuario.nombre_completo = \'GEOVANY RAMOS\' THEN \'GEOVANY RAMOS / MASIVOS\'
            ELSE  usuario.nombre_completo
        END AS nombre_completo,
        gestion.*, tipificacion.descripcion as tipificacion,
                    
        CASE
            WHEN tipificacion_metadata.contactabilidad = \'1\' THEN \'CONTACTADO\'
            ELSE \'NO CONTACTADO\'
        END AS contactabilidad,
        tipificacion_metadata.tipo_contactabilidad,
        persona.identificacion,
        campana.campana

        FROM cobranzas.gestiones.gestion AS gestion
        JOIN cobranzas.auth.auth_usuarios AS usuario ON gestion.user_name = usuario.usr_logname
        JOIN cobranzas.cuentas.cuenta AS cuenta ON cuenta.id_cuenta = gestion.id_cuenta

        JOIN cobranzas.personas.persona AS persona ON persona.id_persona = cuenta.id_deudor

        JOIN cobranzas.campanas.proceso AS proceso ON proceso.id_proceso = cuenta.id_proceso
        JOIN cobranzas.campanas.campana AS campana ON campana.id_campana = proceso.id_campana
        JOIN cobranzas.gestiones.tipificacion AS tipificacion ON tipificacion.id_tipificacion = gestion.id_tipificacion
        JOIN cobranzas.gestiones.tipificacion_metadata AS tipificacion_metadata ON tipificacion_metadata.id_tipificacion = tipificacion.id_tipificacion
        WHERE 
            proceso.id_proceso IN ('.$id_proceso_creditos.','.$id_proceso_TDC.')';
    $db->query($q);
	
	// create an index on tmp gestiones
	$db->query('CREATE INDEX ON '.$tmpTblGestiones.' USING BTREE("id_cuenta")');
	
	// create a temporary table with number of gestiones per id_cuenta
	$db->query('CREATE TEMPORARY TABLE '.$tmpTblNumGestiones.' AS
	SELECT id_cuenta,COUNT(*) AS _gestiones FROM '.$tmpTblGestiones.' GROUP BY 1
	');
	
	// create an index on tbl num gestiones
	$db->query('CREATE INDEX ON '.$tmpTblGestiones.' USING BTREE("id_cuenta")');
	
	// now create a temporary table with all pagos
	$db->query('CREATE TEMPORARY TABLE '.$tmpTblPagos.' AS
		SELECT id_cuenta,ABS(SUM(diferencia)) AS _pago FROM
		cuentas.cuenta_actualizacion
		WHERE id_cuenta IN (
			SELECT DISTINCT id_cuenta FROM '.$tmpTblGestiones.'
		) AND tipo_actualizacion=\'PAGO\' GROUP BY 1
	');
	
    $result = array();
	foreach($db->query('
		SELECT
			*
		FROM
			'.$tmpTblGestiones.'
		LEFT JOIN '.$tmpTblPagos.' USING (id_cuenta)
		LEFT JOIN '.$tmpTblNumGestiones.' USING (id_cuenta)
		'
		) as $qa0) {
			if(is_null($qa0['_pago']))
				$qa0['_pago']=0;

			$qa0['pago']=$qa0['_pago']/$qa0['_gestiones'];
			
			unset($qa0['_pago']);
			unset($qa0['_gestiones']);
        $result[]=$qa0;
    }
    // print_arr($result);
    // die();
	/*
	$sum = 0;
	foreach($result as $r) {
		$sum+=$r['pago'];
	}
	echo $sum;
	die();
	*/
    echo json_encode($result);
    die();