<?php

	$db = DB::getInstance();
	$ret = array();
	$q = 'SELECT 
		DISTINCT(campo) AS campo
	FROM cargas.carga_no_mapeada cnm
	WHERE 
		cnm.id_carga=(SELECT max(id_carga) FROM cargas.carga c WHERE c.id_proceso='.$_POST['id_proceso'].' AND tipo_carga=\'cartera\') 
	ORDER BY 1';
	foreach ($db->query($q)->fetchAll() as $campos) {
		$ret[]=$campos['campo'];
	}
	echo json_encode($ret);
	die;
