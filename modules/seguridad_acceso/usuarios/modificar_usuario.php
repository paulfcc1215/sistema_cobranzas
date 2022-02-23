<?php

	if(!Auth::hasPrivileges('AUTH_SEGURIDAD_USUARIOS_MODIFICAR')) throw new Exception('No autorizado - AUTH_SEGURIDAD_USUARIOS_MODIFICAR');

    $_T['maintitle']='Seguridad y acceso - Usuarios - Modificar usuario';

    $SM=SessionManager::getInstance();
    foreach (Modelo_Grupos::getAll() as $key => $value) {
        $grupos[$key]=$key.' - '.$value->descripcion;
    }
    $aux = $grupos;
	$usuario = new Modelo_Usuarios($_GET['id_usuario']);

    //get grupos del usuario
    foreach (Modelo_Grupos::getGruposPorUsuario($usuario->usr_logname) as $key => $value) {
        $grupos_usuario[$key]=$key.' - '.$value->descripcion;
    }
    foreach ($grupos_usuario as $id_u => $u){
        unset($grupos[$id_u]);
    }

    try {

        if(!$_POST['save']=='1') throw new Exception('');

        foreach ($_POST as $k =>&$v){
            if (!is_array($v))
                $v = trim($v);
            unset($v);
        }

        if(trim($_POST['identificacion'])=='') throw new Exception('Ingrese identificación del usuario');
        if(trim($_POST['nombre_completo'])=='') throw new Exception('Ingrese nombre completo de usuario');
        if(trim($_POST['usr_logname'])=='') throw new Exception('Ingrese usuario');
        //if(!preg_match("|^[a-zA-Z]+(\s*[a-zA-Z]*)*[a-zA-Z]+$|",trim($_POST['usr_logname']))) throw new Exception('El "Usuario" de bebe contener solo caracteres alfabéticos entre [A-Z]');
        if(empty($_POST['mis_grupos'])) throw new Exception('No ha seleccionado perfil de usuario');

		$db = DB::getInstance();
        $db->startTransaction();
        try{

            $q = 'DELETE FROM auth.auth_grupos_usuarios WHERE usr_logname=\''.$usuario->usr_logname.'\'';
            $db->query($q);
            $usuario->identificacion = $_POST['identificacion'];
            $usuario->nombre_completo = strtoupper($_POST['nombre_completo']);
            $usuario->usr_logname = $_POST['usr_logname'];
            if ($_POST['pass']!=$usuario->pass){
                $usuario->pass = $_POST['pass'];
            }
            $usuario->status = $_POST['status'];
            $usuario->force_pw_change = ($_POST['force_pw_change']==''?'0':'1');
            foreach($_POST['mis_grupos'] as $id_g){
                $q = 'INSERT INTO auth.auth_grupos_usuarios(usr_logname,id_grupo)VALUES(\''.$_POST['usr_logname'].'\','.$id_g.')';
                $db->query($q);
            }
            $db->commit();
        }catch(Exception $ex){
            $db->rollback();
            throw new exception ($ex->getMessage());
        }
        

        $_T['maincontent'].='<div class="alert alert-success" role="alert">Usuario "<b>'.$usuario->usr_logname.'</b>" modificado satisfactoriamente!</div><hr>
            <a href="?mod=seguridad_acceso/usuarios/index">Volver a Usuarios</a>';

    }catch(Exception $e) {

        if($e->getMessage()!='') $_T['maincontent'].='<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';

        $_T['maincontent'].='
        <form method="POST">
            <input type="hidden" name="save" value="1">
            <div class="form-group row">
                <div class="col-sm-4">
                    <label>Fecha Creación:</label>
                    <input type="text" class="form-control" readonly value="'.$usuario->fecha_agregado.'">
                </div>
                <div class="col-sm-4">
                    <label>Creado por:</label>
                    <input type="text" class="form-control" readonly value="'.$usuario->agregado_por.'" />
                </div>
                <div class="col-sm-4">
                    <label>Estado:</label>
                    <select name="status" class="form-control">
                        <option value="1" '.($_POST['status']=='1'?"selected":"").'>Activo</option>
                        <option value="0" '.($_POST['status']=='0'?"selected":"").'>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="descripcion">Identificación:</label>
                    <input type="text" placeholder="Ingrese número de cédula/identificación" class="form-control" name="identificacion" value="'.(($_POST['identificacion']=='')?$usuario->identificacion:$_POST['identificacion']).'" required/>
                </div>
                <div class="col-sm-8">
                    <label for="descripcion">Nombre Completo:</label>
                    <input type="text" placeholder="Ingrese nombre del usuario" class="form-control" name="nombre_completo" value="'.(($_POST['nombre_completo']=='')?$usuario->nombre_completo:$_POST['nombre_completo']).'" required/>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-4">
                    <label>Usuario:</label>
                    <input type="text" class="form-control" name="usr_logname" value="'.(($_POST['usr_logname']=='')?$usuario->usr_logname:$_POST['usr_logname']).'">
                </div>
                <div class="col-sm-4">
                    <label>Contraseña:</label>
                    <input type="password" class="form-control" name="pass" value="'.$usuario->pass.'" />
                </div>
                <div class="col-sm-4">
                    <div class="form-check form-switch">
                        <label class="form-check-label" for="force_pw_change">Exigir cambio de clave</label><br>
                        <input class="form-check-input" type="checkbox" name="force_pw_change" id="force_pw_change" '.($usuario->force_pw_change=='1'?'checked':'').'/>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-6">
                    <label>Seleccione Perfil: (Doble click para asignar)</label>
                    <select id="grupos" class="form-control" size="8" multiple ondblclick="agregarGrupo()">';
                        if (!empty($_POST['mis_grupos'])){
                            foreach ($_POST['mis_grupos'] as $p){
                                unset($aux[$p]);
                            }
                        }
                        foreach ($grupos as $id_g => $g){
                            $_T['maincontent'].='<option value="'.$id_g.'">'.$g.'</option>';
                        }
                    $_T['maincontent'].='
                    </select>
                </div>
                <div class="col-sm-6">
                    <label>Perfil asignado: (Doble click para eliminar)</label>
                    <select id="mis_grupos" name="mis_grupos[]" class="form-control" size="8" multiple ondblclick="quitarGrupo()">';
                    if (!empty($_POST['mis_grupos'])){
                        foreach ($_POST['mis_grupos'] as $p){
                            $_T['maincontent'].='<option value="'.$p.'">'.$grupos[$p].'</option>';
                        }
                    }else{
                        foreach ($grupos_usuario as $id_g => $g){
                            $_T['maincontent'].='<option value="'.$id_g.'" selected>'.$g.'</option>';
                        }
                    }
                    $_T['maincontent'].='
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" >Guardar</button>
        </form>
    ';
    }

    $_T['bottom_jscript'] .= '

        function agregarGrupo(){
            $("#grupos").children().each(function(i,o){
                if(o.selected){
                    $("#mis_grupos").append(o);
                }
            });
        }
        function quitarGrupo(){
            $("#mis_grupos").children().each(function(i,o){
                if(o.selected){
                    $("#grupos").append(o);
                }
            });
        }
    ';