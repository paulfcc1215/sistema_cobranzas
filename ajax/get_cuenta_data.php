<?php
$ret=get_cuenta_data($_request['id_cuenta']);
if($ret===false)
	throw new Exception('Cuenta "'.$_request['id_cuenta'].'" no existe');
echo json_encode($ret);