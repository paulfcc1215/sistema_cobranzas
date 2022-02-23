<?php

	try {
		$cuenta=getCuenta($_request['id_cuenta']);
		if(!$cuenta) throw new Exception('Cuenta no existe');
		$html='<table border="1"><tr><th>CUENTA</th><th>DIRECCION</th><th>FECHA CARGA</th><th>CAMPAÑA</th></tr>';
		
		$q='
			SELECT cu.cuenta,cnm.valor,c.fecha_carga,c.descripcion,ca.campana
			FROM cargas.carga_no_mapeada cnm 
				JOIN cargas.carga c USING(id_carga)
				JOIN cuentas.cuenta cu USING(id_cuenta)
				JOIN campanas.proceso p on (cu.id_proceso=p.id_proceso)
				JOIN campanas.campana ca USING(id_campana)
			WHERE 
				cnm.campo=\'direccion\' AND 
				cnm.valor<>\'\' 
				AND cnm.id_cuenta in (
					SELECT id_cuenta
					FROM cuentas.cuenta c 
						JOIN campanas.proceso p USING(id_proceso)
						JOIN campanas.campana ca USING(id_campana)
					WHERE 
						c.cuenta=(SELECT cuenta FROM cuentas.cuenta WHERE id_cuenta = '.$_request['id_cuenta'].') AND 
						p.id_campana=(SELECT p.id_campana FROM cuentas.cuenta c JOIN campanas.proceso p USING(id_proceso) WHERE c.id_cuenta = '.$_request['id_cuenta'].')
				)
			ORDER BY fecha_carga DESC
		';
		foreach ($db->query($q) as $value) {
			$html.='<tr><td>'.$value['cuenta'].'</td><td>'.$value['valor'].'</td><td>'.$value['fecha_carga'].'</td><td>'.$value['campana'].'</td></tr>';
		}
		$html.='</table>';

		echo $html;
	}catch(Exception $e) {
		throw new Exception($e->getMessage());
	}