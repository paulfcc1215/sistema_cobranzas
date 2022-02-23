<?php

header('Content-Type: application/json; charset=utf-8');

$connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=orangeDragon$2017";
$conn = pg_connect($connStr);

$query = "SELECT
        CASE
            WHEN usuario.nombre_completo = 'GEOVANY RAMOS' THEN 'GEOVANY RAMOS / MASIVOS'
            ELSE  usuario.nombre_completo
        END AS nombre_completo,
        gestion.*, tipificacion.descripcion as tipificacion,
                    
        CASE
            WHEN tipificacion_metadata.contactabilidad = '1' THEN 'CONTACTADO'
            ELSE  'NO CONTACTADO'
        END AS contactabilidad,
        tipificacion_metadata.tipo_contactabilidad,
        persona.identificacion

        from cobranzas.gestiones.gestion as gestion
        join cobranzas.auth.auth_usuarios as usuario on gestion.user_name = usuario.usr_logname
        join cobranzas.cuentas.cuenta as cuenta on cuenta.id_cuenta = gestion.id_cuenta

        join cobranzas.personas.persona as persona on persona.id_persona = cuenta.id_deudor

        join cobranzas.campanas.proceso as proceso on proceso.id_proceso = cuenta.id_proceso
        join cobranzas.campanas.campana as campana on campana.id_campana = proceso.id_campana
        join cobranzas.gestiones.tipificacion as tipificacion on tipificacion.id_tipificacion = gestion.id_tipificacion
        join cobranzas.gestiones.tipificacion_metadata as tipificacion_metadata on tipificacion_metadata.id_tipificacion = tipificacion.id_tipificacion
        where 
            proceso.id_proceso = (
                select max(proceso.id_proceso)
                from cobranzas.campanas.proceso as proceso 
                where proceso.id_campana = 17
            )
        ";

$result = pg_query($conn, $query);
$result = pg_fetch_all($result);
 
echo json_encode( $result );
die();