<?php

    header('Content-Type: application/json; charset=utf-8');

    // $connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=orangeDragon$2017";
    $connStr = "host=10.1.210.26 port=5432 dbname=cobranzas user=postgres password=postgres";
    $conn = pg_connect($connStr);



    // IDS CAMPAÑAS
	// 15 CREDITOS
	// 16 TDC

    // get proceso vigente creditos
    $q = 'SELECT MAX(proceso.id_proceso) AS id_proceso FROM cobranzas.campanas.proceso WHERE id_campana=15 AND status=\'1\'';
    $q0 = pg_query($conn,$q);
    $id_proceso_creditos = pg_fetch_assoc($q0)['id_proceso'];

    // get proceso vigente TDC
    $q = 'SELECT MAX(proceso.id_proceso) AS id_proceso FROM cobranzas.campanas.proceso WHERE id_campana=16 AND status=\'1\'';
    $q0 = pg_query($conn,$q);
    $id_proceso_TDC = pg_fetch_assoc($q0)['id_proceso'];
    


    //Cambio manual
	// $id_proceso_creditos = 164;
	// $id_proceso_TDC = 165;





    $campanaName = null;
    $campaigns_name=array();
    $q0=pg_query($conn,'SELECT c.*,p.id_proceso FROM campanas.proceso p JOIN campanas.campana c USING (id_campana) WHERE id_proceso IN ('.$id_proceso_creditos.')');


    while($p=pg_fetch_assoc($q0)) {
        $campaigns_name[$p['id_proceso']]=array('name'=>$p['campana'],'id_campana'=>$p['id_campana']);
    }

    $query = 'SELECT
            c.id_proceso,c.id_cuenta,c.cuenta,ABS(ca.diferencia) as pago,DATE(ca.fecha_actualizacion) as fecha_pago,p.identificacion
        FROM
            cuentas.cuenta c
            JOIN personas.persona p ON(p.id_persona=c.id_deudor)
            JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=c.id_cuenta)
        WHERE
            c.id_proceso in (
                '.implode(',',array_keys($campaigns_name)).'
            )
            AND ca.tipo_actualizacion=\'PAGO\'
        ORDER BY ca.fecha_actualizacion ASC';

    $q0 = pg_query($conn, $query);
    $result = array();
    while($qa0=pg_fetch_assoc($q0)) {
        $qa0['campana']=$campaigns_name[$qa0['id_proceso']]['name'];
        $campanaName = $qa0['campana'];
        $qa0['id_campana']=$campaigns_name[$qa0['id_proceso']]['id_campana'];
        unset($qa0['id_proceso']);
        $result[]=$qa0;
    }

    $fechas = array();
    foreach ($result as $value) {
        $numDay = date('N', strtotime($value['fecha_pago']));
        //if($numDay != 6 && $numDay != 7){
            if(array_key_exists($value['fecha_pago'], $fechas)){
                $fechas[$value['fecha_pago']]++;
            }else{
                $fechas[$value['fecha_pago']] = 1;
            }
        //}
    }

    $now = date( 'Y-m-d');





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
    //C 15
    $meta = 78390.30;
    $meta_diaria = $meta/sizeof($fechas);
    //C 15
    //  $meta = 100000;
    //  $meta_diaria = $meta/sizeof($fechas);

    for ($i=0; $i < sizeof($result); $i++) { 
        
        $result[$i]['campana'] = $campanaName;
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
    
    $result15 = $result;
    unset($result);






    //=======================================================================================\\





    $campanaName = null;
    $campaigns_name=array();
    $q0=pg_query($conn,'SELECT c.*,p.id_proceso FROM campanas.proceso p JOIN campanas.campana c USING (id_campana) WHERE id_proceso IN ('.$id_proceso_TDC.')');


    while($p=pg_fetch_assoc($q0)) {
        $campaigns_name[$p['id_proceso']]=array('name'=>$p['campana'],'id_campana'=>$p['id_campana']);
    }


    $query =   'SELECT
                    c.id_proceso,c.id_cuenta,c.cuenta,ABS(ca.diferencia) as pago,DATE(ca.fecha_actualizacion) as fecha_pago,p.identificacion
                FROM
                    cuentas.cuenta c
                    JOIN personas.persona p ON(p.id_persona=c.id_deudor)
                    JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=c.id_cuenta)
                WHERE
                    c.id_proceso in (
                        '.implode(',',array_keys($campaigns_name)).'
                    )
                    AND ca.tipo_actualizacion=\'PAGO\'
                ORDER BY ca.fecha_actualizacion ASC';

    $q0 = pg_query($conn, $query);
    $result = array();
    while($qa0=pg_fetch_assoc($q0)) {
        $qa0['campana']=$campaigns_name[$qa0['id_proceso']]['name'];
        $campanaName = $qa0['campana'];
        $qa0['id_campana']=$campaigns_name[$qa0['id_proceso']]['id_campana'];
        unset($qa0['id_proceso']);
        $result[]=$qa0;
    }

    $fechas = array();
    foreach ($result as $value) {
        $numDay = date('N', strtotime($value['fecha_pago']));
        //if($numDay != 6 && $numDay != 7){
            if(array_key_exists($value['fecha_pago'], $fechas)){
                $fechas[$value['fecha_pago']]++;
            }else{
                $fechas[$value['fecha_pago']] = 1;
            }
        //}
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
    //TDC 16
    $meta = 14098.38;
    $meta_diaria = $meta/sizeof($fechas);
    //C 15
    //  $meta = 100000;
    //  $meta_diaria = $meta/sizeof($fechas);

    for ($i=0; $i < sizeof($result); $i++) { 
        
        $result[$i]['campana'] = $campanaName;
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

    $result = array_merge($result15, $result);

    echo json_encode( $result, JSON_PRETTY_PRINT );
    die();
