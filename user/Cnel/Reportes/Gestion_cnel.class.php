<?php
class Gestion_cnel implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {

		//if ($_SERVER['REMOTE_ADDR']!='192.168.29.99'){
			//die('Reporte en construcción');
		//}
		
		
		foreach (getProcesoByCampana(18) as $p) {
			// if ($p['status']!='1') continue;
			$procesos[$p['id_proceso']] = $p['id_proceso'] .' - '. $p['descripcion'];
		}

		switch($_get['step']) {
			case '2':

				if (empty($_post['id_proceso'])) throw new exception ('Seleccione proceso');
				if ($_post['fecha_desde']=='') throw new exception ('Seleccione Fecha inicio');
				if ($_post['fecha_hasta']=='') throw new exception ('Seleccione Fecha Fin');
				
				$db = DB::getInstance();
				$reporte = 'gestion_CNEL_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$result[$reporte][]=array(
					'item',
					'unidad_de_negocio',
					'numero_servicio',
					'cedula',
					'cliente',
					'estado',
					'deuda_total',
					'facturas_pendientes',
					'tarifa',
					'tipo_cliente',
					'cedula_valida',
					'rango_pla_pendientes',
					'numero_medidor',
					'serie_medidor',

					'tramo_de_mora',
					'mes',
					'costo_unitario',
					'mes_campana',
					'Descripcion_Canton',
					'Facturas_Pendientes',
					'40.00%',
					'Estado',
					'Data',

					'fecha_gestion',
					'user_name',
					'numero_contacto',
					'tipificacion',
					'peso_tipificacion',
					'observacion',
					'fecha_compromiso',
					'monto_compromiso',
					'origen_telefono'
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
					JOIN personas.persona p ON (c.id_deudor=p.id_persona)
					JOIN campanas.proceso pr ON (pr.id_proceso=c.id_proceso)
					JOIN campanas.campana camp ON (camp.id_campana=pr.id_campana)
					JOIN gestiones.tipificacion t ON (t.id_tipificacion=g.id_tipificacion)
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
						
						//get telefono origen
						$q = 'SELECT origen FROM medios_contacto.telefono WHERE id_persona='.$gestion['p_id_persona'].' AND telefono=\''.$gestion['g_tel_number'].'\'';
						$q0 = $db->query($q);
						$origen = $db->fetchOne($q0)['origen'];
						
						$line = array(
							// 'item',
							$datos_nm['Item'],
							// 'unidad_de_negocio',
							$datos_nm['Unidad de Negocio'],
							// 'numero_servicio',
							$gestion['c_cuenta'],
							// 'cedula',
							$gestion['p_identificacion'],
							// 'cliente',
							Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
							// 'estado',
							$datos_nm['Estado'],
							// 'deuda_total',
							$gestion['c_valor_original'],
							// 'facturas_pendientes',
							$datos_nm['Facturas_Pendientes'],
							// 'tarifa',
							$datos_nm['Tarifa'],
							// 'tipo_cliente',
							$datos_nm['Tipo Cliente'],
							// 'cedula_valida',
							$datos_nm['Cedula valida'],
							// 'rango_pla_pendientes',
							$datos_nm['Rango Pla Pendientes'],
							// 'numero_medidor',
							$datos_nm['Numero Medidor'],
							// 'serie_medidor',
							$datos_nm['Serie Medidor'],
							// 'tramo_de_mora',
							$datos_nm['tramo_de_mora'],
							// 'mes',
							$datos_nm['mes'],
							// 'costo_unitario',
							$datos_nm['costo_unitario'],
							// 'mes_campana',
							$datos_nm['mes_campana'],
							// 'Descripcion_Canton',
							$datos_nm['Descripcion_Canton'],
							// 'Facturas_Pendientes',
							$datos_nm['Facturas_Pendientes'],
							// '40.00%',
							$datos_nm['40.00%'],
							// 'Estado',
							$datos_nm['40.00%'],
							// 'Data',
							$datos_nm['data'],
							// 'fecha_gestion',
							$gestion['g_fecha_inicio'],
							// 'user_name',
							$gestion['g_user_name'],
							// 'numero_contacto',
							$gestion['g_tel_number'],
							// 'tipificacion',
							$tipificacion['descripcion'],
							// peso_tipificacion
							$tipificacion['peso'],
							// 'observacion',
							$gestion['g_observacion'],
							// 'fecha_compromiso',
							$gestion['g_fecha_compromiso'],
							// 'monto_compromiso'
							$gestion['g_monto_compromiso'],
							//  origen telefono
							$origen
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
					<div class="custom-control custom-radio">
						<label class="custom-control-label"><input type="radio" name="tipo_reporte" value="TG" checked>Toda la gestión</label>
						(<small>Incluye todos las gestiones realizadas</small>)
					</div>
					<div class="custom-control custom-radio">
						<label class="custom-control-label"><input type="radio" name="tipo_reporte" value="MG">Mejor gestión</label>
						(<small>Incluye unicamente la mejor gestión efectuada a cada cuenta</small>)
					</div>

					<br>
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