<?php

    if(!Auth::hasPrivileges('AUTH_SEGURIDAD_USUARIOS_CREAR')) throw new Exception('No autorizado - AUTH_SEGURIDAD_USUARIOS_CREAR');
    
    $_T['maintitle']='Seguridad y acceso - Usuarios - Nuevo usuario';

    $SM=SessionManager::getInstance();
    foreach (Modelo_Grupos::getAll() as $key => $value) {
        $grupos[$key]=$key.' - '.$value->descripcion;
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
        //if(!preg_match("|^[a-zA-Z]+(\s*[a-zA-Z]*)*[a-zA-Z]+$|",trim($_POST['nombre_completo']))) throw new Exception('El nombre del usuario de bebe contener solo caracteres alfabéticos entre [A-Z]');
        if(trim($_POST['usr_logname'])=='') throw new Exception('Ingrese usuario');
        // if(!preg_match("|^[a-zA-Z]+(\s*[a-zA-Z]*)*[a-zA-Z]+$|",trim($_POST['usr_logname']))) throw new Exception('El "Usuario" de bebe contener solo caracteres alfabéticos entre [A-Z]');
        $aux_user=Modelo_Usuarios::getByIdentificacion(trim($_POST['identificacion']));
        if(count($aux_user)>0){
            throw new Exception('Ya existe un usuario con la Identificación ingresada');
        }
        if(empty($_POST['mis_grupos'])) throw new Exception('No ha seleccionado perfil de usuario');

        //$identificacion,$nombre_completo,$usuario,$pass,$tatus,$force_pw_change,$array_grupos_usuario
        $usuario = Modelo_Usuarios::create(
            $_POST['identificacion'],
            strtoupper($_POST['nombre_completo']),
            $_POST['usr_logname'],
            $_POST['identificacion'],
            ($_POST['force_pw_change']==''?'0':'1'),
            $_POST['status'],
            $_POST['mis_grupos']
        );

        $usuario->agregado_por=Auth::getUsername();
        
        $_T['maincontent'].='<div class="alert alert-success" role="alert">Usuario "<b>'.$usuario->usr_logname.'</b>" creado satisfactoriamente!</div><hr>
            <a href="?mod=seguridad_acceso/usuarios/index">Volver</a>';

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
                        <option value="0" '.($_POST['status']=='0'?"selected":"").' disabled>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="descripcion">Identificación:</label>
                    <input type="text" placeholder="Ingrese número de cédula/identificación" class="form-control" name="identificacion" value="'.(($_POST['identificacion']=='')?"":$_POST['identificacion']).'" required/>
                </div>
                <div class="col-sm-8">
                    <label for="descripcion">Nombre Completo:</label>
                    <input type="text" placeholder="Ingrese nombre del usuario" class="form-control" name="nombre_completo" value="'.(($_POST['nombre_completo']=='')?"":$_POST['nombre_completo']).'" required/>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-4">
                    <label>Usuario:</label>
                    <input type="text" class="form-control" name="usr_logname" value="'.(($_POST['usr_logname']=='')?"":$_POST['usr_logname']).'">
                </div>
                <div class="col-sm-4">
                    <label>Contraseña:</label>
                    <input type="text" class="form-control" readonly value="Por defecto número de cédula" />
                </div>
                <div class="col-sm-4">
                    <div class="form-check form-switch">
                        <label class="form-check-label" for="force_pw_change">Exigir cambio de clave</label><br>
                        <input class="form-check-input" type="checkbox" name="force_pw_change" id="force_pw_change" />
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-sm-6">
                    <label>Seleccione Perfil: (Doble click para asignar)</label>
                    <select id="grupos" class="form-control" size="8" multiple ondblclick="agregarGrupo()">
                        '.UI_Helper::array_to_options($grupos,null,false).'
                    </select>
                </div>
                <div class="col-sm-6">
                    <label>Perfil asignado: (Doble click para eliminar)</label>
                    <select id="mis_grupos" name="mis_grupos[]" class="form-control" size="8" multiple ondblclick="quitarGrupo()">
                    ';
                    if (!empty($_POST['mis_grupos'])){
                        foreach ($_POST['mis_grupos'] as $p){
                            $_T['maincontent'].='<option value="'.$p.'">'.$grupos[$p].'</option>';

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