<?php
require '../../../../config.php';
header('Content-Type: application/json');
$db = DB::getInstance();
$query='
	SELECT 
	proceso.id_proceso, campana.id_campana, id_cuenta, monto_compromiso, fecha_inicio, persona.identificacion
	FROM 
	gestiones.gestion
	JOIN cuentas.cuenta USING (id_cuenta)
	JOIN campanas.proceso USING (id_proceso)
	JOIN campanas.campana USING (id_campana)
	JOIN personas.persona ON (cuenta.id_deudor = persona.id_persona)
	WHERE
	proceso.id_proceso IN (
		(SELECT MAX(id_proceso) FROM campanas.proceso WHERE id_campana=15),
		(SELECT MAX(id_proceso) FROM campanas.proceso WHERE id_campana=16)
	)
	AND id_tipificacion=243
	-- AND identificacion=\'0928939453\'
	ORDER BY id_cuenta, fecha_inicio DESC
';

$data=array();
$q0=$db->query($query);
foreach($q0 as $q) {
	$data[$q['id_cuenta']][]=array(
		'id_campana'=>$q['id_campana'],
		'id_proceso'=>$q['id_proceso'],
		'identificacion'=>$q['identificacion'],
		'fecha'=>$q['fecha_inicio'],
		'monto_compromiso'=>$q['monto_compromiso']
	);
}


$sums=array(
	'totales'=>0,
	'unicas'=>0,
	'excluyendo_multiples'=>0,
	'diferencia_de_los_multiples'=>0
);

// $db->query('DROP TABLE IF EXISTS public.tmp_db');
// $db->query('CREATE TABLE public.tmp_db (identificacion TEXT, monto float)');

$dataByIdent=array();
foreach($data as $d) {
	foreach($d as $dd) {
		$sums['totales']+=$dd['monto_compromiso'];
	}
	
	$sums['unicas']+=$d[0]['monto_compromiso'];
	$dataByIdent[$d[0]['identificacion']]+=$d[0]['monto_compromiso'];
	
	if(count($d)==1) {
		$sums['excluyendo_multiples']+=$d[0]['monto_compromiso'];
	}

	if(count($d)>1) {
		foreach($d as $dd) {
			$sums['diferencia_de_los_multiples']+=$dd['monto_compromiso'];
		}
	}
	
	$sums['total_gestiones']++;
}


/*
foreach($dataByIdent as $ident=>$monto) {
	$db->query('INSERT INTO public.tmp_db (identificacion,monto) values (\''.$ident.'\','.$monto.')');	
}
*/

foreach($sums as &$s) {
	$s=str_replace('.',',',$s);
}
print_r($sums);