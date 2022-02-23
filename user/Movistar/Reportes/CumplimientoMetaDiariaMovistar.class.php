<?php
class CumplimientoMetaDiariaMovistar implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

    public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db = DB::getInstance();
		$udn = getUdnByName('Movistar');
		$campanas = getCampanasByUdn($udn['id_udn']);
		foreach ($campanas as &$c) {
			$c['procesos']=getProcesoByCampana($c['id_campana']);
			foreach ($c['procesos'] as $p) {
				if($p['status']!=1) continue;
				$procesos[$p['id_campana']][$p['id_proceso']]=$p;
			}
			unset($c);
		}
		
		switch($_get['step']) {
			case '2':
				if ($_post['id_proceso']==''){
					$aux_procesos = array_keys($procesos[$_post['id_campana']]);
				}else{
					$aux_procesos = array($_post['id_proceso']);
				}
				$reporte_data = array();

				foreach($aux_procesos as $id_proceso){
					$descripcion_proceso = $procesos[$_post['id_campana']][$id_proceso]['descripcion'];

					//get valores CARTERA
					$q = 'SELECT 
						cnm.valor AS tramo,count(*) AS cuentas,sum(c.valor_original) AS cobro_empresa
					FROM cargas.carga_no_mapeada cnm
						JOIN cuentas.cuenta c ON(c.id_cuenta=cnm.id_cuenta)
					WHERE 
						c.id_proceso='.$id_proceso.' 
						AND cnm.campo=\'venc_gestion\'
						AND cnm.id_carga = (SELECT max(id_carga) FROM cargas.carga WHERE id_proceso='.$id_proceso.' AND tipo_carga=\'actualizacion\')
					GROUP BY cnm.valor';
					
					foreach($db->query($q) as $q0){
						$reporte_data[$descripcion_proceso][$q0['tramo']]['cuentas']=$q0['cuentas'];
						$reporte_data[$descripcion_proceso][$q0['tramo']]['cobro_empresa']=$q0['cobro_empresa'];
					}

					//get valores PAGOS
					$q = 'SELECT
						cnm.valor AS TRAMO, count(*) AS cuentas, sum(ca.diferencia) AS pagos
					FROM cargas.carga_no_mapeada cnm 
						JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=cnm.id_cuenta)
					WHERE 
						cnm.id_carga=(SELECT max(id_carga) FROM cargas.carga WHERE id_proceso='.$id_proceso.' AND tipo_carga=\'actualizacion\') 
						AND cnm.campo=\'venc_gestion\'
						AND ca.tipo_actualizacion=\'PAGO\'
						AND ca.id_carga=(SELECT max(id_carga) FROM cargas.carga WHERE id_proceso='.$id_proceso.' AND tipo_carga=\'recaudacion\') 
					GROUP BY cnm.valor';
					foreach($db->query($q) as $q0){
						$reporte_data[$descripcion_proceso][$q0['tramo']]['pagos']=$q0['pagos'];
					}
					//get valores AJUSTES
					$q = 'SELECT 
						cnm.valor AS tramo, count(*) AS cuentas, sum(ca.diferencia) AS ajustes
					FROM cargas.carga_no_mapeada cnm 
						JOIN cuentas.cuenta_actualizacion ca ON(ca.id_cuenta=cnm.id_cuenta)
					WHERE 
						cnm.id_carga=(SELECT max(id_carga) FROM cargas.carga WHERE id_proceso='.$id_proceso.' AND tipo_carga=\'actualizacion\') 
						AND cnm.campo=\'venc_gestion\'
						AND ca.tipo_actualizacion=\'AJUSTE-\'
						AND ca.id_carga=(SELECT max(id_carga) FROM cargas.carga WHERE id_proceso='.$id_proceso.' AND tipo_carga=\'recaudacion\') 
					GROUP BY cnm.valor';
					foreach($db->query($q) as $q0){
						$reporte_data[$descripcion_proceso][$q0['tramo']]['ajustes']=$q0['ajustes'];
					}
				}
				
				foreach ($reporte_data as $ciclo => $tramos){
					$result .='<b>'.$ciclo.'</b><br>';
					$result .='<table border="1"><tr>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Tramo</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Ctas.Asignadas</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Cobro Empresa</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Pagos</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Ajustes</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Dif.Empresa</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Meta(%)</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Recuperado(%)</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Valor Meta</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Por Recuperar</th>';
					$result .='<th style="text-align:center;padding:5px;background-color:#D9E3FF;">Recuperación Act.</th></tr>';
					$count_ctas = 0;
					$count_cobro_empresas = 0;
					$count_pagos = 0;
					$count_ajustes = 0;
					$tot_dif_cob_emp = 0;
					foreach($tramos as $tramo => $detalles){
						$pagos = $detalles['pagos'];
						if ($detalles['pagos']<0) $pagos=$pagos*-1;
						$ajustes = $detalles['ajustes'];
						if ($detalles['ajustes']<0) $ajustes=$ajustes*-1;
						$recuperacion_actual = $pagos+$ajustes;
						$dif_empresa = $detalles['cobro_empresa']-($recuperacion_actual);
						$porc_recuperado = $recuperacion_actual/$detalles['cobro_empresa'];

						$count_ctas += $detalles['cuentas'];
						$count_cobro_empresas += $detalles['cobro_empresa'];
						$count_pagos += $pagos;
						$count_ajustes += $ajustes;

						if ($tramo=='0') $meta=89;
						if ($tramo=='30') $meta=42;
						if ($tramo=='60') $meta=20;
						if ($tramo=='90') $meta=15;

						$valor_meta = ($detalles['cobro_empresa']*$meta)/100;
						$por_recuperar = $valor_meta-$recuperacion_actual;

						$result.='<tr><td style="text-align:right;padding:5px;">'.$tramo.'</td>';
						$result.='<td style="text-align:right;padding:5px;">'.$detalles['cuentas'].'</td>';
						$result.='<td style="text-align:right;padding:5px;">$ '.number_format($detalles['cobro_empresa'],2).'</td>';
						$result.='<td style="text-align:right;padding:5px;">$ '.number_format($detalles['pagos']*-1,2).'</td>';
						$result.='<td style="text-align:right;padding:5px;">$ '.number_format($detalles['ajustes']*-1,2).'</td>';
						$result.='<td style="text-align:right;padding:5px;">$ '.number_format($dif_empresa,2).'</td>';
						$result.='<td style="text-align:right;padding:5px;">'.$meta.' %</td>';
						$result.='<td style="text-align:right;padding:5px;">'.number_format($porc_recuperado*100,2).' %</td>';
						$result.='<td style="text-align:right;padding:5px;">$ '.number_format($valor_meta,2).'</td>';
						$result.='<td style="text-align:right;padding:5px;">$ '.number_format($por_recuperar,2).'</td>';
						$result.='<td style="text-align:right;padding:5px;">$ '.number_format($recuperacion_actual,2).'</td></tr>';
					}
					$tot_dif_cob_emp = $count_cobro_empresas - ($count_pagos+$count_ajustes);
					$result .='<tr><td style="text-align:right;padding:4px;background-color:#D9FFF0;"><b>Total:</b></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"><b>'.$count_ctas.'</b></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"><b>$ '.number_format($count_cobro_empresas,2).'</b></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"><b>$ '.number_format($count_pagos,2).'</b></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"><b>$ '.number_format($count_ajustes,2).'</b></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"><b>$ '.number_format($tot_dif_cob_emp,2).'</b></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"></td>';
					$result .='<td style="text-align:right;padding:4px;background-color:#D9FFF0;"></td></tr>';
					$result.='</table><br>';
				}
				$result .= '<a href="?mod=reportes/dispatcher&id_reporte='.$_get['id_reporte'].'">Volver</a>';
				

				return 'flow';
				//return 'raw_output';
			break;

			default:

				$_T['maintitle']='MOVISTAR - Cumplimiento de meta diaria';
				$_T['maincontent']='
				<script>
					var campanas='.json_encode($campanas).';
					var procesos='.json_encode($procesos).';
					function changeCampanas(campana) {
						$("#id_id_proceso").empty();
						if(campana==""){
							$("#id_id_proceso").append("<option value=\"\">Seleccione...</option>");
							return
						};
						$("#id_id_proceso").append("<option value=\"\">--Todos--</option>");
						for(var i in procesos[campana]) {
							var ptr=procesos[campana][i];
							$("#id_id_proceso").append("<option value=\""+ptr.id_proceso+"\">"+ptr.id_proceso+" - "+ptr.descripcion+"</option>");
						}
					}
				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Campaña:</b>
					<br>
					<select name="id_campana" id="id_id_campana" onchange="changeCampanas(this.value)">
					<option value="">Seleccione...</option>
					';
					foreach($campanas as $c) {
						$_T['maincontent'].='<option value="'.$c['id_campana'].'">'.$c['id_campana'].' - '.$c['campana'].'</option>';
					}
					$_T['maincontent'].='
					</select>
					<br><br>
					<b>Seleccione Proceso</b>
					<br>
					<select name="id_proceso" id="id_id_proceso">
					<option value="">Seleccione...</option>
					</select>
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