<?php
/*
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://10.1.210.26/cobranzas/classes/WS/orionapp/");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS,"postvar1=value1&postvar2=value2&postvar3=value3");
	$res = curl_exec($ch);
	curl_close($ch);

	// print_r($_SERVER);
	// $res = file_get_contents("http://10.1.210.26/cobranzas/classes/WS/orionapp/");

	echo $res;

*/

require 'config.php';

$db = DB::getInstance();

$data = (file_get_contents('CarteraPortoaguas27082021.txt'));
$data = explode("\r\n",$data);
foreach($data as &$d) {
	$d = explode("\t",$d);
	unset($d);
}
$head = array_shift($data);
$nline=0;

$data_nm = array('fecha_emision_mes','CATASTRO','VENCIMIENTO_FACTURA','fecha de facturacion','SALDO_CONVENIO','DEUDA_PORTOAGUAS','OBLIGACIONES_VENCIDAS','OBLIGACIONES_CORRIENTES','FACTURAS_VENCIDAS','NUM_MEDIDOR','RECLAMO','ESTADO','SERVICIO','TIPO_CONSUMO','CUENTA');
$resultado = array();
foreach($data as $aux) {
	$nline++;
	if (count($head)!=count($aux)) throw new exception('Error en linea: '.$nline);
	$line = array();
	foreach ($aux as $l => $v){
		$line[$head[$l]]=$v;
	}
	// if ($line['CUENTA']!='2506151') continue;
	$meta_data = get_data_nm($line['CUENTA']);
	if (empty($meta_data)){
		$resultado['metada_NO_ingresada']++;
		$resultado['metada_NO_ingresada']['cuentas'][]=$line['CUENTA'];
		continue;
	}
	$db->startTransaction();
	try{
		$db->query('DELETE FROM cargas.carga_no_mapeada WHERE id_no_mapeada='.$meta_data['id_no_mapeada']);
		$insert=array();
		$order = 1;
		foreach($line as $k => $v){
			if (in_array($k,$data_nm)){
				$db->query('INSERT INTO cargas.carga_no_mapeada(id_carga,id_cuenta,campo,valor,"order")VALUES('.$meta_data['id_carga'].','.$meta_data['id_cuenta'].',\''.$k.'\',\''.$v.'\','.$order.')');
				$order++;
			}
		}
		$resultado['metada_ingresada']++;
		$resultado['metada_ingresada']['cuentas'][]=$meta_data['id_cuenta'];
		$db->commit();
	}catch(Exception $ex){
		throw new Exception($ex->getMessage());
		$db->rollback();
	}
}

print_arr($resultado);
die();


function get_data_nm($cuenta){
	GLOBAL $db;
	$q = 'SELECT cnm.* 
	FROM cuentas.cuenta c
	JOIN cargas.carga_no_mapeada cnm ON(c.id_cuenta=cnm.id_cuenta)
	WHERE c.id_proceso=117 AND
	c.cuenta=\''.$cuenta.'\'';
	$q0 = $db->query($q);
	$result=array();
	if ($db->numRows($q0)==1) {
		$result = $db->fetchOne($q0);
	}
	return $result;
}