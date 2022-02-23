<?php

    $db = DB::getInstance();
    if (!Auth::hasPrivileges('AUTH_MINERIA_INDEX')) throw new Exception('No autorizado - AUTH_MINERIA_INDEX');
    $_T['maintitle']='Minería de Datos';

    try{

        switch ($_GET['action']){
            case 'get_report':

                // print_arr($_FILES);
                // print_arr($_POST);

                if ($_POST['id_campana']=='') throw new exception('Seleccione Campaña');
                if (empty($_POST['tipo_consulta'])) throw new exception('Seleccione Tipo de minería');
                if ($_POST['fecha_desde']=='') throw new exception('Seleccione Fecha Desde');
                if ($_POST['fecha_hasta']=='') throw new exception('Seleccione Fecha Hasta');
                 if ($_POST['solo_archivo']=='SI' && $_FILES['archivo']['name']=='') throw new exception('Ingrese el archivo para las consultas');

                $f_desde = Helpers::dmy2ymd($_POST['fecha_desde']);
                $f_hasta = Helpers::dmy2ymd($_POST['fecha_hasta']);
                $tipo_consulta = array(
                    'lb'=>false,
                    'bo'=>false,
                    'lg'=>false,
                );
                foreach ($tipo_consulta as $tc => &$v){
                    if (in_array($tc,$_POST['tipo_consulta'])){
                        $v=true;
                    }
                }


                // $a = array (
                //     '123'=>array('a','b'),
                //     '234'=>array('a','b'),
                //     '345'=>array('a','b'),
                //     '456'=>array('a','b'),
                // );

                // print_arr($a);

                // $b = array (
                //     '123'=>array('c','d'),
                //     '567'=>array('a','b'),
                //     '678'=>array('a','b'),
                //     '789'=>array('a','b'),
                // );
                // print_arr($b);
                // echo 'merge';
                // $c = $a+$b;
                // print_arr($c);
                // die();


                $results = array();

                $cedulas_procesadas = array();
                // get telefonos del archivo
                if ($_FILES['archivo']['name']!=''){
                    $cabecera_requerida = array('cedula');
                    $file = new Helpers_CSV($_FILES['archivo']['tmp_name']);
                    foreach($file->getHeader() as $c){
                        if (!in_array($c,$cabecera_requerida)){
                            throw new exception('Se requiere los campos '.implode(',',$cabecera_requerida));
                        }
                    }
                    
                    foreach ($file as $f){
                        $aux = array();
                        if (in_array($f['cedula'],$cedulas_procesadas)) continue;
                        $cedulas_procesadas[] = $f['cedula'];
                        if ($_POST['solo_archivo']=='SI'){
                            $aux = getTelefonosByIdentificacion($f['cedula']);
                        }else{
                            $aux = getGestionesByCampana($_POST['id_campana'],$f['cedula'],$f_desde,$f_hasta,$_POST['tipificaciones']);
                        }
                        
                        if (empty($aux)) continue;
                        $results[$f['cedula']] = $aux[$f['cedula']];
                    }
                }

                // si no es solo archivo consultamos tmbn de la campaña
                if ($_POST['solo_archivo']!='SI'){
                    // get telefonos desde campaña
                    $results = $results + getGestionesByCampana($_POST['id_campana'],'',$f_desde,$f_hasta,$_POST['tipificaciones']);
                }

                $mineria = array();
                $mineria[] = array('identificacion','telefonos');
                $cedulas_procesadas = array();
                foreach($results as $identificacion => $r){
                    if (in_array($identificacion,$cedulas_procesadas)) continue;
                    $aux = array();
                    $aux[]=$identificacion;
            
                    //get numeros de repo
                    $tels_repo = _getTelefonosRepositorio($identificacion);
            
                    //se agregan LB (LISTAS BLANCAS)
                    if ($tipo_consulta['lb']){
                        foreach($tels_repo as $rr){
            
                            // se excluye si ya fue agregado
                            if (in_array($rr['numero_telefono'],$aux)) continue;
                            // se excluye el telefono de gestión que es no contactable
                            if (in_array($rr['numero_telefono'],$r['telefono_gestion'])) continue;
            
                            if ($rr['clasificacion_telefono']=='lista_blanca'){
                                $aux[]=$rr['numero_telefono'];
                            }
                        }
                    }
            
                    //se agregan BO (BASE ORIGINAL)
                    if ($tipo_consulta['bo']){
                        foreach($r['telefonos_base'] as $tel){
            
                            // se excluye si ya fue agregado
                            if (in_array($tel,$aux)) continue;
                            // se excluye el telefono de gestión que es no contactable
                            if (in_array($tel,$r['telefono_gestion'])) continue;
            
                            $aux[]=$tel;
                        }
                    }
            
                    //se agregan LG (LISTAS GRIS)
                    if ($tipo_consulta['lg']){
                        foreach($tels_repo as $rr){
            
                            // se excluye si ya fue agregado
                            if (in_array($rr['numero_telefono'],$aux)) continue;
                            // se excluye el telefono de gestión que es no contactable
                            if (in_array($rr['numero_telefono'],$r['telefono_gestion'])) continue;
            
                            if ($rr['clasificacion_telefono']=='lista_gris'){
                                $aux[]=$rr['numero_telefono'];
                            }
                        }
                    }
                    $mineria[] = $aux;
                }
                
                if ($_POST['telefonos']=='T' && $_POST['solo_archivo']!='SI'){
                    foreach(getCuentasSinGestion($_POST['id_campana']) as $identificacion => $ts){
                        $aux = array();
                        $aux[] = $identificacion;
                        foreach ($ts['telefonos_base'] as $t){
                            $aux[] = $t;
                        }
                        $mineria[] = $aux;
                    }
                }


                $file = new Helpers_CSV_Writer();
                $file->setLines($mineria);
                $fileName = $file->getFilePath();
                
                header("Cache-Control: public");
                header("Content-Description: File Transfer");
                header("Content-Disposition: attachment; filename=mineria.txt");
                // header("Content-Type: application/zip");
                // header("Content-Transfer-Encoding: binary");
                
                // Read the file
                readfile($fileName);
                exit;
                
            break;
            default:
                // get udns
                $udns = array();
                foreach(AutoModel::getInstance('estructura','udn',DB::getInstance())->getAll() as $udn){
                    $aux = $udn->getData();
                    if ($aux['status']=='1')
                        $udns[$aux['id_udn']] = ucwords(strtolower($aux['udn']));
                }
                // get campanas
                $campanas = array();
                foreach ($udns as $id => $u){
                    $aux = array();
                    foreach (getCampanasByUdn($id) as $c){
                        if ($c['status']=='1'){
                            $tipificaciones = array();
                            $aux[$c['id_campana']]['campana'] = ucwords(strtolower($c['campana']));
                            foreach(getTipificacionesByCampana($c['id_campana']) as $t){
                                $tipificaciones[$t['id_tipificacion']] = ucwords(strtolower($t['descripcion']));
                            }
                            $aux[$c['id_campana']]['_tipificaciones'] = $tipificaciones;
                        }
                    }
                    $campanas[$id]=$aux;
                }

                $tipo_consulta= array(
                    'lb'=>'Lista Blanca',
                    'bo'=>'Base Original',
                    'lg'=>'Lista Gris'
                );

                $_T['maincontent'] .='
                    <div class="row">
                        <div class="col-sm-12">
                            <form method="POST" action="?mod=mineria/index&action=get_report" enctype="multipart/form-data">
                                <div class="card">
                                    <div class="card-header">
                                        <!--<h3 class="card-title">Parámetros de Minería</h3>
                                        <hr>-->
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-sm-4">
                                                <div class="custom-control custom-radio">
                                                    <label class="custom-control-label"><input type="radio" id="rd_todos_tels" name="telefonos" value="T" onchange="habilitar_tips(true)" checked>Todos las cuentas</label>
                                                    (<small>Cuentas gestionadas y no gestionadas</small>)
                                                </div>
                                                <div class="custom-control custom-radio">
                                                    <label class="custom-control-label"><input type="radio" id="rd_excluir_tels" name="telefonos" value="E" onchange="habilitar_tips(true)">Cuentas gestionadas</label>
                                                    (<small>Unicamente cuentas gestionadas</small>)
                                                </div>
                                                <hr>
                                                <div class="custom-control">
                                                    <label>Seleccione UDN:</label>
                                                    <select class="form-control" id="id_udn" onchange="change_udn($(this).val())">
                                                        <option value="">Seleccione...</option>';
                                                        foreach ($udns as $id => $u){
                                                            $_T['maincontent'] .='<option value="'.$id.'">'.$id.' - '.$u.'</option>';
                                                        }
                                                    $_T['maincontent'] .='
                                                    </select>
                                                </div>
                                                <div class="custom-control">
                                                    <label>Seleccione Campaña:</label>
                                                    <select class="form-control" id="id_campana" name="id_campana" size="6" onchange="change_campana($(this).val())">
                                                        <option value="">Seleccione...</option>
                                                    </select>
                                                </div>
                                                <hr>
                                                <div class="custom-control">
                                                    <label>Seleccione Rango de Fechas:</label><br>
                                                    Desde:<br><input type="text" name="fecha_desde" class="fecha" value="'.date('d/m/Y').'"><br>
                                                    Hasta:<br><input type="text" name="fecha_hasta" class="fecha" value="'.date('d/m/Y').'">
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <label>Seleccione Tipificaciones:</label><br>
                                                (<small>Marque las tipificaciones para excluir</small>)
                                                <div id="tipificaciones">
                                                    <br>
                                                    <p>Seleccione Campaña...</p>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="custom-file">
                                                    <label class="custom-control-label">Incluir Archivo Adicional</label>
                                                    (<small>Debe existir la columna "cedula"</small>)
                                                    <input type="file" name="archivo"><br>
                                                </div>
                                                <div class="checkbox">
                                                    <input type="checkbox" name="solo_archivo" value="SI" data-toggle="toggle" data-on="SI" data-off="NO" data-onstyle="success" data-width="60" data-height="34">
                                                    <b>Solo Archivo</b> (<small>Se hace minería unicamente con las cédulas del archivo</small>)
                                                </div>
                                                <hr>
                                                <label>Seleccione Tipo de Minería:</label><br>';
                                                foreach($tipo_consulta as $tc =>$name){
                                                    $_T['maincontent'].='
                                                    <div class="checkbox">
                                                        <input type="checkbox" name="tipo_consulta[]" value="'.$tc.'" data-toggle="toggle" data-on="SI" data-off="NO" data-onstyle="info" data-width="60" data-height="34" checked>
                                                        <b>'.$name.'</b> (<small>'.$name.'</small>)
                                                    </div>';
                                                }
                                                $_T['maincontent'].='
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <hr>
                                        <button class="btn btn-success">Generar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                ';
            break;
        }
    }catch(Exception $ex){
        $_T['maincontent'].='
            <div class="alert alert-danger alert-dismissible">'.$ex->getMessage().'<br><a href="?mod=mineria/index">Volver a parametrizar</a></div>
        ';
    }

    $_T['bottom_jscript'] .= '

        var campanas = '.json_encode($campanas).';
        function change_udn(me){
            $("#id_campana").empty();
            $("#id_campana").append("<option value=\"\">Seleccione...</option>");
            $("#tipificaciones").empty();
            if (me=="") return false;
            $.each(campanas[me],function(i,o){
                $("#id_campana").append("<option value=\""+i+"\">"+i+" - "+o.campana+"</option>");
            })
        }

        function change_campana(me){
            var tipificaciones = campanas[$("#id_udn").val()][$("#id_campana").val()]._tipificaciones;
            $("#tipificaciones").empty();
            var html="";
            $.each(tipificaciones,function(i,o){
                html+="<label><input type=\"checkbox\" name=\"tipificaciones[]\" value=\""+i+"\">"+o+"</label><br>";
            })
            $("#tipificaciones").append(html);
            if ($("#rd_todos_tels").is(":checked")){
                habilitar_tips(true)
            }
            if ($("#rd_excluir_tels").is(":checked")){
                habilitar_tips(true)
            }
        }

        function habilitar_tips(parametro){
            if (parametro){
                $.each($("#tipificaciones input[type=\'checkbox\']"), function(i,o){
                    $(o).removeAttr("disabled");
                })
            }else{
                $.each($("#tipificaciones input[type=\'checkbox\'],label"), function(i,o){
                    $(o).attr("disabled","disabled");
                })
            }
        }
    ';


    function getGestionesByCampana($id_campana,$identificacion='',$f_desde,$f_hasta,$tipificaciones=array()){
        global $db;

        $condicion='';
        if (!empty($tipificaciones)){
            $condicion = ' AND g.id_tipificacion NOT IN('.implode(',',$tipificaciones).')';
        }
        $condicion2='';
        if ($identificacion!=''){
            $condicion2 = ' AND p.identificacion=\''.$identificacion.'\'';
        }

        $q = 'SELECT 
            p.id_persona,p.identificacion,g.fecha_inicio,g.tel_number,t.descripcion,pr.descripcion as proceso, te.telefono,te.origen
        FROM gestiones.gestion g
            JOIN gestiones.tipificacion t USING(id_tipificacion)
            JOIN cuentas.cuenta c USING(id_cuenta)
            JOIN personas.persona p ON (p.id_persona=c.id_deudor)
            LEFT JOIN medios_contacto.telefono te ON(te.id_persona=p.id_persona)
            JOIN campanas.proceso pr ON(pr.id_proceso=c.id_proceso)
        WHERE
            pr.id_campana='.$id_campana.' AND 
            g.fecha_inicio BETWEEN \''.$f_desde.' 00:00:01\' AND \''.$f_hasta.' 23:59:59\' 
            AND te.origen=\'BASE\''.$condicion.$condicion2;
        $gestiones = array();
        foreach ($db->query($q) as $r){
            if (!in_array($r['tel_number'],$gestiones[$r['identificacion']]['telefono_gestion'])){
                $gestiones[$r['identificacion']]['telefono_gestion'][]=$r['tel_number'];
            }
            if ($r['tel_number']==$r['telefono']) continue;
            if (!in_array($r['telefono'],$gestiones[$r['identificacion']]['telefonos_base'])){
                $gestiones[$r['identificacion']]['telefonos_base'][]=$r['telefono'];
            }
        }
        return $gestiones;
    }


    function getCuentasSinGestion($id_campana){
        if ($id_campana=='') return array();
        global $db;
        $q = 'SELECT 
            pe.identificacion, c.*,te.telefono,te.origen
        FROM cuentas.cuenta c
            JOIN campanas.proceso p USING(id_proceso)
            JOIN personas.persona pe ON(pe.id_persona=c.id_deudor)
            LEFT JOIN medios_contacto.telefono te ON(te.id_persona=pe.id_persona)
        WHERE 
            p.id_campana='.$id_campana.'
            AND NOT EXISTS (
                SELECT 1 FROM gestiones.gestion g 
                JOIN cuentas.cuenta as cu ON(cu.id_cuenta=g.id_cuenta)
                WHERE cu.cuenta=c.cuenta
            )';
        $result = array();
        foreach ($db->query($q) as $r){
            if (!in_array($r['telefono'],$result[$r['identificacion']]['telefonos_base'])){
                $result[$r['identificacion']]['telefonos_base'][]=$r['telefono'];
            }
        }
        return $result;
    }

    function getTelefonosByIdentificacion($identificacion){
        $identificacion=trim($identificacion);
        if ($identificacion=='') return array();
        global $db;
        $q = 'SELECT 
            p.identificacion,t.telefono
        FROM personas.persona p
        JOIN medios_contacto.telefono t USING(id_persona)
        WHERE p.identificacion=\''.$identificacion.'\'';
        $result = array();
        foreach ($db->query($q) as $r){
            if (!in_array($r['telefono'],$result[$r['identificacion']]['telefonos_base'])){
                $result[$r['identificacion']]['telefonos_base'][]=$r['telefono'];
            }
        }
        return $result;
    }