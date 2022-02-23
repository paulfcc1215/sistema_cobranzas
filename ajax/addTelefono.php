<?php
try {
	if (!Helpers::telefonoValido($_request['telefono'],$tipo)) throw new Exception('El teléfono indicado no es válido');
	$cuenta = getCuenta($_request['cuenta']);
	if (!$cuenta) throw new Exception('Cuenta no existe');
	$persona = getPersona($cuenta['id_deudor']);
	if (!$persona) throw new Exception('Persona no existe');
	foreach ($persona['telefonos'] as $t) {
		if($t['telefono']==$_request['telefono']) throw new Exception('Teléfono "'.$_request['telefono'].'" ya existe para este registro');
	}

	$db->query('INSERT INTO medios_contacto.telefono (
		id_persona,
		tipo_telefono,
		telefono,
		fecha_agregado,
		origen,
		pertenece_a,
		ubicacion_telefono
	)VALUES(
		'.$persona['id_persona'].',
		\''.$tipo.'\',
		\''.$db->escape($_request['telefono']).'\',
		NOW(),
		\'GESTION\',
		\''.$db->escape($_request['pertenece_a']).'\',
		\''.$db->escape($_request['ubicacion_telefono']).'\'
	)');
	echo '1';
}catch(Exception $e) {
	throw new Exception($e->getMessage());
}