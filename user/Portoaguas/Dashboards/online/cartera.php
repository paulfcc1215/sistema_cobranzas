<?php

    header('Content-Type: application/json; charset=utf-8');
    require '../../../../config.php';
    $db = DB::getInstance();

    $q = 'SELECT 
        dias_vencidos AS "dias_vencidos",
        rango_dias_vencidos AS "rango_dias_vencidos",
        id_cuenta AS "id_cuenta",
        identificacion AS "identificacion",
        valor_original AS "valor_original",
        fecha_asignacion AS "fecha_asignacion",
        cuenta AS "CUENTA",
        catastro AS "CATASTRO",
        tipo_consumo AS "TIPO_CONSUMO",
        servicio AS "SERVICIO",
        estado AS "ESTADO",
        reclamo AS "RECLAMO",
        num_medidor AS "NUM_MEDIDOR",
        facturas_vencidas AS "FACTURAS_VENCIDAS",
        obligaciones_corrientes AS "OBLIGACIONES_CORRIENTES",
        obligaciones_vencidas AS "OBLIGACIONES_VENCIDAS",
        deuda_portoaguas AS "DEUDA_PORTOAGUAS",
        saldo_convenio AS "SALDO_CONVENIO",
        fecha_de_facturacion AS "fecha de facturacion",
        vencimiento_factura AS "VENCIMIENTO_FACTURA",
        fecha_emision_mes AS "fecha_emision_mes",
        ciudadela AS "CIUDADELA"
    FROM dashboards.cartera_portoaguas';
    $q0 = $db->query($q);

    if ($db->numRows($q0)==0){
        echo 'No existen registros';
        die();
    }
    
    echo json_encode($db->fetchAll($q0));
    die();