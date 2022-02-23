<?php
class Gestion_por_fechas_bco_gye implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db = DB::getInstance();
		$udn = getUdnByName('Banco de Guayaquil');
		$campanas = getCampanasByUdn($udn['id_udn']);

		switch($_get['step']) {
			case '2':
				$reporte = 'gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';

				//CAMBIO DE FORMATO POR PETICIÓN DE MARCO PALA 2021-01-12
				/*$result[$reporte]=array(
					array(
						'numero_operacion',
						'Apellidos_y_Nombres_del_Deudor',
						'Cedula_o_RUC',
						'Ciudad_de_Residencia_del_Cliente',
						'Ciudad_de_ubicacion_de_Agencia_que_otorga_la_operacion',
						'Monto_Inicial_del_Credito',
						'Saldo_de_Capital_Vencido',
						'Interes_Normal_Vencido_y_por_Vencer',
						'Interes_de_Mora_Vencido_',
						'No_Total_de_Cuotas_de_la_Operacion',
						'No_de_Cuotas_Canceladas',
						'No_de_Cuotas_Vencidas',
						'No_de_Cuotas_por_Vencer',
						'Dias_de_Mora',
						'Periodicidad',
						'Tipo_de_credito',
						'Tipo_de_garantia',
						'Calificacion_de_Cartera',
						'Estado_Juridico_de_la_operacion',
						'Tipo_Deudor',
						'total_deuda',
						'tipificacion',
						'usuario',
						'telefono_contacto',
						'fecha_gestion',
						'valor_promesa',
						'fecha_promesa',
						'id_llamada',
						'observaciones'
					)
				);*/
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
					pr.id_campana = '.$_post['id_campana'].' AND
					date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
					ORDER BY
					t.peso DESC,g.fecha_inicio DESC';
				foreach($db->query($query) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}

				$t_cache=array();
				foreach ($gestiones_x_cuenta as $num_cuenta=>$gestiones) {
					foreach ($gestiones as $gestion){
						$datos_nm = getCargaNoMapeada($gestion['g_id_cuenta']);
						$datos_nm = $datos_nm[$gestion['c_id_carga']];
						$line = array(
							//ciclo
							'',
							//'Cedula_o_RUC',
							$gestion['p_identificacion'],
							//'numero_operacion',
							$gestion['c_cuenta'],
							//'Apellidos_y_Nombres_del_Deudor',
							Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
							//diferencia
							$datos_nm['total_deuda'],
							//'motivo_gestion',
							$gestion['t_descripcion'],
							//'codigo_gestion',
							$gestion['t_tag'],
							//status_gestion
							'',
							//proveedor_asignado
							'RECAPT',
							//'fecha_gestion',
							$gestion['g_fecha_inicio'],
							//'telefono_contacto',
							$gestion['g_tel_number'],
							//'observaciones'
							$gestion['g_observacion'],
							//'fecha_promesa',
							$gestion['g_fecha_compromiso'],
							//campana
							'',
							//'usuario',
							$gestion['g_user_name'],
							//periodo
							'',
							//peso
							''
							
							
							//CAMBIO DE FORMATO POR PETICIÓN DE MARCO PALA 2021-01-12

							/*//'numero_operacion',
							$gestion['c_cuenta'],
							//'Apellidos_y_Nombres_del_Deudor',
							Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
							//'Cedula_o_RUC',
							$gestion['p_identificacion'],
							//'Ciudad_de_Residencia_del_Cliente',
							$datos_nm['ciudad_de_residencia_del_cliente'],
							//'Ciudad_de_ubicacion_de_Agencia_que_otorga_la_operacion',
							$datos_nm['ciudad_de_ubicacion_de_agencia_que_otorga_la_operacion'],
							//'Monto_Inicial_del_Credito',
							$datos_nm['monto_inicial_del_credito'],
							//'Saldo_de_Capital_Vencido',
							$datos_nm['saldo_de_capital_vencido'],
							//'Interes_Normal_Vencido_y_por_Vencer',
							$datos_nm['interes_normal_vencido_y_por_vencer'],
							//'Interes_de_Mora_Vencido',
							$datos_nm['interes_de_mora_vencido'],
							//'No_Total_de_Cuotas_de_la_Operacion',
							$datos_nm['no_total_de_cuotas_de_la_operacion'],
							//'No_de_Cuotas_Canceladas',
							$datos_nm['no_de_cuotas_canceladas'],
							//'No_de_Cuotas_Vencidas',
							$datos_nm['no_de_cuotas_vencidas'],
							//'No_de_Cuotas_por_Vencer',
							$datos_nm['no_de_cuotas_por_vencer'],
							//'Dias_de_Mora',
							$datos_nm['dias_de_mora'],
							//'Periodicidad',
							$datos_nm['periodicidad'],
							//'Tipo_de_credito',
							$datos_nm['tipo_de_credito'],
							//'Tipo_de_garantia',
							$datos_nm['tipo_de_garantia'],
							//'Calificacion_de_Cartera',
							$datos_nm['calificacion_de_cartera'],
							//'Estado_Juridico_de_la_operacion',
							$datos_nm['estado_juridico_de_la_operacion'],
							//'Tipo_Deudor',
							$datos_nm['tipo_deudor'],
							//'total_deuda',
							$datos_nm['total_deuda'],
							//'tipificacion',
							$gestion['t_descripcion'],
							//'usuario',
							$gestion['g_user_name'],
							//'telefono_contacto',
							$gestion['g_tel_number'],
							//'fecha_gestion',
							$gestion['g_fecha_inicio'],
							//'valor_promesa',
							$gestion['g_monto_compromiso'],
							//'fecha_promesa',
							$gestion['g_fecha_compromiso'],
							//'id_llamada',
							$gestion['g_telh_id'],
							//'observaciones'
							$gestion['g_observacion']*/
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

				$_T['maintitle']='Banco de Guayaquil - Gestión por fechas';
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