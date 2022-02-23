<?php

    if(!Auth::hasPrivileges('AUTH_SEGURIDAD_PERFILES_CREAR')) throw new Exception('No autorizado - AUTH_SEGURIDAD_PERFILES_CREAR');

    $_T['maintitle']='Seguridad y acceso - Perfiles - Nuevo Perfil';

    try {

        //get session
        $SM = SessionManager::getInstance();

        //get privilegios
        foreach(Modelo_Privilegios::getAll() as $id_p => $p){
            $privilegios[$p->define_privilegio] = $id_p .' - '. $p->descripcion;
        }
        
        $aux = $privilegios;
        foreach ($_POST['mis_privilegios'] as $p){
            unset($aux[$p]);
        }

        if(!$_POST['save']=='1') throw new Exception('');
        if(trim($_POST['descripcion'])=='') throw new Exception('Ingrese descripción del perfil');
        if(!preg_match("|^[a-zA-Z]+(\s*[a-zA-Z]*)*[a-zA-Z]+$|",trim($_POST['descripcion']))) throw new Exception('La descripción de bebe contener solo caracteres alfabéticos entre [A-Z]');

        $nuevo_perfil = Modelo_Grupos::create(
            trim($_POST['descripcion']),
            date('Y-m-d H:i:s'),
            $_POST['status'],
            $_POST['creado_por'],
            $_POST['mis_privilegios']
        );

        $_T['maincontent'].='
            <div class="alert alert-success" role="alert">
                Pefil "<b>'.$nuevo_perfil->id_grupo.' - '.$nuevo_perfil->descripcion.'</b>" creado satisfactoriamente!
            </div><br><br>
            <a href="?mod=seguridad_acceso/perfiles/index">Ir a Perfiles</a>';

    }catch(Exception $e) {

        if($e->getMessage()!='') $_T['maincontent'].='<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';

        $_T['maincontent'].='
            <form method="POST">
                <input type="hidden" name="save" value="1">
                <div class="form-group row">
                    <div class="col-sm-4">
                        <label>Fecha Creación:</label>
                        <input type="text" class="form-control" name="fecha_agregado" readonly value="'.date("Y-m-d H:i:s").'">
                    </div>
                    <div class="col-sm-4">
                        <label>Creado por:</label>
                        <input type="hidden" name="creado_por" value="'.$SM->user['id_usuario'].'" />
                        <input type="text" class="form-control" readonly value="'.$SM->user['usr_logname'].'" />
                    </div>
                    <div class="col-sm-4">
                        <label>Estado:</label>
                        <select name="status" class="form-control">
                            <option value="1" '.($_POST['status']=='1'?"selected":"").'>Activo</option>
                            <option value="0" disabled '.($_POST['status']=='0'?"selected":"").'>Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-12">
                        <label for="descripcion">Descripción del perfil</label>
                        <textarea placeholder="Ingrese Perfil" class="form-control" name="descripcion" >'.(($_POST['descripcion']=='')?"":$_POST['descripcion']).'</textarea>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-6">
                        <label>Asigne Privilegios: (Doble click para asignar)</label>
                        <select id="privilegios" class="form-control" size="12" multiple ondblclick="agregarPrivilegio()">
                            '.UI_Helper::array_to_options($aux,null,false).'
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