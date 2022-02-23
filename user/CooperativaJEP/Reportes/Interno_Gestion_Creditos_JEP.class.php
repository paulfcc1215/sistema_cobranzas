<?php
class Interno_Gestion_Creditos_JEP implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		
		foreach (getProcesoByCampana(15) as $p) {
			// if ($p['status']!='1') continue;
			$procesos[$p['id_proceso']] = $p['id_proceso'] .' - '. $p['descripcion'];
		}

		switch($_get['step']) {
			case '2':

				if (empty($_post['id_proceso'])) throw new exception ('Seleccione proceso');
				if ($_post['tipo_reporte']=='') throw new exception ('Seleccione el tipo de reporte');
				if ($_post['fecha_desde']=='') throw new exception ('Seleccione Fecha Inicio');
				if ($_post['fecha_hasta']=='') throw new exception ('Seleccione Fecha Fin');
				
				// CATALOGO DE RESPUESTA
				$cat_resp = array('NO CONTESTA/NO SATISFACTORIA','CONTACTADO');
				$db = DB::getInstance();
				$reporte = 'gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$cabecera = array(
					'ciclo',
					'identificacion',
					'codigo_cliente',
					'nombre',
					'deuda_vencida',
					'gestion',
					'codced',
					'estado',
					'proveedor_asignado',
					'fecha_gestion',
					'telefono',
					'observacion',
					'login',
					'fecha_compromiso',
					'valor_compromiso',
					'deuda',
					'campana',
					'periodo',
					'origen',
				);
				
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
					ORDER BY
					t.peso DESC,g.fecha_inicio DESC';

				foreach($db->query($q) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}
				

				foreach ($gestiones_x_cuenta as $id_cuenta => $gestiones) {
					
					foreach ($gestiones as $gestion){

						$datos_nm = getCargaNoMapeada($gestion['g_id_cuenta']);
						$datos_nm = $datos_nm[$gestion['c_id_carga']];
						
						$tipificacion = getTipificacion($gestion['g_id_tipificacion']);
						
						$estado = 'INCONTA';
						if ($tipificacion['_metadata']['es_contacto_primera_persona']=='1' || $tipificacion['_metadata']['es_contacto_tercero']=='1')
							$estado = 'CONTA';

						$q = 'SELECT origen FROM medios_contacto.telefono WHERE id_persona='.$gestion['p_id_persona'].' AND telefono=\''.$gestion['g_tel_number'].'\'';
						$q0 = $db->query($q);
						$origen = $db->fetchOne($q0)['origen'];

						$line = array(
							// 'ciclo',
							'',
							// 'identificacion',
							$gestion['p_identificacion'],
							// 'codigo_cliente',
							$gestion['c_cuenta'],
							// 'nombre',
							$gestion['p_primer_nombre'],
							// 'deuda_vencida',
							$gestion['c_valor_actual'],
							// 'gestion',
							$tipificacion['descripcion'],
							// 'codced',
							'',
							// 'estado',
							$estado,
							// 'proveedor_asignado',
							'RECAPT',
							// 'fecha_gestion',
							$gestion['g_fecha_inicio'],
							// 'telefono',
							$gestion['g_tel_number'],
							// 'observacion',
							$gestion['g_observacion'],
							// 'login',
							$gestion['g_user_name'],
							// 'fecha_compromiso',
							$gestion['g_fecha_compromiso'],
							// 'valor_compromiso',
							$gestion['g_monto_compromiso'],
							// 'deuda',
							$gestion['c_valor_original'],
							// 'campana',
							$gestion['camp_campana'],
							// 'periodo'
							$gestion['pr_descripcion'],
							// 'origen'
							$origen,
						);

						foreach($line as &$l) {
							$l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
							unset($l);
						}
						$output[]=$line;
						if ($_post['tipo_reporte'] == 'MG'){
							break;
						}
					}
				}
				return 'file';
			break;

			default:

				$_T['maintitle']='JEP - Reporte interno de creditos';
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
						Tipo de reporte
					</label><br>
					<input type="radio" name="tipo_reporte" value="MG" checked> Mejor Gestión<br>
					<input type="radio" name="tipo_reporte" value="G"> Gestión<br>
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