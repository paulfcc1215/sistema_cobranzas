<?php
try {
	
	//get cantones
	$q = 'SELECT * FROM medios_contacto.ubicacion WHERE id_ubicacion_padre='.$_request['id_provincia'].' AND id_tipo_ubicacion=3 AND status=\'1\'';
	$q0 = $db->query($q);
	$html = '';
	while ($qa0 = $db->fetchOne($q0)){
		$html .= '<option value="'.$qa0['id_ubicacion'].'">'.$qa0['descripcion'].'</option>';
	}
	echo $html;

}catch(Exception $e) {
	throw new Exception($e->getMessage());
}