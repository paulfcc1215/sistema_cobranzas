<?php
class Gestion_Creditos_JEPv2 implements Reporte_Interface {
	private static $preparedDireccion = false;
	/**
	* fjjf - 30/09/2021
	* Reporte solicitado en ticket R-008273
	* Solo se cambian el orden de las columnas
	*/

	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	function getDireccionByIdentificacion($cedula){
		$db = DB::getInstance();
		if(!self::$preparedDireccion) {
			$q = 'SELECT 
				(p.nombre || \'-\' ||
				c.nombre || \'-\' ||
				pa.nombre || \'-\' ||
				d.calle_principal) as direccion
			FROM tmp.direcciones_jep d
			JOIN tmp.provincias p ON(p.cprovincia=d.provincia)
			JOIN tmp.ciudades c ON(c.cciudad=d.ciudad AND c.cprovincia=d.provincia)
			JOIN tmp.parroquias pa ON(pa.cparroquia=d.parroquia AND pa.cciudad=d.ciudad AND pa.cprovincia=d.provincia)
			WHERE d.numero_identificacion=$1 LIMIT 1';
			$db->prepare('getDireccionByIdentificacion',$q);
			self::$preparedDireccion = true;
		}
		
		
		$q0 = $db->execute('getDireccionByIdentificacion',array($cedula));
		if ($db->numRows($q0)==0) return '';
		$direccion = $db->fetchOne($q0);
		$direccion = $direccion['direccion'];
		return $direccion;
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		set_time_limit(0);
		foreach (getProcesoByCampana(15) as $p) {
			// if ($p['status']!='1') continue;
			$procesos[$p['id_proceso']] = $p['id_proceso'] .' - '. $p['descripcion'];
		}

		switch($_get['step']) {
			case '2':

				if (empty($_post['id_proceso'])) throw new exception ('Seleccione proceso');
				if ($_post['fecha_desde']=='') throw new exception ('Seleccione Fecha inicio');
				if ($_post['fecha_hasta']=='') throw new exception ('Seleccione Fecha Fin');
				
				$info_adicional = array('_TIPIFICACION_RECAPT','_ID_GESTION');

				// CATALOGO DE RESPUESTA
				$cat_resp = array('NO CONTESTA/NO SATISFACTORIA','CONTACTADO');
				$db = DB::getInstance();
				$reporte = 'gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				
				$cabecera = array(
					'casa_cobranza',
					'cgestion',
					'contacto',
					'direccion',
					'duracion_llamada',
					'fecha_gestion',
					'fecha_promesa',
					'fecha_proxima_gestion',
					'identificacion_deudor',
					'motivo_nopago',
					'numero_operacion',
					'observacion',
					'respuesta',
					'saldo',
					'telefono',
					'usuario',
					'valor_promesa',
				);
				if ($_post['info_adicional'])
					$cabecera = array_merge($cabecera,$info_adicional);
				
				$result[$reporte][]= $cabecera;
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
					--AND c.cuenta=\'066200358246\'
					ORDER BY
					t.peso DESC,g.fecha_inicio DESC';

				foreach($db->query($q) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}
				$q = 'SELECT gc.field,
					(CASE WHEN gc.field=\'id_jep_tipificacion\' THEN
						(SELECT id_jep_tipificacion FROM custom.jep_tipificaciones WHERE id_jep_tipificaciones= gc."value"::INTEGER)
					ELSE
						"value"
					END) as value
				FROM gestiones.gestion_custom_fields gc
				WHERE gc.id_gestion=$1';

				$db->prepare('get_custom_data_gestion',$q);
				$t_cache=array();
				foreach ($gestiones_x_cuenta as $id_cuenta => $gestiones) {
					foreach ($gestiones as $gestion){

						$datos_nm = getCargaNoMapeada($gestion['g_id_cuenta']);
						$datos_nm = $datos_nm[$gestion['c_id_carga']];
						$tipificacion = getTipificacion($gestion['g_id_tipificacion']);
						$datos_gestion_custom = array();
						foreach ($db->execute('get_custom_data_gestion',array($gestion['g_id_gestion'])) as $row) {
							$datos_gestion_custom[$row['field']] = $row['value'];
						};
						$dias_proxima_gestion=2;
						if ($tipificacion['_metadata']['es_promesa']){
							$dias_proxima_gestion=30;
						}
						
						$fecha_gestion=date('Y-m-d',strtotime($gestion['g_fecha_inicio']));
						$proxima_gestion = new DateTime($fecha_gestion);
						$proxima_gestion->add(new DateInterval('P'.$dias_proxima_gestion.'D'));
						$telefono = $gestion['g_tel_number'];
                                                $telefono = preg_replace('#[^\d]#','',$telefono);
                                                if(strlen($telefono)<9 || strlen($telefono)>14) {
                                                    $telefono = 'INVALIDO - '.$telefono;
                                                }
						$line = array(
							// 'casa_cobranza',
							'RECAPT',
							// 'Cgestion',
							($datos_gestion_custom['id_jep_tipificacion']==''?'81':$datos_gestion_custom['id_jep_tipificacion']),
							// 'contacto',
							($tipificacion['_metadata']['es_contacto_primera_persona'])?'DIRECTO':'INDIRECTO',
							// direccion
							str_replace("\t",' ',$this->getDireccionByIdentificacion($gestion['p_identificacion'])),
							// 'duracion_llamada',
							Helpers::seconds_to_time(strtotime($gestion['g_fecha_fin'])-strtotime($gestion['g_fecha_inicio'])),
							// 'fecha_gestion',
							date('Y-m-d H:i:s',strtotime($gestion['g_fecha_inicio'])),
							// 'fecha_promesa',
							$gestion['g_fecha_compromiso'],
							// 'fecha_proxima_gestion',
							$proxima_gestion->format('Y-m-d'),
							// 'identificacion_deudor',
							str_pad($gestion['p_identificacion'],10,'0',STR_PAD_LEFT),
							// 'motivo_nopago',
							($tipificacion['_metadata']['es_negativa'])?$gestion['g_observacion']:'',
							// 'numero_operacion',
							$gestion['c_cuenta'],
							// 'observacion',
							$gestion['g_observacion'],
							// 'respuesta',
							($tipificacion['_metadata']['es_contacto_primera_persona'] || $tipificacion['_metadata']['es_contacto_tercero'])?'1':'0',
							// 'saldo'
							$gestion['c_valor_actual'],
							// 'telefono',
							$telefono,
							// 'usuario',
							$gestion['g_user_name'],
							// 'valor_promesa',
							$gestion['g_monto_compromiso'],
						);
						foreach($line as &$l) {
							$l=str_replace(',',' ',$l);
							$l=str_replace("\r",'',$l);
							$l=str_replace("\n",' ',$l);
							unset($l);
						}

						if ($_post['info_adicional']){
							// tipificacion recapt;
							$line[] = $gestion['t_descripcion'];
							// id_gestion
							$line[] = $gestion['g_id_gestion'];
						}

						foreach($line as &$l) {
							$l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
							unset($l);
						}

						$output[]=$line;
					}
				}

				foreach($output as &$o) {
					$o = implode(',',$o);
					unset($o);
				}
				header('Content-Disposition: Attachment; filename="result.txt"');
				header('Content-Type: application/octect-stream');
				echo implode("\r\n",$output);
				die();

				return 'file';
			break;

			default:

				$_T['maintitle']='JEP - Reporte de gestión de creditos - Version 2 (R-008273)';
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
					<label>
						<input type="checkbox" name="info_adicional" />Información adicional
					</label>
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
