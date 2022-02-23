<?php
class Acumulado implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array(
			'ord',
			'agencia',
			'cedula',
			'numero_credito',
			'tipo_accion',
			'fue_atendido',
			'obervacion(usuario,telefono,observacion)',
			'fecha_gestion'
		);
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db=DB::getInstance();
		$udn=getUdnByName('Banco Desarrollo');
		$campanas=getCampanasByUdn($udn['id_udn']);
		foreach($campanas as &$c) {
			$c['procesos']=getProcesoByCampana($c['id_campana']);
			foreach($c['procesos'] as $p) {
				if($p['status']!=1) continue;
				$procesos[$p['id_campana']][]=$p;
			}
			unset($c);
		}
		foreach ($procesos as $id_campana => $proceso) {
			foreach ($proceso as $p) {
				$cargas[$p['id_proceso']]=getCargasByProcesoId($p['id_proceso']);
			}
		}
		switch($_get['step']) {
			case '2':
				if($_post['id_proceso']=='') throw new Exception('Seleccione proceso');
				if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_post['fecha_desde'])) throw new Exception('Fecha desde inválida');
                if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_post['fecha_hasta'])) throw new Exception('Fecha hasta inválida');
				$proceso=getProceso($_post['id_proceso']);
				if(!$proceso) throw new Exception('Proceso no existe');

				$reporte = 'acumulado_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				//Marco pide que se quite el encabezado 03042020
				/*$result[$reporte]=array(
					array(
						'ord',
						'agencia',
						'cedula',
						'numero_credito',
						'tipo_accion',
						'fue_atendido',
						'obervacion(usuario,telefopno,observacion)',
						'fecha_gestion'
					)
				);*/
				$output=&$result[$reporte];
				$q='
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
						--JOIN cargas.carga ca ON (ca.id_proceso=pr.id_proceso)
						JOIN campanas.campana camp USING (id_campana)
						JOIN personas.persona p ON (c.id_deudor=p.id_persona)
						JOIN gestiones.tipificacion t USING (id_tipificacion)
					WHERE
						g.id_cuenta IN (SELECT id_cuenta FROM cuentas.cuenta WHERE id_proceso='.$proceso['id_proceso'].')
						AND date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
						--AND ca.id_carga in ('.implode(",",$_post['id_carga']).')
					ORDER BY g.fecha_inicio ASC
				';
				$t_cache=array();
				$ord=1;
				$tipificacion_tipoaccion= array(
					'ACUERDO DE PAGO'=>array('tipo_accion'=>'04','atendido'=>'1'),
					'DIRECTO INDICA QUE PAGO'=>array('tipo_accion'=>'05','atendido'=>'1'),
					'DIRECTO COMPROMISO DE PAGO'=>array('tipo_accion'=>'06','atendido'=>'1'),
					'DIRECTO SOLICITA REFINANCIA'=>array('tipo_accion'=>'07','atendido'=>'1'),
					'DIRECTO NEGATIVA DE PAGO'=>array('tipo_accion'=>'08','atendido'=>'1'),
					'DIRECTO DESCONOCE CREDITO'=>array('tipo_accion'=>'09','atendido'=>'1'),
					'INDIRECTO MENSAJE TERCEROS'=>array('tipo_accion'=>'10','atendido'=>'1'),
					'INDIRECTO FALLECIDO'=>array('tipo_accion'=>'11','atendido'=>'1'),
					'INDIRECTO GRABADORA'=>array('tipo_accion'=>'12','atendido'=>'0'),
					'INDIRECTO CANALES MASIVOS'=>array('tipo_accion'=>'13','atendido'=>'0'),
					'ENVIO IVR'=>array('tipo_accion'=>'13','atendido'=>'0'),
					'ENVIO WHATSAPP'=>array('tipo_accion'=>'13','atendido'=>'0'),
					'ENVIO EMAIL'=>array('tipo_accion'=>'13','atendido'=>'0'),
					'ENVIO SMS'=>array('tipo_accion'=>'13','atendido'=>'0'),
					'NO CONTACTO NO CONTESTA'=>array('tipo_accion'=>'14','atendido'=>'0'),
					'NO CONTACTO DAÑADO'=>array('tipo_accion'=>'15','atendido'=>'0'),
					'ILOCALIZABLE NO EXISTE'=>array('tipo_accion'=>'16','atendido'=>'0'),
					'ILOCALIZABLE NUMERO EQUIVOCADO'=>array('tipo_accion'=>'17','atendido'=>'0'),
				);
				foreach($db->query($q) as $gestion) {
					$line=array(
						// Ord
						$ord,
						//'agencia',
						'049',
						//'cedula',
						$gestion['p_identificacion'],
						//'numero_credito',
						$gestion['c_cuenta'],
						//'tipo_accion',
						$tipificacion_tipoaccion[$gestion['t_descripcion']]['tipo_accion'],
						//fue_atendido
						$tipificacion_tipoaccion[$gestion['t_descripcion']]['atendido'],
						//'obervacion(usuario,telefopno,observacion)',
						$gestion['g_user_name'].','.$gestion['g_tel_number'].','.$gestion['g_observacion'],
						//'fecha_gestion'
						date('d/m/Y h:i',strtotime($gestion['g_fecha_inicio'])),
						// Nombre
						//Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
						//5 columnas en blanco
						'',
						'',
						'',
						'',
						'',
 					);
 					$ord++;
					foreach($line as &$l) {
						$l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
						unset($l);
					}
					$output[]=$line;
				}
				return 'file';
			break;

			default:

				$_T['maintitle']='Banco Desarrollo - Reporte Acumulado';
				$_T['maincontent']='
				<script>
					var campanas='.json_encode($campanas).';
					var procesos='.json_encode($procesos).';
					var cargas='.json_encode($cargas).';
					function updateProcesos(campana) {
						var proceso_combo=$("#id_id_proceso");
						proceso_combo.html("<option value=\'\'>Seleccione...</option>");
						if(campana=="") return;
						var html=[];
						for(var i in procesos[campana]) {
							var ptr=procesos[campana][i];
							html.push("<option value=\'"+ptr.id_proceso+"\'>"+ptr.id_proceso+" - "+ptr.descripcion+"</option>");
						}
						proceso_combo.html(proceso_combo.html()+html.join(""));

					}
					function updateCargas(proceso) {
						var var_procesos = procesos[$("#id_id_campana").val()];
						$.each(var_procesos,function(i,o){
							if(proceso==o.id_proceso){
								var aux = o.fecha_apertura.split(" ")[0].split("-")
								var fecha_desde = aux[2]+"/"+aux[1]+"/"+aux[0];
								$("#fecha_desde").val(fecha_desde);
							}
						})
						var carga_combo=$("#id_carga");
						carga_combo.html("<option value=\'\'>Seleccione...</option>");
						if(proceso=="") return;
						var html=[];
						for(var i in cargas[proceso]) {
							var ptr=cargas[proceso][i];
							html.push("<option value=\'"+ptr.id_carga+"\'>"+ptr.id_carga+" - "+ptr.descripcion+"</option>");
						}
						carga_combo.html(carga_combo.html()+html.join(""));
					}
				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Campaña:</b>
					<br>
					<select name="id_campana" id="id_id_campana" onchange="updateProcesos(this.value)">
						<option value="">Seleccione...</option>
						';
						foreach($campanas as $c) {
							$_T['maincontent'].='<option value="'.$c['id_campana'].'">'.$c['id_campana'].' - '.$c['campana'].'</option>';
						}
						$_T['maincontent'].='
					</select>
					<br><br>
					<b>Seleccione Proceso:</b>
					<br>
					<select name="id_proceso" id="id_id_proceso" onchange="updateCargas(this.value)">
						<option value="">Seleccione...</option>
					</select>
					<!--<br><br>
					<b>Seleccione Carga:</b>
					<br>
					<select name="id_carga[]" id="id_carga" multiple style="width:300px; height:200px;">
						<option value="">Seleccione...</option>
					</select>-->
					<br><br>
					<!--<input type="hidden" name="fecha_desde" id="fecha_desde" />-->
					<!--<b>Indique Rango de Fecha</b>-->
					<br>
					<table border="0" class="t">
						<tr>
							<td>
								Desde:<input type="text" name="fecha_desde" id="fecha_desde" placeholder="Seleccione proceso" readonly>
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
