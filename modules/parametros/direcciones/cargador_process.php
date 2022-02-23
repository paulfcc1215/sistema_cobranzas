<?php

    if ($_FILES['archivo']['error']==4) throw new exception('No ha seleccionado archivo');
    if ($_FILES['archivo']['error']!=0) throw new exception('Error al subir archivo');
    //mover el archivo
    $fpath = _TMP_UPLOAD_FOLDER.'/'.uniqid();
    move_uploaded_file($_FILES['archivo']['tmp_name'],$fpath);
    
    $file = new Helpers_CSV($fpath);
    $cabecera = $file->getHeader();

    // VALIDACION DE COLUMNAS OBLIGATORIAS
    $columnas_obligatorias = array('identificacion','tipo_direccion');
    $faltan = array();
    foreach ($columnas_obligatorias as $columna) {
        if (!in_array($columna,$cabecera)){
            $faltan[] = $columna;
        }
    }
    if (!empty($faltan)){
        throw new exception('Columnas Obligatorias: '.implode(',',$faltan));
    }

    // VALIDACION DE COLUMNAS PERMITIDAS
    $q = 'SELECT * FROM medios_contacto.tipo_ubicacion';
    foreach ($db->query($q) as $d){
        $columnas_permitidas[$d['id_tipo_ubicacion']]=$d['descripcion'];
    }
    $no_permitidas = array();
    foreach ($cabecera as $c) {
        if ($c=='identificacion' || $c=='tipo_direccion') continue;
        if (!in_array($c,$columnas_permitidas)) {
            $no_permitidas[]=$c;
        }
    }
    if (!empty($no_permitidas)){
        throw new exception('Columnas No permitidas: '.implode(',',$no_permitidas).'<br>Las columnas permitidas son: '.implode(',',$columnas_permitidas));
    }

    $tipo_direccion = array();
    foreach ($db->query('SELECT * FROM pg_enum WHERE enumtypid=(SELECT oid FROM pg_type WHERE typname = \'enum_tipo_direccion\')') as $d){
        $tipo_direccion[$d['enumlabel']]=$d['enumlabel'];
    }
    
    $resultados = array(
        'procesado' => array(
            'count' => 0,
            'data' => array()
        ),
        'no_procesado' => array(
            'count' => 0,
            'persona_no_existe' => array(
                'count' => 0,
                'data' => array()
            ),
            'sin_identificacion' => array(
                'count' => 0,
                'data' => array()
            ),
            'tipo_direccion_incorrecto' => array(
                'count' => 0,
                'data' => array()
            ),
            'direccion_ya_existe' => array(
                'count' => 0,
                'data' => array()
            ),
            'error_insert' => array(
                'count' => 0,
                'data' => array()
            ),
        )
    );

    $db->prepare('new_direccion','INSERT INTO medios_contacto.direcciones(id_persona,tipo_direccion,hash)VALUES($1,$2,$3) RETURNING id_direccion');
    $db->prepare('new_direccion_data','INSERT INTO medios_contacto.direcciones_data(id_direccion,id_tipo_ubicacion,valor)VALUES($1,$2,$3)');
    foreach ($file as $num_linea => $linea) {
        $direccion=array();
        foreach ($linea as $key => &$value) {
            trim($value);
            if ($key=='identificacion' || $key=='tipo_direccion') continue;
            $direccion[array_search($key,$columnas_permitidas)]=$value;
        }
        ksort($direccion);
        $hash = implode('',$direccion);
        $hash = preg_replace('#[^A-za-z0-9]#','',$hash);
        $hash = crc32($hash);

        // VALIDACION PERSONA
        if ($linea['identificacion']==''){
            $resultados['no_procesado']['count']++;
            $resultados['no_procesado']['sin_identificacion']['count']++;
            $resultados['no_procesado']['sin_identificacion']['data'][] = $num_linea;
            continue;
        }
        $q = 'SELECT * FROM personas.persona WHERE identificacion=\''.$linea['identificacion'].'\'';
        $q0 = $db->query($q);
        if ($db->numRows($q0)==0) {
            $resultados['no_procesado']['count']++;
            $resultados['no_procesado']['persona_no_existe']['count']++;
            $resultados['no_procesado']['persona_no_existe']['data'][] = $num_linea.' - '.$linea['identificacion'];
            continue;
        }
        $id_persona = $db->fetchOne($q0)['id_persona'];

        // VALIDACION TIPO DIRECCION
        if (!in_array($linea['tipo_direccion'],$tipo_direccion)){
            $resultados['no_procesado']['count']++;
            $resultados['no_procesado']['tipo_direccion_incorrecto']['count']++;
            $resultados['no_procesado']['tipo_direccion_incorrecto']['data'][] = $num_linea.' - '.$linea['identificacion'];
            continue;
        }

        // VALIDACION HASH DIRECCION
        // get direciones existentes
        $q = 'SELECT d.* FROM medios_contacto.direcciones d WHERE d.id_persona='.$id_persona;
        $direccion_existe=false;
        foreach ($db->query($q) as $row) {
            if ($hash==$row['hash']){
                $direccion_existe=true;
            }
        }
        if ($direccion_existe){
            $resultados['no_procesado']['count']++;
            $resultados['no_procesado']['direccion_ya_existe']['count']++;
            $resultados['no_procesado']['direccion_ya_existe']['data'][]=$num_linea.' - '.$linea['identificacion'];
            continue;
        }
        
        $db->startTransaction();
        try{
            $id_direccion = $db->execute('new_direccion',array($id_persona,$linea['tipo_direccion'],$hash))->current()['id_direccion'];
            foreach ($direccion as $key => $value) {
                $db->execute('new_direccion_data',array($id_direccion,$key,$value));
            }
            $resultados['procesado']['count']++;
            $resultados['procesado']['data'][]=$num_linea.' - '.$linea['identificacion'];
            $db->commit();
        }catch(Exception $ex){
            $resultados['no_procesado']['count']++;
            $resultados['no_procesado']['error_insert']['count']++;
            $resultados['no_procesado']['error_insert']['data'][]=$num_linea.' - '.$linea['identificacion'];
            $db->rollback();
        }

    }

    $_T['maincontent'].='
    <table>
        <tr>
            <td style="padding:10px;" valign="top">';
                $_T['maincontent'].='<label>PROCESADOS: '.$resultados['procesado']['count'].'</label><br>';
                foreach ($resultados['procesado']['data'] as $dp){
                    $_T['maincontent'].=$dp.'<br>';
                }
            $_T['maincontent'].='
            </td>
            <td style="padding:10px;" valign="top">';
                $_T['maincontent'].='<label>NO PROCESADOS: '.$resultados['no_procesado']['count'].'</label><br>';
                foreach ($resultados['no_procesado'] as $p => $dp){
                    if ($p=='count') continue;
                    $_T['maincontent'].='<label>'.$p.': '.$dp['count'].'</label><br>';
                    foreach ($dp['data'] as $aux){
                        if ($dp=='count') continue;
                        $_T['maincontent'].=$aux.'<br>';
                    }
                }
            $_T['maincontent'].='
            </td>
        </tr>
    </table>';
    

    
    