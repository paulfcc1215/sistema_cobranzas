<?php
//error_reporting(E_ALL);
    //get persona ids
    $q = 'SELECT id_persona FROM personas.persona WHERE identificacion=\''.$_POST['identificacion'].'\'';
    $ids=array();
    foreach ($db->query($q) as $v){
        $ids[]=$v['id_persona'];
    }
    //get personas data
    foreach($ids as $id){
        $persona[] = _getPersona($id,$ref,false,true,false,false);
    }
    $data_persona= $persona[0];
    $telefonos_agregados = array();
    $telefonos_persona = array();
    
    foreach ($persona as $p){
        //gestionados
        foreach ($p['telefonos_gestionados']['contacto'] as $t){
            if (!in_array($t['tel_number'],$telefonos_agregados)){
                $telefonos_agregados[] = $t['tel_number'];
                $telefonos_persona['gestionados']['contacto'][$t['tel_number']]=$t;
            }
        }
        foreach ($p['telefonos_gestionados']['sin_contacto'] as $t){
            if (!in_array($t['tel_number'],$telefonos_agregados)){
                $telefonos_agregados[] = $t['tel_number'];
                $telefonos_persona['gestionados']['sin_contacto'][$t['tel_number']]=$t;
            }
        }
        //no gestionados
        foreach ($p['telefonos'] as $t){
            if (!in_array((is_null($t['tel_number'])?$t['telefono']:$t['tel_number']),$telefonos_agregados)){
                $telefonos_agregados[] = (is_null($t['tel_number'])?$t['telefono']:$t['tel_number']);
                $telefonos_persona['no_gestionados'][(is_null($t['tel_number'])?$t['telefono']:$t['tel_number'])]=$t;
            }
        }
    }
    
    $_T['maincontent'] .= '<label>Identificación:</label>'.$data_persona['identificacion'].'<br>';
    $_T['maincontent'] .= '<label>Nombres:</label>'.Helpers::implodeNotEmpty(' ',array($data_persona['primer_nombre'],$data_persona['segundo_nombre'],$data_persona['primer_apellido'],$data_persona['segundo_apellido'])).'<br><br>';
    
    $_T['maincontent'] .= '
    <table class="simple_table2">
        <tr>
            <td valign="top">
                <b>GESTIONADOS:</b>
                <hr>
                <table>
                    <tr><th>FECHA GESTIÓN</th><th>TIPIFICACIÓN</th><th>FUENTE</th><th>TELÉFONO</th></tr>';
                    foreach ($telefonos_persona['gestionados'] as $clasificacion => $tels){
                        $_T['maincontent'] .= '<tr><th colspan="4">'.strtoupper($clasificacion).'</th></tr>';
                        foreach($tels as $tel_number => $tel_detalle){
                            $_T['maincontent'] .= '<tr><td>'.$tel_detalle['fecha_inicio'].'</td><td>'.$tel_detalle['descripcion'].'</td><td>'.$tel_detalle['fuente'].'</td><td>'.$tel_number.'</td></tr>';
                        }
                    }
                $_T['maincontent'] .= '
                </table>
            </td>';
    $_T['maincontent'] .= '
            <td valign="top">
                <b>NO GESTIONADOS:</b>
                <hr>
                <table>
                    <tr><th>FECHA ACTUALIZACIÓN</th><th>CLASIFICACIÓN</th><th>FUENTE</th><th>TELÉFONO</th></tr>';
                    foreach ($telefonos_persona['no_gestionados'] as $tel_number => $tel_detalle){
                        $_T['maincontent'] .= '<tr><td>'.(is_null($tel_detalle['fecha_agregado'])?$tel_detalle['fecha_inicio']:$tel_detalle['fecha_agregado']).'</td><td>'.$tel_detalle['clasificacion_telefono'].'</td><td>'.(is_null($tel_detalle['fuente'])?$tel_detalle['origen']:$tel_detalle['fuente']).'</td><td>'.$tel_number.'</td></tr>';
                    }
                $_T['maincontent'] .= '
                </table>
            </td>
        </tr>
    </table>';