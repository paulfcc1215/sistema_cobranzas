<?php
class MejorGestion_por_fechas_bco_territorial implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db = DB::getInstance();
		$udn = getUdnByName('BANCO TERRITORIAL COMPRADA');
		$campanas = getCampanasByUdn($udn['id_udn']);

		switch($_get['step']) {
			case '2':
				$reporte = 'mejor_gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$result[$reporte]=array(
					array(
						'identificacion_deudor',
						'nombre_deudor',
						'numero_prestamo',
						'total_a_pagar',
						'comision_cobranza',
						'dias_mora',
						'fecha_ultimo_pago',
						'tipo_titular_garante_codeudor',
						'tipificacion',
						'usuario',
						'telefono_contacto',
						'telh_id',
						'fecha_gestion',
						'observaciones'
					)
				);
				$output=&$result[$reporte];
				$query='
					SELECT
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
                        pr.id_campana = '.$_post['id_campana'].' AND
                        date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
						ORDER BY
						t.peso DESC,g.fecha_inicio DESC';
				foreach($db->query($query) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}

				$t_cache=array();
				foreach($gestiones_x_cuenta as $num_cuenta=>$gestiones) {
					$gestion=&$gestiones[0];
					$datos_nm=getCargaNoMapeada($gestion['g_id_cuenta']);
					$datos_nm=$datos_nm[$gestion['c_id_carga']];
					$line = array(
						//'identificacion_deudor',
						$gestion['p_identificacion'],
						//'nombre_deudor',
						Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
						//'numero_prestamo',
						$gestion['c_cuenta'],
						//'total_a_pagar',
						$gestion['c_valor_original'],
						//'comision_cobranza',
						'',
						//'dias_mora',
						'',
						//'fecha_ultimo_pago',
						'',
						//'tipo_titular_garante_codeudor',
						'',
						//'tipificacion',
						$gestion['t_descripcion'],
						//'usuario',
						$gestion['g_user_name'],
						//'telefono_contacto',
						$gestion['g_tel_number'],
						//telh_id
						$gestion['g_telh_id'],
						//'fecha_gestion',
						$gestion['g_fecha_inicio'],
						//'observaciones'
						$gestion['g_observacion']
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

				$_T['maintitle']='Banco de Territorial - Mejor gestión por fechas';
				$_T['maincontent']='
				<script>

				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Campaña:</b>
					<br>
					<select name="id_campana" id="id_id_campana">
					<option value="">Seleccione...</option>
					';
					foreach($campanas as $c) {
						$_T['maincontent'].='<option value="'.$c['id_campana'].'">'.$c['id_campana'].' - '.$c['campana'].'</option>';
					}
					$_T['maincontent'].='
					</select>
					<br><br>
					<b>Indique Rango de Fecha</b>
					<br>
					<table border="0" class="t">
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
				</form>
				';
				return 'flow';
			break;
		}


	}

	public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {

	}
}