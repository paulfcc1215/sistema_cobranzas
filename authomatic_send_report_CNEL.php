<?php
    /*
    Proceso para envio automatico de reporte de gestion del UDN CNEL
    */
    require 'config.php';

    $proceso = getProcesoByCampana(18,' ORDER BY id_proceso DESC')[0];
    $id_proceso = $proceso['id_proceso'];
    
    $db = DB::getInstance();
    
    $reporte = 'gestion_CNEL_'.date('dmY');
    $result[$reporte][]=array(
        'item',
        'unidad_de_negocio',
        'numero_servicio',
        'cedula',
        'cliente',
        'estado',
        'deuda_total',
        'facturas_pendientes',
        'tarifa',
        'tipo_cliente',
        'cedula_valida',
        'rango_pla_pendientes',
        'numero_medidor',
        'serie_medidor',

        'tramo_de_mora',
        'mes',
        'costo_unitario',
        'mes_campana',
        'Descripcion_Canton',
        'Facturas_Pendientes',
        '40.00%',
        'Estado',
        'Data',

        'fecha_gestion',
        'user_name',
        'numero_contacto',
        'tipificacion',
        'observacion',
        'fecha_compromiso',
        'monto_compromiso'
    );
    
    $output = &$result[$reporte];
    $q = 'SELECT
        '.get_query_fields('gestion','g','g_','gestiones',true).',
        '.get_query_fields('cuenta','c','c_','cuentas',true).',
        '.get_query_fields('persona','p','p_','personas',true).',
        '.get_query_fields('proceso','pr','pr_','campanas',true).',
        '.get_query_fields('tipificacion','t','t_','gestiones',true).',
        '.get_query_fields('campana','camp','camp_','campanas',true).'
        FROM
            gestiones.gestion g
            JOIN cuentas.cuenta c USING (id_cuenta)
            JOIN campanas.proceso pr USING (id_proceso)
            JOIN campanas.campana camp USING (id_campana)
            JOIN personas.persona p ON (c.id_deudor=p.id_persona)
            JOIN gestiones.tipificacion t USING (id_tipificacion)
        WHERE
            pr.id_proceso='.$id_proceso.'
        ORDER BY
            g.fecha_inicio DESC,t.peso DESC';
    $gestiones_x_cuenta=array();
    foreach($db->query($q) as $aux) {
        $gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
    }

    $db->prepare('q2','SELECT campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 AND id_carga=$2');
    foreach ($gestiones_x_cuenta as $id_cuenta => $gestiones) {
        foreach ($gestiones as $gestion){
            
            $q1 = $db->execute('q2',array($gestion['g_id_cuenta'],$gestion['c_id_carga']));
            while ($row = $db->fetchOne($q1)){
                $datos_nm[$row['campo']]=$row['valor'];
            }
            $tipificacion = getTipificacion($gestion['g_id_tipificacion']);
            
            $line = array(
                // 'item',
                $datos_nm['Item'],
                // 'unidad_de_negocio',
                $datos_nm['Unidad de Negocio'],
                // 'numero_servicio',
                $gestion['c_cuenta'],
                // 'cedula',
                $gestion['p_identificacion'],
                // 'cliente',
                Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
                // 'estado',
                $datos_nm['Estado'],
                // 'deuda_total',
                $gestion['c_valor_original'],
                // 'facturas_pendientes',
                $datos_nm['Facturas_Pendientes'],
                // 'tarifa',
                $datos_nm['Tarifa'],
                // 'tipo_cliente',
                $datos_nm['Tipo Cliente'],
                // 'cedula_valida',
                $datos_nm['Cedula valida'],
                // 'rango_pla_pendientes',
                $datos_nm['Rango Pla Pendientes'],
                // 'numero_medidor',
                $datos_nm['Numero Medidor'],
                // 'serie_medidor',
                $datos_nm['Serie Medidor'],
                // 'tramo_de_mora',
                $datos_nm['tramo_de_mora'],
                // 'mes',
                $datos_nm['mes'],
                // 'costo_unitario',
                $datos_nm['costo_unitario'],
                // 'mes_campana',
                $datos_nm['mes_campana'],
                // 'Descripcion_Canton',
                $datos_nm['Descripcion_Canton'],
                // 'Facturas_Pendientes',
                $datos_nm['Facturas_Pendientes'],
                // '40.00%',
                $datos_nm['40.00%'],
                // 'Estado',
                $datos_nm['40.00%'],
                // 'Data',
                $datos_nm['data'],
                // 'fecha_gestion',
                $gestion['g_fecha_inicio'],
                // 'user_name',
                $gestion['g_user_name'],
                // 'numero_contacto',
                $gestion['g_tel_number'],
                // 'tipificacion',
                $tipificacion['descripcion'],
                // 'observacion',
                $gestion['g_observacion'],
                // 'fecha_compromiso',
                $gestion['g_fecha_compromiso'],
                // 'monto_compromiso'
                $gestion['g_monto_compromiso']
            );

            foreach($line as &$l) {
                $l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
                unset($l);
            }
            $output[]=$line;
        }
    }

    $file = new Helpers_CSV_Writer();
    $file->setLines($output);
    $file_name = 'gestion_'.date('dmY').'.txt';
    $file_path = $file->getFilePath();
    rename($file_path,$file_path='/tmp'.'/'.$file_name);

    $file_zip = '/tmp/reporte_'.date('dmY').'.zip';
    $zip = new ZipArchive();
    if (!$zip->open($file_zip,ZipArchive::CREATE)){
        throw new Exception('Error al crear archivo ZIP');
    }
    if (!$zip->addFile($file_path,$file_name)){
        throw new Exception('Error al agregar archivo "'.$file_name.'" en el ZIP');
    }
    $zip->close();

    $mail = new Helpers_Mail();
	$to = array(
        'yadira.elizalde@cnel.gob.ec',
        'jimmy.vinces@cnel.gob.ec',
		'paul.cedeno@recappt.com',
		'jairoguevara@recappt.com',
        'eduardo.martinez@recapt.com.ec',
        'marco.pala@recapt.com.ec',
        'diego.gaybor@recapt.com.ec',
	);
	$mail->add_attachment($file_zip);
    $subject = 'NOTIFICACION AUTOMATICA - GESTION DEL MES EN CURSO';
    $content = 'Estimado CNEL EP,<br><br>Dando cumplimiento al contrato, se adjuntan las gestiones diarias correspondientes al mes en curso.<br><br>Saludos cordiales.<br>RECAPT S.A.<br><br><br><br><br><br><span>--- Este es un correo generado autom&aacute;ticamente por el sistema de gesti&oacute;n de cobranzas y no se debe responder, si existe alguna aclaraci&oacute;n pongase en contacto con el administrador ---</span>';
	$mail->sendMail($to,$subject,$content);

    //echo 'Reporte generado exitosamente!!';
    //echo '<br>';
    // die();
