<?php
$db=DB::getInstance();
$query='SELECT
ig.grupo_nombre,
it.nombre AS instrumento_nombre,
r.*
FROM "'._DB_SCHEMA_DEFAULT.'".reportes r
JOIN instrumento_tipo it ON (it.id_instrumento_tipo=r.id_instrumento_tipo)
JOIN instrumento_grupo ig ON (ig.id_instrumento_grupo=it.id_instrumento_grupo)
WHERE r.status=\'1\'
ORDER BY it.id_instrumento_tipo
';
$q0=$db->query($query);
while($qa0=$db->fetchOne($q0)) {
    $_reportes[$qa0['grupo_nombre']][$qa0['instrumento_nombre']][]=$qa0;
}
try {

    switch($_GET['step']) {
        default:
            $_T['maintitle']='REPORTES';
            $_T['maincontent']='<b>Seleccione un reporte</b><br><br>';
            foreach($_reportes as $grupo=>$instrumentos) {
                $_T['maincontent'].='<ul>';
                $_T['maincontent'].='<il><b>'.$grupo.'</b></il>';
                $_T['maincontent'].='<ul>';
                foreach($instrumentos as $instrumento=>$reportes) {
                    $_T['maincontent'].='<li><b>'.$instrumento.'</b></li>';
                    $_T['maincontent'].='<ul>';
                    foreach($reportes as $reporte) {
                        $_T['maincontent'].='<li><a href="?mod=reportes/dispatcher&id_reporte='.$reporte['id_reporte'].'">'.$reporte['nombre_reporte'].'</a></li>';
                    }
                    $_T['maincontent'].='</ul>';
                    
                }
                $_T['maincontent'].='</ul>';
                
                
                
                $_T['maincontent'].='</ul>';
                
            }

        break;
    }
}catch(Exception $e) {
    $_T['maintitle']='REPORTES';
    $_T['maincontent'].='
    <h2 style="color: maroon;">'.$e->getMessage().'</h2>
    <hr>
    <a href="javascript:history.go(-1)">Regresar</a>
    ';
    
    
    
}
