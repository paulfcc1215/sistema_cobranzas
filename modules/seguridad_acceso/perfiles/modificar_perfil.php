<?php

    if(!Auth::hasPrivileges('AUTH_SEGURIDAD_PERFILES_MODIFICAR')) throw new Exception('No autorizado - AUTH_SEGURIDAD_PERFILES_MODIFICAR');

    $_T['maintitle']='Seguridad y acceso - Perfiles - Modificar Perfil';

    try {

        //get perfil
        $perfil = new Modelo_Grupos($_GET['id_grupo']);

        //get usuario
        $usuario = new Modelo_Usuarios($perfil->creado_por);

        //get privilegios
        foreach (Modelo_Privilegios::getAll() as $id_p => $p){
            $privilegios[$p->define_privilegio] = $p->descripcion;
        }
        asort($privilegios);

        //get privilegios by grupo
        foreach (Modelo_Privilegios::getPrivilegiosPorGrupoId($_GET['id_grupo']) as $id_p => $p){
            $privilegios_grupo[$p->define_privilegio] = $id_p .' - '. $p->descripcion;
        }

        foreach ($privilegios_grupo as $define => $p){
            unset($privilegios[$define]);
        }

        if(!$_POST['save']=='1') throw new Exception('');
        if(trim($_POST['descripcion'])=='') throw new Exception('Ingrese descripción del perfil');
        if(!preg_match("|^[a-zA-Z]+(\s*[a-zA-Z]*)*[a-zA-Z]+$|",trim($_POST['descripcion']))) throw new Exception('La descripción de bebe contener solo caracteres alfabéticos entre [A-Z]');

        $perfil->descripcion=trim($_POST['descripcion']);
        $perfil->status=trim($_POST['status']);

        //create privilegios by grupo
        $db = DB::getInstance();
        $q = 'DELETE FROM auth.auth_privilegios_grupos WHERE id_grupo='.$perfil->id_grupo;
        $db -> query($q);
        foreach ($_POST['mis_privilegios'] as $p){
            $q = 'INSERT INTO auth.auth_privilegios_grupos (define_privilegio,id_grupo) VALUES(\''.$p.'\','.$perfil->id_grupo.')';
            $db -> query($q);
        }

        $_T['maincontent'].='
            <div class="alert alert-success" role="alert">
                Pefil "<b>'.$perfil->id_grupo.' - '.$perfil->descripcion.'</b>" modificado satisfactoriamente!
            </div><br>
            <a href="?mod=seguridad_acceso/perfiles/index">Ir a Perfiles</a>';

    }catch(Exception $e) {

        if($e->getMessage()!='') $_T['maincontent'].='<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';

        $_T['maincontent'].='
            <form method="POST">
                <input type="hidden" name="save" value="1">
                <div class="form-group row">
                    <div class="col-sm-4">
                        <label>Fecha Creación:</label>
                        <input type="text" class="form-control" readonly value="'.$perfil->fecha_agregado.'">
                    </div>
                    <div class="col-sm-4">
                        <label>CREADO POR:</label>
                        <input type="hidden" value="'.$perfil->creado_por.'" />
                        <input type="text" class="form-control" readonly value="'.$usuario->usr_logname.'" />
                    </div>
                    <div class="col-sm-4">
                        <label>ESTADO:</label>
                        <select name="status" class="form-control">
                            <option value="1" '.($perfil->status=='1'?"selected":"").'>Activo</option>
                            <option value="0" '.($perfil->status=='0'?"selected":"").'>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-12">
                        <label for="descripcion">Descripción del perfil</label>
                        <textarea placeholder="Ingrese Perfil" class="form-control" name="descripcion" >'.(($_POST['descripcion']=='')?$perfil->descripcion:$_POST['descripcion']).'</textarea>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-6">
                        <label>Asigne Privilegios: (Doble click para asignar)</label>
                        <select id="privilegios" class="form-control" size="12" multiple ondblclick="agregarPrivilegio()">
                            '.UI_Helper::array_to_options($privilegios,null,false).'
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label>Pivilegios Asignados: (Doble click para eliminar)</label>
                        <select id="mis_privilegios" name="mis_privilegios[]" class="form-control" size="12" multiple ondblclick="quitarPrivilegio()">
                        ';
                        if (!empty($_POST['mis_privilegios'])){
                            foreach ($_POST['mis_privilegios'] as $p){
                                $_T['maincontent'].='<option value="'.$p.'">'.$privilegios[$p].'</option>';

                            }
                        }else{
                            foreach($privilegios_grupo as $define => $p){
                                $_T['maincontent'].='<option value="'.$define.'">'.$p.'</option>';
                            }
                        }
                        $_T['maincontent'].='
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary">Guardar</button>
            </form>
        ';
    }

    $_T['bottom_jscript'] .= '
        function agregarPrivilegio(){
            $("#privilegios").children().each(function(i,o){
                if(o.selected){
                    $("#mis_privilegios").append(o);
                }
            });
        }
        function quitarPrivilegio(){
            $("#mis_privilegios").children().each(function(i,o){
                if(o.selected){
                    $("#privilegios").append(o);
                }
            });
        }
    ';