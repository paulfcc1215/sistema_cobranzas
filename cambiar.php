<?php
require 'config.php';
$db = DB::getInstance();
$source=file_get_contents('CANT_Cambios.txt');
$source=explode("\r\n",$source);
foreach($source as &$s) {
    $s=explode("\t",$s);
}
$head=array_shift($source);
print_r($head);
unset($s);
$id_proceso='37';
$db->prepare('find','SELECT * FROM cuentas.cuenta WHERE id_proceso=\''.$id_proceso.'\' AND cuenta=$1');
$db->prepare('update','UPDATE cargas.carga_no_mapeada SET valor=$1 WHERE id_cuenta=$2 AND campo=$3');
$total=count($source);
$c=0;
foreach($source as $s) {
    $c++;
    $row=array();
    foreach($head as $k=>$v) {
        $row[$v]=$s[$k];
    }
    echo '('.$c.' de '.$total.') Cuenta: '.$row['contrato'].' ';
    $q0=$db->execute('find',array($row['contrato']));
    if($q0->numRows()==0) {
        echo 'No encontrada'."\n\n";
        die();
    }
    $id_cuenta=$q0->current()['id_cuenta'];
    echo '| Id cuenta: '.$id_cuenta;
    $db->execute('update',array(
        $row['saldo total'],
        $id_cuenta,
        'saldo total'
    ));
    $db->execute('update',array(
        $row['valor a pagar'],
        $id_cuenta,
        'valor a pagar'
    ));
    echo ' Listo';
    echo "\n";

    
}

