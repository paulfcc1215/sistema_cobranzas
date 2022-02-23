<?php

require '../../../config.php';

// header('Content-Type: application/json; charset=utf-8');

$db=DB::getInstance();
$q = 'SELECT
    c.id_cuenta,c.cuenta,(ca.diferencia)*-1 as pago,ca.fecha_actualizacion as fecha_pago,p.identificacion
    FROM cuentas.cuenta c
        JOIN personas.persona p ON(p.id_persona=c.id_deudor)
        JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=c.id_cuenta)
    WHERE c.id_proceso=119 AND ca.tipo_actualizacion=\'PAGO\'';
$q0 = $db->query($q);

// print_arr($db->fetchOne($q0));
// die();

echo json_encode($db->fetchAll($q0));

die();