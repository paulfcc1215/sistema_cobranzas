<?php

    try {

        $date=null;
        $date = getdate();
        if ($date['hours']<8 || $date['hours']>18) die();

        require '/opt/www/cobranzas/config.php';
        $db = DB::getInstance();

        // truncate tabla dashboards.gestiones_cnel
        $db->query('TRUNCATE TABLE dashboards.gestiones_cnel');

        // último proceso
        $q = 'SELECT max(id_proceso) FROM campanas.proceso WHERE id_campana=18 AND status=\'1\'';
        $q0 = $db->query($q);
        $id_proceso=$db->fetchOne($q0)['max'];
        // quemado para enero 2022
	    // $id_proceso=158;

        // get gestiones
        $q = 'SELECT
            c.cuenta,
            c.valor_original,
            CASE WHEN u.nombre_completo = \'GEOVANY RAMOS\' 
                THEN \'GEOVANY RAMOS / MASIVOS\'
                ELSE  u.nombre_completo
            END AS nombre_completo,
            g.* ,t.descripcion as tipificacion,
            CASE WHEN tm.contactabilidad = \'1\' 
                THEN \'CONTACTADO\'
                ELSE  \'NO CONTACTADO\'
            END AS contactabilidad,
            tm.tipo_contactabilidad,
            p.identificacion
        FROM gestiones.gestion g
            JOIN auth.auth_usuarios u ON g.user_name = u.usr_logname
            JOIN cuentas.cuenta c ON c.id_cuenta = g.id_cuenta
            JOIN personas.persona p ON p.id_persona = c.id_deudor
            JOIN campanas.proceso pr ON pr.id_proceso = c.id_proceso
            JOIN campanas.campana ca ON ca.id_campana = pr.id_campana
            JOIN gestiones.tipificacion t ON t.id_tipificacion = g.id_tipificacion
            JOIN gestiones.tipificacion_metadata tm ON tm.id_tipificacion = t.id_tipificacion
        WHERE 
            --NOT EXISTS (SELECT * FROM gestiones.tipificacion WHERE id_tipificacion=g.id_tipificacion AND id_tipificacion IN(282,283,285,287,289)) AND
            pr.id_proceso = '.$id_proceso;

        $q2 = 'SELECT * FROM cuentas.cuenta_actualizacion WHERE id_cuenta=$1 ORDER BY ABS(diferencia) DESC, fecha_actualizacion DESC';
        $db->prepare('q2',$q2);

        $q0 = $db->query($q);
        $db->startTransaction();
        while ($qa0 = $db->fetchOne($q0)){

            $aux = $qa0;
            $aux['gestion_cobro'] = '';

            // get carga no mapeada
            $cnm = getCargaNoMapeada($qa0['id_cuenta']);
            $cnm = array_shift($cnm);
            if (floatval($cnm['RECAUDA_CONV_PAGO_TOT'])!=0){
                $aux['gestion_cobro'] = 'Convenio';
            }else{
                $fecha_gestion = $qa0['fecha_inicio'];
                // get pagos
                $q_prepare = $db->execute('q2',array($qa0['id_cuenta']));
                $ca = $db->fetchAll($q_prepare);

                if (!empty($ca)){
                    $pago = 0;
                    foreach ($ca as $a){
                        if ($a['tipo_actualizacion']=='PAGO' && strtotime($fecha_gestion)<=strtotime($a['fecha_actualizacion'])){
                            $pago = $pago + abs($a['diferencia']);
                            break;
                        }
                    }
                    if ($pago!=0){
                        $porcentaje_pagado = ($pago*100)/$qa0['valor_original'];
                        // si pagos supera el 40%
                        if ($porcentaje_pagado>=40 && !in_array($qa0['id_tipificacion'],array(282,283,285,287,289))) {
                            $aux['gestion_cobro'] = '40%';
                            // print_arr($qa0['id_cuenta'].', Deuda: '.$qa0['valor_original'].' =====>Pago: '.$pago.'=====>'.$porcentaje_pagado);
                        }else{
                            $aux['gestion_cobro'] = 'PAGO MENOR 40%';
                        }
                    }
                }
            }
            
            foreach ($aux as $k => &$v){
                $v = str_replace("'","",$v);
            }
            // insert en tabla dashboards.gestiones_cnel
            $q = 'INSERT INTO dashboards.gestiones_cnel('.implode(',',array_keys($aux)).') VALUES(\''.implode('\',\'',$aux).'\')';
            $db->query($q);
        }
        $db->commit();
    } catch (Exception $ex) {
        $mail = new Helpers_Mail();
        $to = array(
            'paul.cedeno@recappt.com',
            'artura.villafuerte@grupocant.com'
        );
        $mail->add_attachment($file_zip);
        $subject = 'NOTIFICACION AUTOMATICA - DASHBOARD CNEL';
        $content = 'Ha ocurrido un error al generar data para consumo dashboard CNEL.<br><br>Error: '.$ex->getMessage();
        $mail->sendMail($to,$subject,$content);
        $db->rollback();
    }