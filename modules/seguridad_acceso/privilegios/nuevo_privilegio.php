<?php
    $_T['maintitle']='Seguridad y acceso - Privilegios - Nuevo privilegio';

    if(!Auth::hasPrivileges('AUTH_SEGURIDAD_PRIVILEGIOS_CREAR')) throw new Exception('No autorizado - AUTH_SEGURIDAD_PRIVILEGIOS_CREAR');

    try {

        if(!$_POST['save']=='1') throw new Exception('');
        if(trim($_POST['descripcion'])=='') throw new Exception('Ingrese descripción');
        if(trim($_POST['define_privilegio'])=='') throw new Exception('Ingrese define privilegio');
        if(!preg_match("|^[a-zA-Z]+(\s*[a-zA-Z]*)*[a-zA-Z]+$|",trim($_POST['define_privilegio']))) throw new Exception('Definición de privilegio de bebe contener solo caracteres alfabéticos entre [A-Z]');
        $nuevo_privilegio = Modelo_Privilegios::create(
            trim($_POST['descripcion']),
            'AUTH_'.trim(strtoupper(str_replace(array(' '),'_',$_POST['define_privilegio']))),
            date('Y-m-d H:i:s'),
            $_POST['status']
        );
        $_T['maincontent'].='
            <div class="alert alert-success" role="alert">
                Pefil "<b>'.$nuevo_privilegio->id_privilegio.' - '.$nuevo_privilegio->descripcion.'</b>" creado satisfactoriamente!
            </div>
            <hr>
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
                            <input type="text" placeholder="Ingrese Privilegio" class="form-control" maxlength="50" name="descripcion" value="'.(($_POST['descripcion']=='')?"":$_POST['descripcion']).'" >
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>Definición de privilegio:</label>
                            <input type="text" class="form-control" name="define_privilegio" value="'.(($_POST['define_privilegio']=='')?"":$_POST['define_privilegio']).'">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>Fecha Creación:</label>
                            <input type="text" class="form-control" name="fecha_agregado" readonly value="'.date("Y-m-d H:i:s").'">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label>ESTADO:</label>
                            <select name="status" class="form-control">
                                <option value="1" '.($_POST['status']=='1'?"selected":"").'>Activo</option>
                                <option value="0" disabled '.($_POST['status']=='0'?"selected":"").'>Inactivo</option>
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