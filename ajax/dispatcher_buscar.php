<?php
if(!in_array($_request['by'],array('cedula','cuenta','nombres','id_cuenta','telefono')))
	throw new Exception('Debe seleccionar un campo por el cual buscar (Cedula, Cuenta, Nombres, id_cuenta o Teléfono)');

switch($_request['by']) {
	case 'cedula':
		$query='SELECT * FROM public.get_cuentas_by_cedula(\''.$db->escape($_request['q']).'\')';
	break;
	
	case 'cuenta':
		$query='SELECT * FROM public.get_cuentas_by_cuenta(\''.$db->escape($_request['q']).'\')';
	break;
	
	case 'id_cuenta':
		$query='SELECT * FROM public.get_cuentas_by_id_cuenta('.$db->escape($_request['q']).')';
	break;
	
	case 'telefono':
		$query='SELECT * FROM public.get_cuentas_by_telefono(\''.$db->escape($_request['q']).'\')';
	break;
	
	default:
		throw new Exception('No implementado');
	break;
}

$procesos=array();
foreach($db->query($query) as $q) {
	if(!array_key_exists($q['id_proceso'],$procesos)) {
		$procesos[$q['id_proceso']]=$db->query('SELECT * FROM campanas.proceso WHERE id_proceso='.$q['id_proceso'])->current();
	}
    if(!_SHOW_DISABLED_PROCESSES_IN_SEARCH) {
        if($procesos[$q['id_proceso']]['status']!='1')
            continue;
    }
	$records[$q['udn']][]=$q;
}
//print_arr($records);
//die();
$smarty->assign('records',$records);
$smarty->assign('query',$_request['q']);
$smarty->assign('user_name',$_request['user_name']);
$smarty->display('ajax_dispatcher_buscar.tpl');