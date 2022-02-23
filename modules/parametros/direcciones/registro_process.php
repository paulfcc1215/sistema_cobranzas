<?php

    if ($_POST['identificacion']=='') throw new exception('Identificacion requerido');
    if ($_POST['tipo_direccion']=='') throw new exception('Tipo direccion requerido');
    if ($_POST['PROVINCIA']=='') throw new exception('Provincia requerido');
    if ($_POST['CANTON']=='') throw new exception('Cantón requerido');
    if ($_POST['CALLE_PRINCIPAL']=='') throw new exception('Calle principal requerido');
    if ($_POST['CALLE_SECUNDARIA']=='') throw new exception('Calle secundaria requerido');

    $q = 'SELECT * FROM medios_contacto.tipo_ubicacion';
    foreach ($db->query($q) as $d){
        $_tipos_ubicacion[$d['id_tipo_ubicacion']]=str_replace(' ','_',$d['descripcion']);
    }

    $q = 'SELECT * FROM personas.persona WHERE identificacion=\''.$_POST['identificacion'].'\'';
    $q0 = $db->query($q);
    $personas = $db->fetchAll($q0);
    if (empty($personas)) throw new exception('La identificación no corresponde a ninguna persona en Orión');
    unset($q0);

    $db->startTransaction();
    foreach ($personas as $p){
        $aux_dir =array();
        foreach ($_POST as $k => $v){
            if (trim($v)=='') continue;
            if (in_array($k,array('save','identificacion','tipo_direccion'))) continue;
            $aux_dir[$k]=$v;
        }
        $hash = implode('',$aux_dir);
        $hash = preg_replace('#[^A-za-z0-9]#','',$hash);
        $hash = crc32($hash);
        try {
            $q = 'INSERT INTO medios_contacto.direcciones(id_persona,tipo_direccion,fecha_insercion,hash)VALUES('.$p['id_persona'].',\''.$_POST['tipo_direccion'].'\',CURRENT_TIMESTAMP,\''.$hash.'\') RETURNING id_direccion';
            $q0 = $db->query($q);
            $id_direccion = $db->fetchOne($q0)['id_direccion'];
            foreach ($aux_dir as $tipo_ubi => $valor){
                $id_tipo_ubicacion = array_search($tipo_ubi,$_tipos_ubicacion);
                if (!$id_tipo_ubicacion) continue;
                $q = 'INSERT INTO medios_contacto.direcciones_data(id_direccion,id_tipo_ubicacion,valor) VALUES ('.$id_direccion.','.$id_tipo_ubicacion.',\''.$valor.'\')';
                $q0 = $db->query($q);
            }
            $db->commit();
        }catch(Exception $ex){
            throw new exception($ex->getMessage());
            $db->rollback();
        }
        
    }

    $_T['maincontent'] .= '<div class="alert alert-success" role="alert">Dirección registrada correctamente!</div>';