<?php

	try {
		$cuenta=getCuenta($_request['id_cuenta']);
		if(!$cuenta) throw new Exception('Cuenta no existe');
		$html='<table border="1"><tr><th>FECHA GESTION</th><th>TIPIFICACION</th><th>TELEFONO</th><th>USUARIO</th><th>OBSERVACION</th></tr>';
		$q='
			SELECT 
				g.id_cuenta,g.id_gestion,g.fecha_inicio,t.descripcion,g.tel_number,g.user_name,g.observacion
			FROM gestiones.gestion g 
				JOIN gestiones.tipificacion t USING(id_tipificacion)
			WHERE g.id_cuenta in (
				SELECT id_cuenta
				FROM cuentas.cuenta c 
					JOIN campanas.proceso p USING(id_proceso)
					JOIN campanas.campana ca USING(id_campana)
				WHERE 
					c.cuenta=(SELECT cuenta FROM cuentas.cuenta WHERE id_cuenta = '.$_request['id_cuenta'].') AND 
					p.id_campana=(SELECT p.id_campana FROM cuentas.cuenta c JOIN campanas.proceso p USING(id_proceso) WHERE c.id_cuenta = '.$_request['id_cuenta'].')
			)
			ORDER BY g.fecha_inicio DESC
		';
		foreach ($db->query($q) as $value) {
			$html.='<tr><td>'.$value['fecha_inicio'].'</td><td>'.$value['descripcion'].'</td><td>'.$value['tel_number'].'</td><td>'.$value['user_name'].'</td><td>'.$value['observacion'].'</td></tr>';
		}
		$html.='</table>';

		echo $html;
	}catch(Exception $e) {
		throw new Exception($e->getMessage());
	}