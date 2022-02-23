<?php

    $_T['maincontent'] .= '
    <table>
        <tr>
            <td>
                <lable>Ingrese Identificación:
                <input type="text" class="form-control" id="identificacion"/>
                </label><br>
                <button class="btn btn-primary" type="button" onclick="buscar_direcciones($(\'#identificacion\').val())">Buscar</button><br><br>
            </td>
        </tr>
        <tr>
            <td>
                <div id="direciones"></div>
            </td>
        </tr>
    </table>';

    $_T['bottom_jscript'] .= '

    function buscar_direcciones(me){
        $("#direciones").empty();
        if (me==""){
            return false;
        }
        $.ajax({
            method:"post",
            url:"ajax.php",
            data:{
                a:"getDireccionesByIdentificacion",
                identificacion:me
            },
            success: function(result){
                var result = JSON.parse(result);
                $("#direciones").append(result.data);
            }
        })
    }
    
    ';