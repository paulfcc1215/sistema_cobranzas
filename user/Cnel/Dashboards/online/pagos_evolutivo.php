<?php

header('Content-Type: application/json; charset=utf-8');

// $connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=orangeDragon$2017";
$connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=postgres";
$conn = pg_connect($connStr);

// get rpoceso vigente
// último proceso
$q = 'SELECT max(id_proceso) AS id_proceso FROM campanas.proceso WHERE id_campana=18 AND status=\'1\'';
$id_proceso = pg_fetch_assoc(pg_query($q))['id_proceso'];

// quemado para enero 2022
// $id_proceso = 158;

$query =   'SELECT
            c.id_cuenta,c.cuenta,ABS(ca.diferencia) as pago,ca.fecha_actualizacion as fecha_pago,p.identificacion
            FROM cuentas.cuenta c
                JOIN personas.persona p ON(p.id_persona=c.id_deudor)
                JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=c.id_cuenta)
            WHERE 
                c.id_proceso='.$id_proceso.'
                AND ca.tipo_actualizacion=\'PAGO\'
            ORDER BY 
                ca.fecha_actualizacion ASC';

$result = pg_query($conn, $query);
$result = pg_fetch_all($result);


$fechas = array();
foreach ($result as $value) {
    $numDay = date('N', strtotime($value['fecha_pago']));
    if($numDay != 6 && $numDay != 7){

        if(array_key_exists($value['fecha_pago'], $fechas)){
            $fechas[$value['fecha_pago']]++;
        }else{
            $fechas[$value['fecha_pago']] = 1;
        }
    }
}

$now = date('Y-m-d');

// $now = date( 'Y-m-d', strtotime($now."- 1 month") );

$numDays = date( 't', strtotime($now) );

$dates = array();
$initDay = date( 'Y-m', strtotime($now) ).'-01';
for ($i=0; $i < $numDays; $i++) { 
    $numDate = date('Y-m-d', strtotime($initDay. ' + '.$i.' days'));
    $numDay = date('N', strtotime( $numDate ));
    //if($numDay != 6 && $numDay != 7){
        $dates[] = $numDate;
    //}
}


for ($i=0; $i < sizeof($dates); $i++) { 
    $existsDate = false;
    foreach ($fechas as $key => $value) {
        $fecha = date( 'Y-m-d', strtotime($key) );
        if($dates[$i] == $fecha){
            $existsDate = true;
        }
    }
    if(!$existsDate){ 
        $result[] = array(
            "id_cuenta" => null,
            "cuenta" => null,
            "pago" => null,
            "fecha_pago" => $dates[$i],
            "identificacion" => null
        );
        $fechas[$dates[$i]] = 1;
    }
}

$meta = 1200000;
$meta_diaria = $meta/sizeof($fechas);

for ($i=0; $i < sizeof($result); $i++) { 
    $result[$i]['meta'] = $meta;
    $result[$i]['meta_diaria'] = null;
    $result[$i]['fecha_meta_diaria'] = null;
    
    foreach ($fechas as $key => $num) {
        if($result[$i]['fecha_pago'] == $key){
            $numDay = date('N', strtotime($result[$i]['fecha_pago']));
            $result[$i]['meta_diaria'] = $meta_diaria/$num;
            if($result[$i]['pago'] != null){   
                $result[$i]['fecha_meta_diaria'] = $result[$i]['fecha_pago'];
            }
        }
    }
}
 


echo json_encode( $result, JSON_PRETTY_PRINT );
die();