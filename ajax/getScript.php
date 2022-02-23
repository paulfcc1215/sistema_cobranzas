<?php
try {
	$cuenta=getCuenta($_request['id_cuenta']);
	if(!$cuenta) throw new Exception('Cuenta no existe');
	$data=array();
	foreach ($db->query('SELECT nm.* as campo FROM cargas.carga c JOIN cargas.carga_no_mapeada nm USING(id_carga) WHERE c.id_proceso='.$cuenta['id_proceso'].' AND nm.id_cuenta='.$_request['id_cuenta'].' ORDER BY id_cuenta') as $value) {
		$data[$value['campo']]=$value['valor'];
	}
	if ($data['saldo pendiente']<=100){
		$data['descuento'] ='0.00';
		$data['valor a pagar'] =$data['saldo pendiente'];
		$data['saldo total'] ='0.00';
	}
	$script = $db->query('SELECT script FROM "campanas"."scripts" WHERE id_proceso='.$cuenta['id_proceso'].' AND status=\'1\'')->current('script')['script'];
	if(preg_match_all('#\[\[%(.*?)%\]\]#',$script,$matches)) {
		foreach($matches[1] as $match) {
			$script = str_replace('[[%'.$match.'%]]',$data[$match],$script);
		}
	}
	echo $script;
}catch(Exception $e) {
	throw new Exception($e->getMessage());
}