<?php
require '../config.php';
$db=DB::getInstance();
$id_proceso=1;
$solo_con_telefonos=true;
$solo_sin_gestion=true;

$output=array(
	array(
		'contrato',
		'identificacion',
		'nombres',
		'valor',
		'telefonos',
		'id_cuenta',
	)
);
if($solo_sin_gestion) {
	$q=$db->query('SELECT * FROM cuentas.cuenta WHERE id_proceso='.$id_proceso.' AND id_cuenta NOT IN (SELECT id_cuenta FROM gestiones.gestion)');
}else{
	$q=$db->query('SELECT * FROM cuentas.cuenta WHERE id_proceso='.$id_proceso);
}
foreach($q as $cuenta) {
	$persona=$db->query('SELECT * FROM personas.persona WHERE id_persona='.$cuenta['id_deudor'])->current();
	$telefonos=array();
	foreach($db->query('SELECT * FROM medios_contacto.telefono WHERE id_persona='.$persona['id_persona']) as $t) {
		if(strlen($t['telefono'])==7) {
			$t['telefono']='04'.$t['telefono'];
		}
		$telefonos[]=$t['telefono'];
	}
	if($solo_con_telefonos && empty($telefonos))
		continue;
		
	$line=array();
	$line[]=$cuenta['cuenta'];
	$line[]=$persona['identificacion'];
	$line[]=$persona['primer_nombre'];
	$line[]=$cuenta['valor_actual'];
	$line[]=implode('|',$telefonos);
	$line[]=$cuenta['id_cuenta'];
	
	$output[]=$line;
}
foreach($output as &$o) {
	$o=implode("\t",$o);
	unset($o);
}

file_put_contents('output.txt',implode("\r\n",$output));