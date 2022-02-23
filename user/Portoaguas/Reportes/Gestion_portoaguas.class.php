<?php
class Gestion_portoaguas implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		
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
				$reporte = 'gestion_'.date('dmY').'.txt';
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
					'origen_telefono',
					'fecha_compromiso',
					'IMPORTE PROMESA',
					'observaciones',
					'campana',
					'agente',
					'peso',
					'id_llamada',
					// 'numero_pagos',
					// 'suma_pagos',
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
					--c.id_cuenta=2160529 AND
					pr.id_proceso IN ('.implode(',',$_post['id_proceso']).') AND
					date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
				ORDER BY
					t.peso DESC,g.fecha_inicio DESC';

				
				foreach($db->query($q) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}

				$tip_excluir = array(325);
				$db->prepare('q2','SELECT campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 AND id_carga=$2');
				foreach ($gestiones_x_cuenta as $id_cuenta => $gestiones) {
					$aux=$gestiones;
					foreach ($gestiones as $gestion){

						if (in_array($gestion['t_id_tipificacion'],$tip_excluir)) continue;
						// $datos_nm = getCargaNoMapeada($gestion['g_id_cuenta']);
						// $datos_nm = $datos_nm[$gestion['c_id_carga']];
						$q1 = $db->execute('q2',array($gestion['g_id_cuenta'],$gestion['c_id_carga']));
						while ($row = $db->fetchOne($q1)){
							$datos_nm[$row['campo']]=$row['valor'];
						}
						$tipificacion = getTipificacion($gestion['g_id_tipificacion']);
						$num_pagos=0;
						$importe_pagos=0.00;
						foreach ($aux as $g){
							if ($g['g_monto_compromiso']!=''){
								$num_pagos++;
								$importe_pagos+=$g['g_monto_compromiso'];
							}
						}

						//get telefono origen
						$telefono = $gestion['g_tel_number'];
						if (substr($gestion['g_tel_number'],0,3)=='593'){
							$telefono = '0'.substr($gestion['g_tel_number'],3);
						}
						$q = 'SELECT origen FROM medios_contacto.telefono WHERE id_persona='.$gestion['p_id_persona'].' AND telefono=\''.$gestion['g_tel_number'].'\'';
						$q0 = $db->query($q);
						$origen = 'GESTION';
						while($qa0 = $db->fetchOne($q0)){
							if ($qa0['origen']=='BASE'){
								$origen = 'BASE';
								break;
							}
						}
						// intenta obtener telh_id desde dragon
						if ($gestion['g_telh_id']==''){
							$data_dragon = getDataDragonByTelNumber('10.1.210.103',$telefono);
							if (!empty($data_dragon)){
								$gestion['g_telh_id'] = $data_dragon['telh_id'];
								$db->query('UPDATE gestiones.gestion SET telh_id=\''.$data_dragon['telh_id'].'\' WHERE id_gestion='.$gestion['g_id_gestion']);
							}
						}

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
							$telefono,
							// 'origen_telefono',
							$origen,
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
							// $num_pagos,
							// 'suma_pagos'
							// $importe_pagos
						);

						foreach($line as &$l) {
							$l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
							unset($l);
						}
						$output[]=$line;
					}
				}

				return 'file';
			break;

			default:

				$_T['maintitle']='PortoAguas - Gestión De Cobranza';
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