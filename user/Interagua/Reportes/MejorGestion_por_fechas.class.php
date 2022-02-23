<?php
class MejorGestion_por_fechas implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array(
			'ciclo',
			'nro fact adeudadas',
		);
	}

    public function getTData($id_tipificacion) {
		if(!array_key_exists($id_tipificacion,$this->cache)) {
			$db=DB::getInstance();
			$this->cache[$id_tipificacion]=$db->query('SELECT * FROM "aux_tables"."interagua_tipif_extra_data" join gestiones.tipificacion t using(id_tipificacion) WHERE id_tipificacion='.$id_tipificacion)->current();
		}
		return $this->cache[$id_tipificacion];

	}
	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db = DB::getInstance();
		$udn = getUdnByName('Interagua');
		$campanas = getCampanasByUdn($udn['id_udn']);
		foreach ($campanas as &$c) {
			$c['procesos']=getProcesoByCampana($c['id_campana']);
			foreach ($c['procesos'] as $p) {
				//if($p['status']!=1) continue;
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
				$reporte = 'mejor_gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$result[$reporte]=array(
					array(
						'Ciclo',
						'Identificación',
						'Contrato',
						'Nombre Ciclo',
						'Nombre',
						'# Facturas',
						'Saldo Pendiente',
						'Gestión',
						'Codced',
						'Estado',
						'Proveedor Asignado',
						'Fecha Gestión',
						'Teléfono',
						'Observación',
						'Login',
						'Fecha Compromiso',
						'Valor Compromiso',
						'Campaña',
						'__CantGestiones',
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
						t.peso DESC,g.fecha_inicio DESC

					';
				foreach($db->query($query) as $aux) {
					$gestiones_x_cuenta[$aux['c_id_cuenta']][]=$aux;
				}


				$t_cache=array();
				foreach($gestiones_x_cuenta as $num_cuenta=>$gestiones) {
					$gestion=&$gestiones[0];
					$datos_nm=getCargaNoMapeada($gestion['g_id_gestion']);
					$datos_nm_most_recent=array_keys($datos_nm)[0];
					$tdata=$this->getTData($gestion['t_id_tipificacion']);

					$line=array(
						// Ciclo
						$datos_nm[$datos_nm_most_recent]['ciclo'],
						// Identificación
						$gestion['p_identificacion'],
						// Contrato
						$gestion['c_cuenta'],
						// Nombre Ciclo
						$gestion['pr_descripcion'],
						// Nombre
						Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
						// # Facturas
						$datos_nm[$datos_nm_most_recent]['nro fact adeudadas'],
						// Saldo Pendiente
						$gestion['c_valor_actual'],
						// Gestión
						$gestion['t_descripcion'],
						// Codced
						$tdata['codced'],
						// Estado
						$tdata['estado'],
						// Proveedor Asignado
						'CANT',
						// Fecha Gestión
						date('d/m/Y H:i:s',strtotime($gestion['g_fecha_inicio'])),
						// Teléfono
						$gestion['g_tel_number'],
						// Observación
						$gestion['g_observacion'],
						// Login
						$gestion['g_user_name'],
						// Fecha Compromiso
						($gestion['g_fecha_compromiso']!='')?date('d/m/Y',strtotime($gestion['g_fecha_compromiso'])):'',
						// Valor Compromiso
						$gestion['g_monto_compromiso'],
						// Campaña
						$gestion['camp_campana'],
						// __CantGestiones
						count($gestiones),


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

				$_T['maintitle']='INTERAGUA - Reporte de mejor gestion por fechas';
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