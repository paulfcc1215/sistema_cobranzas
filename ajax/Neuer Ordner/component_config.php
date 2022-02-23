<?php
require '../config.php';
try {
    if(!class_exists($_POST['component'])) throw new Exception('Componente inválido');
    $c=new $_POST['component'];
    $html=array();
    $html[]='<b>Descripción: </b><br>'.$c->getCommonDescription().'<br><br>';
    $images=$c->getImagesSample();
    if(!empty($images)) {
        $html[]='<b>Ejemplo:</b><br><img src="data:image;base64, '.base64_encode($images[0]).'"><br><br>';
    }
    $html[]='<b>Configuración</b><hr style="margin: 0px; border-color: #ccc; margin-bottom: 5px;">';
    foreach($_POST as $k=>$v) {
        if(in_array($k,array('step'))) {
            $step=$v;
            continue;
        }
        if(in_array($k,array('component'))) continue;
        $state[$k]=$v;
    }
    $html[]=$c->configGUI($step,$state);
    
    echo implode('',$html);
    
}catch(Exception $e) {
    echo $e->getMessage();
    die();
}
