<?php
Auth::enforcePrivileges('AUTH_PARAMETROS_TELEFONOS_INDEX');

$_T['maintitle']='Parámetros del sistema - Teléfonos';

$db = DB::getInstance();

$_T['maincontent'] .= '
    <table style="width:100%;">
        <tr>
            <td style="width:180px;padding-right:20px;border-right:1px solid blue;" valign="top">
                <ul>
                    <li><a href="?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/telefonos/index','op'=>'buscador')).'">Buscador</a></li>
                    <!--<li><a href="?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/telefonos/index','op'=>'cargador')).'">Cargador x lotes</a></li>
                    <li><a href="?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/telefonos/index','op'=>'registro')).'">Registrar teléfono</a></li>-->
                </ul>
            </td>
            <td style="padding-left:20px;align:top;" valign="top">';
                try{
                    if (!$_POST['save']=='1') throw new exception('');
                    //validaciones para carga de referidos
                    if ($_GET['op']=='buscador') {
                        require 'buscador_process.php';
                    }
                    if ($_GET['op']=='cargador') {
                        require 'cargador_process.php';
                    }
                    if ($_GET['op']=='registro') {
                        require 'registro_process.php';
                    }
                
                }catch(Exception $ex){
                    if($ex->getMessage()!=''){
                        $_T['maincontent'] .= '<div class="alert alert-danger" role="alert">'.$ex->getMessage().'</div>';
                    }
                    switch ($_GET['op']){
                        case 'buscador':
                            require 'buscador_tpl.php';
                        break;
                        case 'cargador':
                            require 'cargador_tpl.php';
                        break;
                        case 'registro':
                            require 'registro_tpl.php';
                        break;
                    }
                }
            $_T['maincontent'] .= '
            </td>
        </tr>
    </table';