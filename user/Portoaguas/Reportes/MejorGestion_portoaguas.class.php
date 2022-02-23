<?php
class MejorGestion_portoaguas implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	function getPagos($id_cuenta,$id_proceso){
		$db = DB::getInstance();
		$q = 'SELECT ca.* 
		FROM cuentas.cuenta_actualizacion ca
			JOIN cuentas.cuenta c ON(c.id_cuenta=ca.id_cuenta)
		WHERE ca.id_cuenta='.$id_cuenta.' AND c.id_proceso='.$id_proceso.' AND ca.tipo_actualizacion=\'PAGO\'';
		$q0 = $db->query($q);
		$result['num_pagos'] = 0;
		$result['pagos'] = 0.00;
		while ($qa0 = $db->fetchOne($q0)){
			$result['num_pagos']++;
			$result['pagos'] += abs($qa0['diferencia']);
		}
		return $result;
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {

		// if($_SERVER['REMOTE_ADDR']!='10.0.210.85'){
		// 	die('Reporte en construcción');
		// }
		
		foreach (getProcesoByCampana(17) as $p) {
			// if ($p['status']!='1') continue;
			$procesos[$p['id_proceso']] = $p['id_proceso'] .' - '. $p['descripcion'];
		}

		switch($_get['step']) {
			case '2':

				if (empty($_post['id_proceso'])) throw new exception ('Seleccione proceso');
				if ($_post['fecha_desde']=='') throw new exception ('Seleccione Fecha inicio');
				if ($_post['fecha_hasta']=='') throw new exception ('Seleccione Fecha Fin');
				
				$db = DB::getInstance();
				$reporte = 'gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$result[$reporte][]=array(
					'IDENTIFICACION',
					'CLIENTE',
					'CUENTA',
					'CATASTRO',
					'TIPO_CONSUMO',
					'SERVICIO',
					'ESTADO',
					'RECLAMO',
					'NUM_MEDIDOR',
					'FACTURAS_VENCIDAS',
					'OBLIGACIONES_CORRIENTES',
					'OBLIGACIONES_VENCIDAS',
					'DEUDA_PORTOAGUAS',
					'SALDO_CONVENIO',
					'vencimiento_factura_vencida',
					'VENCIMIENTO_FACTURA_corriente',
					'FECHA DE ASIGNACION',
					'FECHA ULTIMA DE  CARGA',
					'FECHA DE GESTION',
					'GESTION RECAPT',
					'GESTION PROVEEDOR',
					'CONTACTABILIDAD',
					'telefono_de_contacto',
					'fecha_compromiso',
					'IMPORTE PROMESA',
					'observaciones',
					'campana',
					'agente',
					'peso',
					'id_llamada',
					'numero_pagos',
					'suma_pagos'
				);
				$output=&$result[$reporte];
				$q='SELECT
				'.get_query_fields('gestion','g','g_','gestiones',true).',
				'.get_query_fields('cuenta','c','c_','cuentas',true).',
				'.get_query_fields('persona','p','p_','personas',true).',
				'.get_query_fields('proceso','pr','pr_','campanas',true).',
				'.get_query_fields('tipificacion','t','t_','gestiones',true).',
				'.get_query_fields('campana','camp','camp_','campanas',true).'
				FROM
					gestiones.gestion g
					JOIN cuentas.cuenta c USING (id_cuenta)
					JOIN campanas.proceso pr USING (id_proceso)
					JOIN campanas.campana camp USING (id_campana)
					JOIN personas.persona p ON (c.id_deudor=p.id_persona)
					JOIN gestiones.tipificacion t USING (id_tipificacion)
				WHERE
					pr.id_proceso IN ('.implode(',',$_post['id_proceso']).') AND
					date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
					ORDER BY
					t.peso DESC,g.fecha_inicio DESC';
				// print_arr($q);
				// die();

				foreach($db->query($q) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}
				$db->prepare('q2','SELECT campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 AND id_carga=$2');
				foreach ($gestiones_x_cuenta as $id_cuenta => $gestiones) {
					$gestion = $gestiones[0];
					// print_arr($gestion);
					// die();
					// if ($gestion['g_id_cuenta']!='1718678') continue;
					$pagos = $this->getPagos($gestion['g_id_cuenta'],$gestion['pr_id_proceso']);

					// get data no mapeada
					$q1 = $db->execute('q2',array($gestion['g_id_cuenta'],$gestion['c_id_carga']));
					while ($row = $db->fetchOne($q1)){
						$datos_nm[$row['campo']]=$row['valor'];
					}
					$tipificacion = getTipificacion($gestion['g_id_tipificacion']);
					$line = array(
						// 'IDENTIFICACION',
						$gestion['p_identificacion'],
						// 'CLIENTE',
						Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
						// 'CUENTA',
						$gestion['c_cuenta'],
						// 'CATASTRO',
						$datos_nm['CATASTRO'],
						// 'TIPO_CONSUMO',
						$datos_nm['TIPO_CONSUMO'],
						// 'SERVICIO',
						$datos_nm['SERVICIO'],
						// 'ESTADO',
						$datos_nm['ESTADO'],
						// 'RECLAMO',
						$datos_nm['RECLAMO'],
						// 'NUM_MEDIDOR',
						$datos_nm['NUM_MEDIDOR'],
						// 'FACTURAS_VENCIDAS',
						$datos_nm['FACTURAS_VENCIDAS'],
						// 'OBLIGACIONES_CORRIENTES',
						$datos_nm['OBLIGACIONES_CORRIENTES'],
						// 'OBLIGACIONES_VENCIDAS',
						$datos_nm['OBLIGACIONES_VENCIDAS'],
						// 'DEUDA_PORTOAGUAS',
						$datos_nm['DEUDA_PORTOAGUAS'],
						// 'SALDO_CONVENIO',
						$datos_nm['SALDO_CONVENIO'],
						// 'vencimiento_factura_vencida',
						$datos_nm['fecha de facturacion'],
						// 'VENCIMIENTO_FACTURA_corriente',
						$datos_nm['VENCIMIENTO_FACTURA'],
						// 'FECHA DE ASIGNACION',
						$gestion['c_fecha_creacion'],
						// 'FECHA ULTIMA DE  CARGA',
						$gestion['c_fecha_valor_actual'],
						// 'FECHA DE GESTION',
						$gestion['g_fecha_inicio'],
						// 'GESTION RECAPT',
						$tipificacion['descripcion'],
						// 'GESTION PROVEEDOR',
						'',
						// 'CONTACTABILIDAD',
						($tipificacion['_metadata']['contactabilidad']=='1'?'CONTACTADO':'NO CONTACTADO'),
						// 'telefono_de_contacto',
						$gestion['g_tel_number'],
						// 'fecha_compromiso',
						$gestion['g_fecha_compromiso'],
						// 'IMPORTE PROMESA',
						$gestion['g_monto_compromiso'],
						// 'observaciones',
						$gestion['g_observacion'],
						// 'campana',
						$gestion['camp_campana'],
						// 'agente',
						$gestion['g_user_name'],
						// 'peso',
						$tipificacion['peso'],
						// 'id_llamada',
						$gestion['g_telh_id'],
						// 'numero_pagos',
						$pagos['num_pagos'],
						// 'suma_pagos',
						$pagos['pagos']
					);

					foreach($line as &$l) {
						$l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
						unset($l);
					}
					$output[]=$line;

				}
				return 'file';
			break;

			default:

				$_T['maintitle']='PortoAguas - Mejor Gestión De Cobranza';
				$_T['maincontent']='
				<script>

				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Proceso:</b>
					<br>
					<select name="id_proceso[]" size="12" multiple>';
					foreach($procesos as $id_p => $p) {
						$_T['maincontent'].='<option value="'.$id_p.'">'.$p.'</option>';
					}
					$_T['maincontent'].='
					</select>
					<br><br>
					<b>Indique Rango de Fecha</b>
					<br>
					<table border="0" class="t">
						<tr>
							<td>
								Desde:<br><input type="text" name="fecha_desde" class="fecha" value="'.date('d/m/Y').'">
							</td>
						</tr>
						<tr>
							<td>
								Hasta:<br><input type="text" name="fecha_hasta" class="fecha" value="'.date('d/m/Y').'">
							</td>
						</tr>
					</table>
					<br><br>
					<button class="btn btn-primary">Siguiente</button>
				</form>';

				return 'flow';
			break;
		}


	}

	public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {

	}
}