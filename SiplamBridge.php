<?php

	require ('config.php');
	$ret = array(
		'status'=>'success',
		'data'=>null,
		'message'=>null
	);
	$db=DB::getInstance();
	switch ($_REQUEST['accion']) {
		case 'get_subidas':
			$result = array();
			try {
				foreach($db->query('
				SELECT
				'.get_query_fields('proceso','p','p_','campanas',true).',
				'.get_query_fields('campana','camp','camp_','campanas',true).',
				'.get_query_fields('udn','u','u_','estructura',true).'
				FROM
				campanas.proceso p
				JOIN campanas.campana camp USING (id_campana)
				JOIN estructura.udn u USING (id_udn)
				ORDER BY id_proceso ASC') as $carga) {

					$aux=array();
					$aux['descripcion']=$carga['p_descripcion'];
					$aux['fecha_creacion']=$carga['p_fecha_carga'];
					$aux['subidas']=array(
						$carga['p_id_proceso']=>array(
							'descripcion_subida'=>$carga['u_udn'].' - '.$carga['camp_campana'].' - '.$carga['p_descripcion'],
							'fecha_subida'=>$carga['p_fecha_apertura'],
						)
					);
					$result[]=$aux;
					
				}
				$ret['data']=$result;
			} catch (Exception $e) {
				$ret['status']='error';
				$ret['data']=null;
				$ret['message']=$e->getMessage();
			}
		break;

		case 'get_registro':
			try {
				if(!json_decode($_REQUEST['base'],true)) {
					throw new Exception('JSON invalido para BASE');
				}
				
				$base=json_decode($_REQUEST['base'],true);
				if(!is_array($base))
					throw new Exception('JSON invalido para BASE');
				$base=implode(',',$base);
				$query='SELECT * FROM cuentas.cuenta c JOIN personas.persona p ON (p.id_persona=c.id_deudor) WHERE identificacion=\''.$db->escape($_REQUEST['identificacion']).'\' AND c.id_proceso IN ('.$base.')';
				$cuentas=$db->query($query);
				if($cuentas->numRows()==0)
					throw new Exception('No existe la identificación solicitada para los procesos ('.$base.')');
				
					
				$ret['data']=$cuentas->current()['id_cuenta'];
				
			} catch (Exception $e) {
				$ret['status']='error';
				$ret['data']=null;
				$ret['message']=$e->getMessage();
			}
		break;

		case 'get_gestion':
			try {
				if(!json_decode($_REQUEST['base'],true)) {
					throw new Exception('JSON invalido para BASE');
				}
				
				$base=json_decode($_REQUEST['base'],true);
				if(!is_array($base))
					throw new Exception('JSON invalido para BASE');
				$base=implode(',',$base);
				$query='
					SELECT g.id_gestion,g.telh_id,g.tel_number,g.fecha_inicio,g.user_name,p.identificacion,t.descripcion as tipificacion_crm,tm.siplam_regestionable as regestionable,tm.tipificacion_siplam
					FROM cuentas.cuenta c 
						JOIN personas.persona p ON (p.id_persona=c.id_deudor) 
						JOIN gestiones.gestion g ON (g.id_cuenta=c.id_cuenta)
						JOIN gestiones.tipificacion t USING(id_tipificacion)
						JOIN gestiones.tipificacion_metadata tm USING(id_tipificacion)
					WHERE identificacion=\''.$db->escape($_REQUEST['identificacion']).'\' AND c.id_proceso IN ('.$base.')';
				$cuentas=$db->query($query);
				if($cuentas->numRows()==0){
					//throw new Exception('No existe la identificacion solicitada para los procesos ('.$base.')');
				}
				$ret['data']=$cuentas->fetchAll();
			} catch (Exception $e) {
				$ret['status']='error';
				$ret['data']=null;
				$ret['message']=$e->getMessage();
			}
		break;
		
		default:
			$ret['status']='error';
			$ret['data']=null;
			$ret['message']='accion no definida';
		break;
	}
	echo json_encode($ret);