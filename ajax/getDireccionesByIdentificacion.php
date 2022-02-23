<?php
/*
revibe identificacion o id_cuenta
*/
try {
	$identificacion = $_request['identificacion'];
	if ($identificacion==''){
		$q = 'SELECT identificacion FROM personas.persona WHERE id_persona = (select id_deudor FROM cuentas.cuenta WHERE id_cuenta=\''.$_request['id_cuenta'].'\')';
		$q0 = $db->query($q);
		$qa0 = $db->fetchOne($q0);
		$identificacion = $qa0['identificacion'];
	}
	$q = 'SELECT 
		d.id_direccion,
		p.identificacion, 
		p.primer_nombre,
		d.fecha_insercion,d.tipo_direccion,tu.descripcion,dd.valor
	FROM medios_contacto.direcciones d
		JOIN medios_contacto.direcciones_data dd ON(d.id_direccion=dd.id_direccion)
		JOIN medios_contacto.tipo_ubicacion tu ON(tu.id_tipo_ubicacion=dd.id_tipo_ubicacion)
		JOIN personas.persona p ON(p.id_persona=d.id_persona)
	WHERE p.identificacion=\''.$identificacion.'\'';
	$q0 = $db->query($q);
	$direcciones = array();
	while ($qa0 = $db->fetchOne($q0)){
		$direcciones['identificacion'] = $qa0['identificacion'];
		$direcciones['nombre_cliente'] = $qa0['primer_nombre'];
		$direcciones['_direcciones'][$qa0['tipo_direccion']][$qa0['id_direccion']]['fecha_insercion'] = $qa0['fecha_insercion'];
		$direcciones['_direcciones'][$qa0['tipo_direccion']][$qa0['id_direccion']][$qa0['descripcion']] = $qa0['valor'];
	}
	$html.='<br><label>Identificación: '.$direcciones['identificacion'].'</label>';
	$html.='<br><label>Nombre Cliente: '.$direcciones['nombre_cliente'].'</label><br><br>';
	$html.='<table class="simple_table2" style="border:2px solid green;">';
	foreach ($direcciones['_direcciones'] as $tipo_direccion => $direccion) {
		$html.='<tr><td style="text-align: center;" colspan="'.count($direcciones['_direcciones']).'"><b>'.$tipo_direccion.'</b></td></tr>';
		$count_dir=1;
		foreach ($direccion as $direccion_data) {
			$html.='<tr><td><b>DIRECCION '.$count_dir.'</b></td></tr>';
			$count_dir++;
			foreach ($direccion_data as $campo => $valor) {
				$html.='<tr><td>'.$campo.'</td><td>'.$valor.'</td></tr>';
			}
			
		}
	}
	$html.='</table>';
	echo $html;

}catch(Exception $e) {
	throw new Exception($e->getMessage());
}