<?php
class MejorGestionMovistar_por_fechas implements Reporte_Interface {
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

				if ($_post['id_proceso']=='') throw new exception('Seleccione Proceso');
				if (empty($_post['id_proceso'])) throw new exception('Seleccione Proceso');
				$procesos = $_post['id_proceso'];
				foreach(getTipificacionesByCampana($_post['id_campana']) as $t){
					$tipificaciones[$t['id_tipificacion']] = $t;
				}
				if (empty($tipificaciones)) throw new exception('No existen tipificaciones definidas para la campaña seleccionada');

				$reporte = 'mejor_gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$result[$reporte]=array(
					array(
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
						'periodo'
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
						pr.id_proceso IN('.implode(',',$procesos).') 
						--pr.id_campana IN('.implode(',',$campanas).') 
						--AND pr.status=\'1\' 
						AND date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
					ORDER BY
						t.peso DESC,g.fecha_inicio DESC';
				foreach($db->query($query) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}
				$t_cache=array();
				foreach($gestiones_x_cuenta as $num_cuenta=>$gestiones) {
					$gestion=&$gestiones[0];
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
						//'telefono',
						$gestion['g_tel_number'],
						//'observacion',
						$gestion['g_observacion'],
						//'login',
						$gestion['g_user_name'],
						//'fecha_compromiso',
						$gestion['g_fecha_compromiso'],
						//'valor_compromiso',
						$gestion['g_monto_compromiso'],
						//'deuda',
						$gestion['c_valor_actual'],
						//'campana',
						'',
						//'periodo'
						$gestion['camp_campana']
 					);
					foreach($line as &$l) {
						$l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
						unset($l);
					}
					$output[]=$line;
				}

				//get no gestiones
				$q = 'SELECT c.id_cuenta,c.valor_original,c.cuenta,pr.descripcion,p.identificacion,p.primer_nombre,p.segundo_nombre,p.primer_apellido,p.segundo_apellido
					FROM cuentas.cuenta c
						JOIN personas.persona p ON(c.id_deudor=p.id_persona)
						JOIN campanas.proceso pr ON(pr.id_proceso=c.id_proceso)
					WHERE 
						c.id_proceso IN('.implode(',',$procesos).')
						AND c.id_cuenta NOT IN(
							SELECT DISTINCT(ges.id_cuenta)
							FROM gestiones.gestion ges
								JOIN cuentas.cuenta cue ON(ges.id_cuenta=cue.id_cuenta)
							WHERE 
								date(ges.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\' AND
								cue.id_proceso IN('.implode(',',$procesos).')
						)';

				foreach($db->query($q) as $no_gestion){
					$line=array(
						// Ciclo
						$no_gestion['descripcion'],
						//'identificacion',
						$no_gestion['identificacion'],
						//'codigo_cliente',
						$no_gestion['cuenta'],
						//'nombre',
						Helpers::implodeNotEmpty(' ',array($no_gestion['primer_nombre'],$no_gestion['segundo_nombre'],$no_gestion['primer_apellido'],$no_gestion['segundo_apellido'])),
						//'deuda_vencida',
						$no_gestion['valor_original'],
						//'gestion',
						'',
						//'codced',
						'',
						//'estado',
						'',
						//'proveedor_asignado',
						'RECAPT',
						//'fecha_gestion',
						'',
						//'telefono',
						'',
						//'observacion',
						'',
						//'login',
						'',
						//'fecha_compromiso',
						'',
						//'valor_compromiso',
						'',
						//'deuda',
						$no_gestion['valor_actual'],
						//'campana',
						'',
						//'periodo'
						''
					 );
					 $output[]=$line;
				}
				return 'file';
			break;

			default:

				$_T['maintitle']='MOVISTAR - Reporte de mejor gestión por fechas';
				$_T['maincontent']='
				<script>
					function change_campana(me){
						$("#id_id_proceso").empty();
						$("#id_id_proceso").append("<option value=\"\">Seleccione...</option>");
						if (me=="") return false;
						$.ajax({
							"url":"/cobranzas/ajax/getProcesosByCampanaId.php",
							"method":"post",
							"data":{
								"id_campana":me
							},
							success: function(res){
								res = JSON.parse(res);
								$.each(res,function(i,o){
									$("#id_id_proceso").append("<option value=\""+o.id_proceso+"\">"+o.id_proceso+" - "+o.descripcion+"</option>");
								})
							}
						})
					}
				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Campaña:</b>
					<br>
					<select class="form-control" name="id_campana" size="8" onchange="change_campana($(this).val())">
						<option value="">Seleccione...</option>';
						foreach($campanas as $c) {
							$_T['maincontent'].='<option value="'.$c['id_campana'].'">'.$c['id_campana'].' - '.$c['campana'].'</option>';
						}
						$_T['maincontent'].='
					</select>
					<br>
					<b>Seleccione Proceso:</b>
					<br>
					<select name="id_proceso[]" class="form-control" id="id_id_proceso" multiple size="8">
						<option value="">Seleccione...</option>
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