<?php

	class Gestion_GAD_Portoviejo implements Reporte_Interface {
		private $cache=array();
		private $contactabilidad_enviado = array(317,319,316,315);

		public function getCamposRequeridos() {
			return array();
		}

		public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
			
			foreach (getProcesoByCampana(19) as $p) {
				// if ($p['status']!='1') continue;
				$procesos[$p['id_proceso']] = $p['id_proceso'] .' - '. $p['descripcion'];
			}

			switch($_get['step']) {
				case '2':

					if (empty($_post['id_proceso'])) throw new exception ('Seleccione proceso');
					if ($_post['fecha_desde']=='') throw new exception ('Seleccione Fecha inicio');
					if ($_post['fecha_hasta']=='') throw new exception ('Seleccione Fecha Fin');
					
					$db = DB::getInstance();
					$reporte = 'gestion_GAD_PORTOVIEJO_'.date('dmY').'.txt';
					$result[$reporte][]=array(
						'CEDULARUC',
						'NOMBRE',
						'id',
						'principal',
						'tipo',
						'clave',
						'MesObligacion',
						'añoemision',
						'AñoObligacion',
						'total',
						'FECHA DE ASIGNACION',
						'FECHA ULTIMA DE  CARGA',
						'FECHA DE GESTION',
						'GESTION RECAPT',
						'GESTION PROVEEDOR',
						'CONTACTABILIDAD',
						'telefono_de_contacto',
						'origen_telefono',
						'fecha_compromiso',
						'IMPORTEPROMESA',
						'observaciones',
						'campana',
						'agente',
						'peso',
						'id_llamada',
						'HORA',
						'MINUTOS',
						'DIA',
						'MES',
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
					foreach($db->query($q) as $aux) {
						$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
					}
					
					$db->prepare('q2','SELECT campo,valor FROM cargas.carga_no_mapeada WHERE id_cuenta=$1 AND id_carga=$2');
					foreach ($gestiones_x_cuenta as $id_cuenta => $gestiones) {
						
						foreach ($gestiones as $gestion){

							$q1 = $db->execute('q2',array($gestion['g_id_cuenta'],$gestion['c_id_carga']));
							while ($row = $db->fetchOne($q1)){
								$datos_nm[$row['campo']]=$row['valor'];
							}
							$tipificacion = getTipificacion($gestion['g_id_tipificacion']);
							$contactabilidad = 'NO CONTACTADO';
							if ($tipificacion['_metadata']['contactabilidad']){
								$contactabilidad = 'CONTACTADO';
							}
							if (in_array($tipificacion['id_tipificacion'],$this->contactabilidad_enviado)){
								$contactabilidad = 'ENVIADO';
							}

							//get telefono origen
							$q = 'SELECT origen FROM medios_contacto.telefono WHERE id_persona='.$gestion['p_id_persona'].' AND telefono=\''.$gestion['g_tel_number'].'\'';
							$q0 = $db->query($q);
							$origen = $db->fetchOne($q0)['origen'];
							
							$fecha_gestion = new datetime($gestion['g_fecha_inicio']);

							$line = array(

								// 'CEDULARUC',
								$gestion['p_identificacion'],
								// 'NOMBRE',
								Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
								// 'id',
								$gestion['c_cuenta'],
								// 'principal',
								'1',
								// 'tipo',
								$datos_nm['tipo'],
								// 'clave',
								$datos_nm['clave'],
								// 'MesObligacion',
								$datos_nm['MesObligacion'],
								// 'añoemision',
								$datos_nm['añoemision'],
								// 'AñoObligacion',
								$datos_nm['AñoObligacion'],
								// 'total',
								$gestion['c_valor_original'],
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
								$contactabilidad,
								// 'telefono_de_contacto',
								$gestion['g_tel_number'],
								// 'origen_telefono',
								$origen,
								// 'fecha_compromiso',
								$gestion['g_fecha_compromiso'],
								// 'IMPORTEPROMESA',
								$gestion['g_monto_compromiso'],
								// 'observaciones',
								str_replace(array("\t","\n")," ",$gestion['g_observacion']),
								// 'campana',
								$gestion['camp_campana'],
								// 'agente',
								$gestion['g_user_name'],
								// 'peso',
								$gestion['t_peso'],
								// 'id_llamada',
								$gestion['g_telh_id'],
								// 'HORA',
								$fecha_gestion->format('H'),
								// 'MINUTOS',
								$fecha_gestion->format('i'),
								// 'DIA',
								$fecha_gestion->format('d'),
								// 'MES',
								$fecha_gestion->format('m'),

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

					$_T['maintitle']='GAD Portoviejo - Gestión De Cobranza';
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