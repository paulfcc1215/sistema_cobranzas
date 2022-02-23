<?php

    header('Content-Type: application/json; charset=utf-8');
    require '../../../../config.php';

    $db = DB::getInstance();

    // último proceso
    $q = 'SELECT max(id_proceso) AS id_proceso FROM campanas.proceso WHERE id_campana=18 AND status=\'1\'';
    $q0 = $db->query($q);
    $id_proceso = $db->fetchOne($q0)['id_proceso'];
    
    // quemado para enero 2022
    // $id_proceso = 158;

    $q = 'SELECT
        c.id_cuenta,c.cuenta,ABS(ca.diferencia) as pago,ca.fecha_actualizacion,p.identificacion
    FROM cuentas.cuenta c
        JOIN personas.persona p ON(p.id_persona=c.id_deudor)
        JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=c.id_cuenta)
    WHERE 
        c.id_proceso = '.$id_proceso.' AND 
        ca.tipo_actualizacion=\'PAGO\' AND 
        ca.fecha_actualizacion = (SELECT max(fecha_actualizacion) FROM cuentas.cuenta_actualizacion WHERE id_cuenta=ca.id_cuenta AND tipo_actualizacion=\'PAGO\')
    ORDER BY 
        ca.id_cuenta,ABS(ca.diferencia) DESC,ca.fecha_actualizacion DESC';

    $q0 = $db->query($q);
    $result = array();
    $cuenta_procesada = array();
    while ($qa0 = $db->fetchOne($q0)){
        // if ($qa0['id_cuenta']!=2192720) continue;
        if (!in_array($qa0['id_cuenta'],$cuenta_procesada)){
            $cuenta_procesada[] = $qa0['id_cuenta'];
            $result[] = $qa0;
        }
    }

    echo json_encode( $result, JSON_PRETTY_PRINT );
    die();