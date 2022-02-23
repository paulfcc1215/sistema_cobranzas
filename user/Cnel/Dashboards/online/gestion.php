<?php

    header('Content-Type: application/json; charset=utf-8');
    require '../../../../config.php';
    $db = DB::getInstance();

    $q = 'SELECT * FROM dashboards.gestiones_cnel';
    $q0 = $db->query($q);
    
    echo json_encode($db->fetchAll($q0));