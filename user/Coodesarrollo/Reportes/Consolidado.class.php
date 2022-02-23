<?php

class Consolidado implements Reporte_Interface {
	private $cache=array();
	private $tipificaciones_cliente = array(
		'NO CONTESTA'=>'CLIENTE_NO_CONTESTA',
		'COMPROMISO DE PAGO'=>'CLIENTE_HACE_COMPROMISO_DE_PAGO',
		'EQUIVOCADO'=>'NUMERO_EQUIVOCADO',
		'VOLVER A LLAMAR'=>'CLIENTE_DESEA_REGESTION',
		'MSJ TERCEROS'=>'MENSAJE A TERCERO',
	);

    public function getCamposRequeridos() {
		return array(
			'ID_DEUDOR',
			'NUMEROPRESTAMO',
			'MínDeFECHA1',
			'AGENCIA',
			'NOMBRE DEL DEUDOR',
			'REMOVIDOS DE GESTION',
			'ULTIMA GESTION',
			'CONTACTO',
			'ORIGEN TEL',
			'CONTACTADOS CON SALDO',
			'TELEFONO',
			'ORIGEN TELEFÓNCIO',
			'NO CONTACTADOS',
			'TELEFONO',
			'ORIGEN TELEFÓNICO',
			'TIPIFICACIÓN',
			'ORIGEN TELEF',
			'TIPIFICACION DEL CLIENTE'
		);
	}

    public function getTData($id_tipificacion) {
		if(!array_key_exists($id_tipificacion,$this->cache)) {
			$db=DB::getInstance();
			$this->cache[$id_tipificacion]=$db->query('SELECT * FROM "aux_tables"."interagua_tipif_extra_data" join gestiones.tipificacion t using(id_tipificacion) WHERE id_tipificacion='.$id_tipificacion)->current();
		}
		return $this->cache[$id_tipificacion];
	}

	private function getMejorGestion($gestiones){
		if (!is_array($gestiones)) return null;
		usort($gestiones,function($a,$b){
			if($a['_tipificacion']['peso']==$b['_tipificacion']['peso']) return 0;
			return $a['_tipificacion']['peso']<$b['_tipificacion']['peso']? 1:-1;
		});
		return $gestiones;
	}

	private function getAgencia($id_cuenta,$id_carga){
		$db=DB::getInstance();
		$q='SELECT * FROM "cargas"."carga_no_mapeada" WHERE id_cuenta='.$id_cuenta.' AND id_carga='.$id_carga;
		foreach ($db->query($q)->fetchAll() as $value) {
		 	$ret[$value['campo']]=$value['valor'];
		}
		return $ret;
	}

	private function getNextArrayElement($array, $key) {
		$currentKey = key($array);
		while ($currentKey !== null && $currentKey != $key) {
			next($array);
			$currentKey = key($array);
		}
		return next($array);
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {

		if($_SERVER['REMOTE_ADDR']!=='10.0.201.128') die('Reporte en desarrollo, <a href="?mod=reportes/index">Regresar<a/>');

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
		switch($_get['step']) {
			case '2':
				if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_post['fecha_desde'])) throw new Exception('Fecha desde inválida');
                if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_post['fecha_hasta'])) throw new Exception('Fecha hasta inválida');
				$proceso=getProceso($_post['id_proceso']);

				if(!$proceso) throw new Exception('Proceso no existe');

				$desde = Helpers::dmy2ymd($_post['fecha_desde']);
				$hasta = Helpers::dmy2ymd($_post['fecha_hasta']);

				$reporte = 'consolidado_'.str_replace(' ','_',strtolower($udn['udn'])).'_'.date('dmY').'.txt';
				$result[$reporte]=array(
					array(
						'ID_DEUDOR',
						'NUMEROPRESTAMO',
						'MinDeFECHA1',
						'AGENCIA',
						'NOMBRE_DEL_DEUDOR',

						'REMOVIDOS_DE_GESTION',
						'ULTIMA_GESTION',
						'CONTACTO',
						'ORIGEN_TEL',

						'CONTACTADOS_CON_SALDO',
						'TELEFONO',
						'ORIGEN_TELEFONCIO',

						'NO_CONTACTADOS',
						'TELEFONO',
						'ORIGEN_TELEFONICO',
						'TIPIFICACION',
						'ORIGEN_TELEF',
						'TIPIFICACION_CLIENTE'
					)
				);
				$output=&$result[$reporte];
				$cuentas_agregadas=array();
				
				//GET DE CARGAS SEGUN PARAMETROS DEL REPORTE
				$cargas_del_proceso=array();
				foreach ($db->query('SELECT * FROM cargas.carga WHERE id_proceso='.$proceso['id_proceso'].' AND tipo_carga in (\'actualizacion\',\'cartera\') and date(fecha_carga) BETWEEN \''.$desde.'\' AND \''.$hasta.'\' ORDER BY fecha_carga ASC')->fetchAll() as $aux) {
				 	$cargas_del_proceso[$aux['id_carga']]=$aux;
				} 
//print_arr('cargas donde debe estar la cuenta');
//print_arr(array_keys($cargas_del_proceso));
				//RECORRER CARGAS DEL PROCESO
				foreach ($cargas_del_proceso as $id_c => $carga) {

					//RECORRER CUENTAS DE LA CARGA
					$q='SELECT 
						t.telefono,cu.*,p.identificacion,p.primer_nombre,p.segundo_nombre,p.primer_apellido,p.segundo_apellido 
					FROM cargas.carga c 
						JOIN cargas.carga_seg_cuentas sc USING (id_carga) 
						JOIN cuentas.cuenta cu on(cu.cuenta=sc.numero_cuenta) 
						JOIN personas.persona p on (cu.id_deudor=p.id_persona) 
						LEFT JOIN medios_contacto.telefono t USING(id_persona)
					WHERE c.id_carga='.$db->escape($id_c);
					foreach ($db->query($q)->fetchAll() as $row) {
						$cuentas_cliente[$row['id_cuenta']]['telefonos'][]=$row['telefono'];
						$cuentas_cliente[$row['id_cuenta']]['cuenta']=$row;
					}
					foreach ($cuentas_cliente as $id_cuenta => $cuenta_tel) {
						$cuenta = $cuenta_tel['cuenta'];
						$telefonos = $cuenta_tel['telefonos'];

						if (!in_array($cuenta['cuenta'],$cuentas_agregadas)){

							$cuentas_agregadas[]=$cuenta['cuenta'];
							
							//GET REMOVIDO DE GESTION
							$removido_gestion='';
							$ultima_gestion='';
							$contacto='';
							$origen_telefonico='';

							//GET CONTACTO CON SALDO
							$contactados_con_saldo='';
							$ccs_telefono='';
							$ccs_origen_telefonico='';
							
							//GET NO CONTACTADOS
							$no_contactado='';
							$nc_telefono='';
							$nc_origen_telefonico='';
							
							//TIPIFICACIONES
							$tipificacion='';
							$tipificacion_origen_telefonico='';
							$tipificacion_cliente='';

							//GET MEJOR GESTION DE LA CUENTA
							$mg = $this->getMejorGestion(getGestiones($id_cuenta))[0];
							if(!is_null($mg) || !empty($mg)){

								$cargas_cuenta = getCargasByCuenta($id_cuenta);
								$remover_gestion=false;
								in_array($mg['tel_number'], $telefonos)?$aux_origen_telefonico='coodesarrollo':$aux_origen_telefonico='repositorio c3';
								foreach (array_keys($cargas_del_proceso) as $a) {
									//var_dump(!in_array($a,array_keys($cargas_cuenta)));
									if(!in_array($a,array_keys($cargas_cuenta))){
										$remover_gestion = true;
										break;
									}
								}
								//print_arr($mg);die;
								
								if ($remover_gestion){
									//REMOVIDO _GESTION
									$removido_gestion=$cuenta['cuenta'];
									//SET ULTIMA GESTION
									if($mg['_tipificacion']['_metadata']['es_contacto_primera_persona']==1){
										$ultima_gestion='DIRECTO '.$mg['_tipificacion']['descripcion'];
									}elseif($mg['_tipificacion']['_metadata']['es_contacto_tercero']==1){
										$ultima_gestion='INDIRECTO '.$mg['_tipificacion']['descripcion'];
									}else{
										$ultima_gestion='NO CONTACTO '.$mg['_tipificacion']['descripcion'];
									}
									//CONTACTO
									$contacto=$mg['tel_number'];
									//ORIGEN_TEL
									$origen_telefonico=$aux_origen_telefonico;

									$tipificacion=$ultima_gestion;
									$tipificacion_cliente=$this->tipificaciones_cliente[$ultima_gestion];
								}else{

									if($mg['_tipificacion']['_metadata']['es_contacto_primera_persona']=='1' || $mg['_tipificacion']['_metadata']['es_contacto_tercero']=='1'){
										//"CONTACTADO CON SALDO"
										$contactados_con_saldo=$mg['_tipificacion']['descripcion'];
										$telefono=$mg['tel_number'];
										$ccs_origen_telefonico=$aux_origen_telefonico;

										$tipificacion=$contactados_con_saldo;
										$tipificacion_cliente=$this->tipificaciones_cliente[$tipificacion];
									}else{
										//"NO CONTACTO"
										$no_contactado=$mg['_tipificacion']['descripcion'];
										$nc_telefono=$mg['tel_number'];
										$nc_origen_telefonico=$aux_origen_telefonico;

										$tipificacion=$no_contactado;
										$tipificacion_cliente=$this->tipificaciones_cliente[$tipificacion];
									}

								}
								$tipificacion_origen_telefonico=$aux_origen_telefonico;
							}else{
								$tipificacion='NO CONTACTO';
								$tipificacion_cliente=$this->tipificaciones_cliente[$mg['_tipificacion']['descripcion']];
							}

							$line = array(
								// ID_DEUDOR
								$cuenta['identificacion'],
								//NUMEROPRESTAMO
								$cuenta['cuenta'],
								//MinDeFECHA1
								date('Y-m-d',strtotime($carga['fecha_carga'])),
								//'AGENCIA',
								$this->getAgencia($id_cuenta,$carga['id_carga'])['agencia'],
								//'NOMBRE_DEL_DEUDOR',
								Helpers::implodeNotEmpty(' ',array($cuenta['primer_nombre'],$cuenta['segundo_nombre'],$cuenta['primer_apellido'],$cuenta['segundo_apellido'])),


								//'REMOVIDOS_DE_GESTION',
								$removido_gestion,
								//'ULTIMA_GESTION',
								$ultima_gestion,
								//'CONTACTO',
								$contacto,
								//'ORIGEN_TEL',
								$origen_telefonico,


								//'CONTACTADOS_CON_SALDO',
								$contactados_con_saldo,
								//'TELEFONO',
								$telefono,
								//'ORIGEN_TELEFONCIO',
								$ccs_origen_telefonico,


								//'NO_CONTACTADOS',
								$no_contactado,
								//'TELEFONO',
								$nc_telefono,
								//'ORIGEN_TELEFONICO',
								$nc_origen_telefonico,


								//'TIPIFICACION',
								$tipificacion,
								//'ORIGEN_TELEF',
								$tipificacion_origen_telefonico,
								//'TIPIFICACION_CLIENTE'
								$tipificacion_cliente,
							);
							$output[]=$line;
						}
					}
				}

				return 'file';
			break;

			default:

				$_T['maintitle']='Banco Desarrollo - Reporte Consolidado';
				$_T['maincontent']='
				<script>
					var campanas='.json_encode($campanas).';
					var procesos='.json_encode($procesos).';
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