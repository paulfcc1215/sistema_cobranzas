<?php

    header('Content-Type: application/json; charset=utf-8');

    // $connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=orangeDragon$2017";
    $connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=postgres";
    $conn = pg_connect($connStr);

    //get last process
    $q = 'SELECT max(id_proceso) AS id_proceso FROM campanas.proceso WHERE id_campana=17 AND status=\'1\'';
    $id_proceso = pg_fetch_assoc(pg_query($conn,$q))['id_proceso'];

    // quemado para mostrar dashboard mes seleccionado
    // $id_proceso = 157;

    $query = 'SELECT 
                CASE
                    WHEN usuario.nombre_completo = \'GEOVANY RAMOS\' THEN \'GEOVANY RAMOS / MASIVOS\'
                    ELSE  usuario.nombre_completo
                END AS nombre_completo,
                gestion.*, tipificacion.descripcion AS tipificacion,
                CASE
                    WHEN tipificacion_metadata.contactabilidad = \'1\' THEN \'CONTACTADO\'
                    ELSE  \'NO CONTACTADO\'
                END AS contactabilidad,
                tipificacion_metadata.tipo_contactabilidad,
                persona.identificacion
            FROM cobranzas.gestiones.gestion AS gestion
                JOIN cobranzas.auth.auth_usuarios AS usuario ON gestion.user_name = usuario.usr_logname
                JOIN cobranzas.cuentas.cuenta AS cuenta ON cuenta.id_cuenta = gestion.id_cuenta
                JOIN cobranzas.personas.persona AS persona ON persona.id_persona = cuenta.id_deudor
                JOIN cobranzas.campanas.proceso AS proceso ON proceso.id_proceso = cuenta.id_proceso
                JOIN cobranzas.campanas.campana AS campana ON campana.id_campana = proceso.id_campana
                JOIN cobranzas.gestiones.tipificacion AS tipificacion ON tipificacion.id_tipificacion = gestion.id_tipificacion
                JOIN cobranzas.gestiones.tipificacion_metadata AS tipificacion_metadata ON tipificacion_metadata.id_tipificacion = tipificacion.id_tipificacion
            WHERE proceso.id_proceso='.$id_proceso.'
            ORDER BY gestion.fecha_inicio DESC';

    $result = pg_query($conn, $query);
    $result = pg_fetch_all($result);
    
    echo json_encode( $result );
    die();