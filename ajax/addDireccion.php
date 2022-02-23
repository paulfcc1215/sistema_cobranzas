<?php
try {
	$cuenta=getCuenta($_request['cuenta']);
	if(!$cuenta) throw new Exception('Cuenta no existe');
	$persona=getPersona($cuenta['id_deudor']);
	if(!$persona) throw new Exception('Persona no existe');
	
	$q0 = $db->query('SELECT medios_contacto.new_direccion(
        '.$_request['id_parroquia'].',
        \''.$_request['tipo_direccion'].'\',
        '.$persona['id_persona'].',
        \''.$_request['calle_principal'].'\',
        \''.$_request['calle_secundaria'].'\',
        \''.$_request['numeracion'].'\',
        \''.$_request['latitud'].'\',
        \''.$_request['longitud'].'\',
        \''.$_request['referencia'].'\'
        ) AS id_direccion'
    );

	echo '1';
}catch(Exception $e) {
	throw new Exception($e->getMessage());
}