<?php
    $_T['maintitle']='Seguridad y acceso - Privilegios - Modificar privilegio';
    if(!Auth::hasPrivileges('AUTH_SEGURIDAD_PRIVILEGIOS_MODIFICAR')) throw new Exception('No autorizado - AUTH_SEGURIDAD_PRIVILEGIOS_MODIFICAR');

    try {

        //get privilegio
        if ($_GET['id_privilegio']=='') throw new exception('id_privilegio not defined');
        $privilegio = new Modelo_Privilegios($_GET['id_privilegio']);

        if(!$_POST['save']=='1') throw new Exception('');
        if(trim($_POST['descripcion'])=='') throw new Exception('Ingrese descripción');
        if(trim($_POST['define_privilegio'])=='') throw new Exception('Ingrese define privilegio');
    
        $DB = db::getInstance();
        $q = 'SELECT * FROM auth.auth_privilegios_grupos WHERE define_privilegio=\''.$privilegio->define_privilegio.'\'';
        $q0 = $db->query($q);
        $actualizar_privilegio_grupo=false;
        //privilegio_grupo
        if ($db->numRows($q0)!=0){
            $q = 'DELETE FROM auth.auth_privilegios_grupos WHERE define_privilegio=\''.$privilegio->define_privilegio.'\'';
            $db->query($q);
            $actualizar_privilegio_grupo=true;
        }
        $privilegio->descripcion=trim($_POST['descripcion']);
        $privilegio->define_privilegio=trim(strtoupper($_POST['define_privilegio']));
        $privilegio->status=$_POST['status'];
        // si se elimina de privilegio_grupo insetamos el nuevo privilegio del grupo
        if ($actualizar_privilegio_grupo){
            foreach ($db->fetchAll($q0) as $row){
                $id_grupo = $row['id_grupo'];
                $q = 'INSERT INTO auth.auth_privilegios_grupos(define_privilegio,id_grupo)VALUES(\''.trim(strtoupper($_POST['define_privilegio'])).'\','.$id_grupo.')';
                $db->query($q);
            }
        }

        $_T['maincontent'].='
            <div class="alert alert-success" role="alert">Privilegio "<b>'.$privilegio->id_privilegio.' - '.$privilegio->descripcion.'</b>" modificado satisfactoriamente!</div><hr>
            <a href="?mod=seguridad_acceso/privilegios/index">Ir a Privilegios</a>';

    }catch(Exception $e) {
        if($e->getMessage()!='') $_T['maincontent'].='<div class="alert alert-danger" role="alert">'.$e->getMessage().'</div>';
        $_T['maincontent'].='
            <form method="POST">
            <input type="hidden" name="save" value="1">
            <table class="table_form">
                <tr>
                    <td>
                        <label>Descripción:</label>
                        <input type="text" placeholder="Ingrese Privilegio" class="form-control" maxlength="50" name="descripcion" value="'.(($_POST['descripcion']=='')?$privilegio->descripcion:$_POST['descripcion']).'" >
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Definición de privilegio:</label>
                        <input type="text" class="form-control" name="define_privilegio" value="'.(($_POST['define_privilegio']=='')?$privilegio->define_privilegio:$_POST['define_privilegio']).'">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>Fecha Creación:</label>
                        <input type="text" class="form-control" name="fecha_agregado" readonly value="'.($privilegio->fecha_agregado).'">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>ESTADO:</label>
                        <select name="status" class="form-control">
                            <option value="1" '.($privilegio->status=='1'?'selected':'').'>Activo</option>
                            <option value="0" '.($privilegio->status=='0'?'selected':'').'>Inactivo</option>
                        </select>
                    </td>
                </tr>
            </table>
            <br>
            <button class="btn btn-primary">Guardar</button>
            </form>';
    }

    $_T['bottom_jscript'] .= '
        test_js();
        function test_js(){
            console.log("ejecutando js");
        }
    ';