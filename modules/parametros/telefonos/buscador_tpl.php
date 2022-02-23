<?php
    if(!Auth::hasPrivileges('AUTH_PARAMETROS_TELEFONOS_BUSCADOR')) throw new Exception('No autorizado - AUTH_PARAMETROS_TELEFONOS_BUSCADOR');

    $_T['maincontent'] .= '
    <table>
        <tr>
            <td>
                <form method="post" action="?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/telefonos/index','op'=>'buscador')).'">
                    <input type="hidden" name="save" value="1" />
                    <lable>Ingrese Identificación:
                    <input type="text" class="form-control" name="identificacion"/>
                    </label><br>
                    <button class="btn btn-primary" >Buscar</button><br><br>
                </form>
            </td>
        </tr>
    </table>';