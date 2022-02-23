<?php

    if ($_GET['op2']=='descarga_modelo'){
        $model_file='modules/parametros/direcciones/formato_carga.txt';
        header("Content-Type: application/octet-stream");
        header('Content-Disposition: attachment; filename="'.basename($model_file).'"');
        header('Content-Length:' . filesize($model_file));
        readfile($model_file);
        die();
    }


    $_T['maincontent'] .= '
    <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/direcciones/index','op'=>'cargador')).'" enctype="multipart/form-data">
        <input type="hidden" name="save" value="1"/>
        Descargar formato de carga <a href="?'.Helpers::arr_to_url($_GET,array(),array('mod'=>'parametros/direcciones/index','op'=>'cargador','op2'=>'descarga_modelo')).'">aquí</a>
        <br>

        <img src="modules/parametros/direcciones/demo_carga_masiva.png" width="100%" height="100%" style="border:1px solid blue; padding:10px;">

        <br><br><br>
        <label>Selecciona el archivo de carga:
        <input type="file" class="form-control" name="archivo"/>
        </label><br><br>
        <button class="btn btn-primary">Procesar</button>
    </form>
    ';