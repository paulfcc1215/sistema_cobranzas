<?php
class GestionMovistar_por_fechas implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array(
			'ciclo',
			'nro fact adeudadas',
		);
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db = DB::getInstance();
		$udn = getUdnByName('Movistar');
		$campanas = getCampanasByUdn($udn['id_udn']);
		
		switch($_get['step']) {
			case '2':
				if (empty($_post['id_campana'])) throw new exception('Seleccione campaña');
				$campanas = array();
				foreach($_post['id_campana'] as $c){
					if ($c=='') continue;
					$campanas[]=$c;
				}
				foreach($campanas as $c){
					foreach(getTipificacionesByCampana($c) as $t){
						$tipificaciones[$t['id_tipificacion']] = $t;
					}
					if (!empty($tipificaciones)) break;
				}
				$reporte = 'gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$result[$reporte]=array(
					array(
						'ciclo',
						'cedula',
						'cod_cliente',
						'nombre_del_cliente',
						'diferencia',
						'motivo_gestion',
						'codigo_gestion',
						'status_gestion',
						'proveedor_asignado',
						'fecha_gestion',
						'contacto',
						'observaciones',
						'fecha_compromiso',
						'campana',
						'agente',
						'periodo',
						'peso'
					)
				);
				$output=&$result[$reporte];
				$query='SELECT
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
					pr.id_campana IN ('.implode(',',$campanas).') 
					--AND pr.status=\'1\' 
					AND date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
					ORDER BY
					t.peso DESC,g.fecha_inicio DESC';

				/*foreach($db->query($query) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}*/
				$t_cache=array();
				foreach($db->query($query) as $gestion) {
					$estado='INCON';
					if ($tipificaciones[$gestion['g_id_tipificacion']]['_metadata']['es_contacto_primera_persona']){
						$estado='CONTA';
					}
					$line=array(
						// Ciclo
						$gestion['pr_descripcion'],
						//'identificacion',
						$gestion['p_identificacion'],
						//'codigo_cliente',
						$gestion['c_cuenta'],
						//'nombre',
						Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
						//'deuda_vencida',
						$gestion['c_valor_original'],
						//'gestion',
						$gestion['t_descripcion'],
						//'codced',
						$gestion['t_tag'],
						//'estado',
						$estado,
						//'proveedor_asignado',
						'RECAPT',
						//'fecha_gestion',
						date('d/m/Y H:i',strtotime($gestion['g_fecha_inicio'])),
						//'contacto',
						$gestion['g_tel_number'],
						//'observacion',
						$gestion['g_observacion'],
						//'fecha_compromiso',
						$gestion['g_fecha_compromiso'],
						//'campana',
						'',
						//'agente',
						$gestion['g_user_name'],
						//'periodo'
						$gestion['camp_campana'],
						//peso
						$gestion['t_peso']

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

				$_T['maintitle']='MOVISTAR - Reporte de gestión por fechas';
				$_T['maincontent']='
				<script>
					
				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Campaña:</b>
					<br>
					<select name="id_campana[]" id="id_id_campana" multiple size="8">
					<option value="">Seleccione...</option>
					';
					foreach($campanas as $c) {
						$_T['maincontent'].='<option value="'.$c['id_campana'].'">'.$c['id_campana'].' - '.$c['campana'].'</option>';
					}
					$_T['maincontent'].='
					</select>
					<br><br>
					<b>Indique Rango de Fechas:</b>
					<br>
					<table>
						<tr>
							<td>
								Desde:<input type="text" name="fecha_desde" class="fecha" value="'.date('d/m/Y').'">
							</td>
						</tr>
						<tr>
							<td>
								Hasta:<input type="text" name="fecha_hasta" class="fecha" value="'.date('d/m/Y').'">
							</td>
						</tr>
					</table>
					<br><br>
					<button class="btn btn-primary">Siguiente</button>
					<br><br>
					<a href="?mod=reportes/index">Regresar</a>
				</form>
				';
				return 'flow';
			break;
		}


	}

	public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {

	}
}