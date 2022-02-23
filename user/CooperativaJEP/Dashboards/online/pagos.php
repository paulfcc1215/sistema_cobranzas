<?php
    require '../../../../config.php';
    $db = DB::getInstance();

    header('Content-Type: application/json; charset=utf-8');

/*
    {
        "id_cuenta": "1903559",
        "cuenta": "83200002052",
        "pago": "0.03",
        "fecha_pago": "2021-10-01 05:19:09",
        "identificacion": "0106358203",
        "meta": null,
        "meta_diaria": null,
        "fecha_meta_diaria": null
    }
	*/
	
	
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





	// get campaign name from procesos
	$campaigns_name=array();
	foreach($db->query('SELECT c.*,p.id_proceso FROM campanas.proceso p JOIN campanas.campana c USING (id_campana) WHERE id_proceso IN ('.$id_proceso_creditos.','.$id_proceso_TDC.')') as $p) {
		$campaigns_name[$p['id_proceso']]=$p['campana'];
	}
	
    $query = 'SELECT
            c.id_proceso,c.id_cuenta,c.cuenta,(ca.diferencia)*-1 as pago,ca.fecha_actualizacion as fecha_pago,p.identificacion
        FROM cuentas.cuenta c
            JOIN personas.persona p ON(p.id_persona=c.id_deudor)
            JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=c.id_cuenta)
        WHERE c.id_proceso IN('.$id_proceso_creditos.','.$id_proceso_TDC.') AND ca.tipo_actualizacion=\'PAGO\'
        order by ca.fecha_actualizacion ASC';

    $result=array();
	foreach($db->query($query) as $r) {
		$r['meta']=null;
		$r['meta_diaria']=null;
		$r['fecha_meta_diaria']=null;
		$r['campana']=$campaigns_name[$r['id_proceso']];
		unset($r['id_proceso']);
		$result[]=$r;
	}

    echo json_encode( $result, JSON_PRETTY_PRINT );
