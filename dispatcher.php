<?php
require dirname(__FILE__).'/config.php';
try {
	if (!isset($_GET['user_name'])) throw new Exception('No existe usuario logueado');
    if (trim($_GET['user_name'])=='') throw new Exception('_GET["user_name"] no puede ser vacio');
    $user = Modelo_Usuarios::getByUser($_GET['user_name']);
    if (empty($user)) throw new Exception('_GET["user_name"] No existe el usuario en el sistema Orion.');

	// si no especifican el id de registro
	if($_GET['id_cuenta']=='') {
		$smarty->display('main_dispatcher_buscar.tpl');
		die();
	}else{
		if($_POST['__save']!=='1') {
            $db = DB::getInstance();
            $campana = getCampana((getProceso(getCuenta($_REQUEST['id_cuenta'])['id_proceso']))['id_campana']);
            $cuentas_adicionales=array();
            foreach($campana['hooks']['dispatcher_pre_get_gestion_panel_data'] as $h) {
                if($h['enabled']=='1')
                    eval($h['code']);
            }
            
			$data=getGestionPanelData($_REQUEST['id_cuenta'],$cuentas_adicionales);
			if(!_SHOW_ACCOUNTS_ON_ALL_UDNS) {
				$target_udn=$data[$_GET['id_cuenta']]['udn']['id_udn'];
				$aux=array();
				foreach($data as $k=>$v) {
					if($v['udn']['id_udn']==$target_udn) $aux[$k]=$v;
				}
				$data=$aux;
			}
			$_REQUEST['tel_number']=preg_replace('#[^\d]#','',$_REQUEST['tel_number']);
			$_GET['tel_number']=preg_replace('#[^\d]#','',$_GET['tel_number']);
            
            // hooks
            foreach ($data['cuentas'][$_REQUEST['id_cuenta']]['campana']['hooks']['GestionPanel-Cuentas-PreDisplay'] as $hook) {
                $uid = 'f'.uniqid();
                $code='
                $__foo = function(&$data) {
                '.$hook['code'].'
                };
                
                $__foo($data);
                ';
                eval($code);
            }
            $hooks=array();
            foreach($data['cuentas'][$_REQUEST['id_cuenta']]['campana']['hooks'] as $k=>$v) {
                if(!preg_match('#^smarty_#',$k))
                    continue;
                $code='$hooks[\''.$k.'\']=function($params, &$template) {
                    $vars=array();
                    foreach($template->tpl_vars as $k=>$v) {
                        $vars[$k]=$v->value;
                    }
                    extract($vars);
                    unset($vars);
                    unset($k);
                    unset($v);
                ';


                $hasCode = false;
                foreach($v as $vv) {
                    if($vv['enabled']!='1')
                        continue;
                    $hasCode = true;
                    $code.=$vv['code']."\r\n\r\n";
                }
                $code.='
                };';
                
                if($hasCode) {
                    eval($code);
                }
            }
			
			foreach($data['cuentas'] as &$c) {
				// fjjf - ldkfjgh48hf8
				// 28/09/2021
				// se esta asumiendo que el valor pagado es la diferencia entre valor_original y valor_actual
				// sin embargo, no se está tomando en cuenta que el valor_actual es calculado tambien con las correcciones
				// por este motivo se cambia la forma de calcular valor pagado utilizando solo las actualizaciones que sean PAGO
				// codigo original
				//$c['cuenta']['valor_pagado']=$c['cuenta']['valor_original']-$c['cuenta']['valor_actual'];
				
				// codigo nuevo
				$c['cuenta']['valor_pagado']=0.0;
				foreach($c['actualizaciones'] as $act) {
					if($act['tipo_actualizacion']=='PAGO' /*|| $act['tipo_actualizacion']=='CONVENIO' */) {
						$c['cuenta']['valor_pagado']+=abs($act['diferencia']);
					}
				}
				// fin - ldkfjgh48hf8
				
				if($c['cuenta']['valor_pagado']<0) $c['cuenta']['valor_pagado']=0;
				
				unset($c);
			}
            //get provincias
            $q = 'SELECT * FROM medios_contacto.ubicacion WHERE id_ubicacion_padre=1 AND id_tipo_ubicacion=2 AND status=\'1\'';
            $q0 = $db->query($q);
            $provincias = array();
            while($qa0 = $db->fetchOne($q0)){
                $provincias[$qa0['id_ubicacion']] = $qa0['descripcion'];
            }
            // print_arr($hooks);
            // die();
            $smarty->assign('hooks',$hooks);
            $smarty->assign('ip',$_SERVER['REMOTE_ADDR']);
			$smarty->assign('data',$data);
			$smarty->assign('id_cuenta_seleccionada',$_REQUEST['id_cuenta']);
			$smarty->assign('internal_fecha_inicio',date('Y-m-d H:i:s'));
			$smarty->assign('internal_user_name',$_REQUEST['user_name']);
			$smarty->assign('internal_telh_id',$_REQUEST['telh_id']);
			$smarty->assign('internal_servidor',$_REQUEST['servidor']);
            $smarty->assign('catalogo_ubicacion',$provincias);

			if($_SERVER['REMOTE_ADDR']=='10.225.14.1') {
                // print_arr($data);
				// die();
			}

			
			$smarty->display('main_gestionar/main.tpl');
		}
	}
}catch(Exception $unhandled) {
	$smarty->assign('error',$unhandled->getMessage());
	$smarty->display('main_fatal.tpl');
	die();
}
