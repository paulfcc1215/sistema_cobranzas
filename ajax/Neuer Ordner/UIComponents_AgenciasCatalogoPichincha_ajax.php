<?php
require '../config.php';
try {
    $db=Db::getInstance();
    $catalogo=new Catalogo($_REQUEST['id_catalogo'],$db);
    $buscar_en=explode(',',$_REQUEST['buscar_en']);
    $_REQUEST['q']=strtolower($_REQUEST['q']);
    foreach($catalogo as $k=>$v) {
        foreach($buscar_en as $b) {
            if(strpos(strtolower($v[$b]),$_REQUEST['q'])!==false) {
                $results[]=$v;
                break;
            }
        }
    }
    
    
    if(count($results)==0) {
        echo '<h2>No se encontraron resultados</h2>';
        die();
    }
    
    echo '<table class="UIComponents_AgenciasCatalogoPichincha_tbl" border="1">
    <tr>
    ';
    foreach($results[0] as $k=>$tf) {
        $k=str_replace('_',' ',$k);
        $k=explode(' ',$k);
        foreach($k as &$kk) {
            $kk[0]=strtoupper($kk[0]);
            unset($kk);
        }
        $k=implode(' ',$k);
        echo '<th>'.$k.'</th>';
    }
    echo '
    </tr>
    ';
    foreach($results as $r) {
        echo '<tr class="UIComponents_AgenciasCatalogoPichincha_tbl_clickable" onclick="UIComponents_AgenciasCatalogoPichincha_select(\''.$_REQUEST['component_id'].'\',\''.$r[$_REQUEST['campos_enviar']].'\',\''.$r[$_REQUEST['campos_mostrar']].'\')">';
        foreach($r as $k=>$v) {
            echo '<td>'.$v.'</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    
    
}catch(Exception $e){
    echo $e->getMessage();
    die();
}