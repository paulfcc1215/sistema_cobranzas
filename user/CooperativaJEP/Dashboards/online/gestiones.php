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



    
    // get gestiones
    $q = 'SELECT
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

    $q0 = $db->query($q);
    $result = array();
    while ($qa0 = $db->fetchOne($q0)){
        $result[]=$qa0;
    }
    // print_arr($result);
    // die();
    echo json_encode($result);
    die();