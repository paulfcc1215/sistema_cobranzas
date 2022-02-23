<?php
    /*
    Proceso para envio automatico de reporte de gestion del UDN CNEL
    */
    require '../config.php';

    $proceso = getProcesoByCampana(19,' ORDER BY id_proceso DESC')[0];
    $id_proceso = $proceso['id_proceso'];
    
    $db = DB::getInstance();
    
    $reporte = 'gestion_GAD_Portoviejo_'.date('dmY');
    $result[$reporte][]=array(
        'CEDULARUC',
        'NOMBRE',
        'id',
        'principal',
        'tipo',
        'clave',
        'MesObligacion',
        'añoemision',
        'AñoObligacion',
        'total',
        'FECHA DE ASIGNACION',
        'FECHA ULTIMA DE  CARGA',
        'FECHA DE GESTION',
        'GESTION RECAPT',
        'GESTION PROVEEDOR',
        'CONTACTABILIDAD',
        'telefono_de_contacto',
        'origen_telefono',
        'fecha_compromiso',
        'IMPORTEPROMESA',
        'observaciones',
        'campana',
        'agente',
        'peso',
        'id_llamada',
        'HORA',
        'MINUTOS',
        'DIA',
        'MES'
    );
    $output = &$result[$reporte];

    $initDate = date("Y-m-01 00:00:00");
    $endDate = date("Y-m-d 21:00:00");


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
            pr.id_proceso='.$id_proceso.' AND
            date(g.fecha_inicio) BETWEEN \''.$initDate.'\' AND \''.$endDate.'\'
        ORDER BY
            g.fecha_inicio DESC,t.peso DESC';
    $gestiones_x_cuenta=array();
    $xxx = array();
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
            
            //get telefono origen
            $q = 'SELECT origen FROM medios_contacto.telefono WHERE id_persona='.$gestion['p_id_persona'].' AND telefono=\''.$gestion['g_tel_number'].'\'';
            $q0 = $db->query($q);
            $origen = $db->fetchOne($q0)['origen'];
            
            $fecha_gestion = new datetime($gestion['g_fecha_inicio']);

            $line = array(
                
                // 'CEDULARUC',
                $gestion['p_identificacion'],
                // 'NOMBRE',
                Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
                // 'id',
                $gestion['c_cuenta'],
                // 'principal',
                '1',
                // 'tipo',
                $datos_nm['tipo'],
                // 'clave',
                $datos_nm['clave'],
                // 'MesObligacion',
                $datos_nm['MesObligacion'],
                // 'añoemision',
                $datos_nm['añoemision'],
                // 'AñoObligacion',
                $datos_nm['AñoObligacion'],
                // 'total',
                $gestion['c_valor_original'],
                // 'FECHA DE ASIGNACION',
                $gestion['c_fecha_creacion'],
                // 'FECHA ULTIMA DE  CARGA',
                $gestion['c_fecha_valor_actual'],
                // 'FECHA DE GESTION',
                $gestion['g_fecha_inicio'],
                // 'GESTION RECAPT',
                $tipificacion['descripcion'],
                // 'GESTION PROVEEDOR',
                '',
                // 'CONTACTABILIDAD',
                $tipificacion['_metadata']['contactabilidad']?'CONTACTADO':'NO CONTACTADO',
                // 'telefono_de_contacto',
                $gestion['g_tel_number'],
                // 'origen_telefono',
                $origen,
                // 'fecha_compromiso',
                $gestion['g_fecha_compromiso'],
                // 'IMPORTEPROMESA',
                $gestion['g_monto_compromiso'],
                // 'observaciones',
                str_replace(array("\t","\n")," ",$gestion['g_observacion']),
                // 'campana',
                $gestion['camp_campana'],
                // 'agente',
                $gestion['g_user_name'],
                // 'peso',
                $gestion['t_peso'],
                // 'id_llamada',
                $gestion['g_telh_id'],
                // 'HORA',
                $fecha_gestion->format('H'),
                // 'MINUTOS',
                $fecha_gestion->format('i'),
                // 'DIA',
                $fecha_gestion->format('d'),
                // 'MES',
                $fecha_gestion->format('m'),

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
    $file_name = 'gestion_GAD_Portoviejo_'.date('dmY').'.txt';
    $file_path = $file->getFilePath();
    rename($file_path,$file_path='/tmp'.'/'.$file_name);

    $file_zip = '/tmp/reporte_gestiones_GAD_'.date('dmY').'.zip';
    $zip = new ZipArchive();
    if (!$zip->open($file_zip,ZipArchive::CREATE)){
        throw new Exception('Error al crear archivo ZIP');
    }
    if (!$zip->addFile($file_path,$file_name)){
        throw new Exception('Error al agregar archivo "'.$file_name.'" en el ZIP');
    }
    $zip->close();

    //Send to SFTP

    include('phpseclib1.0.20/Net/SFTP.php');

    $file  = $file_zip;
    $remote_file  = explode("/", $file);
    $remote_file = "/home/sftp/GadPortoviejo/GESTIONES REC/".$remote_file[sizeof($remote_file)-1];

    $sftp_server = "192.168.180.40";
    $sftp_user_name = "root";
    $sftp_user_pass = "sftpcant2021.";

    $sftp = new Net_SFTP($sftp_server);
    if (!$sftp->login($sftp_user_name, $sftp_user_pass)) {
        exit('Login Failed');
    }
    if(!$sftp->put($remote_file, $file, NET_SFTP_LOCAL_FILE)){
        echo "No se ha podido cargar el resporte.";
    }

    die();