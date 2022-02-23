<?php
	// FASE DE ANALISIS
	// preparamos statements
	$db->prepare('cuenta_existe','SELECT id_cuenta,valor_original,valor_actual FROM cuentas.cuenta WHERE cuenta=$1 AND id_proceso='.$db->escape($_POST['id_proceso']));
	$db->prepare('persona_existe','SELECT id_persona FROM personas.persona WHERE tipo_identificacion=$1 AND identificacion=$2 AND id_proceso=$3');
	$db->prepare('contacto_existe','SELECT id_medio_contacto FROM medios_contacto.medio_contacto WHERE tipo_medio=$1 AND id_persona=$2');
	$db->prepare('get_deudor','SELECT * FROM personas.persona WHERE id_persona=(SELECT id_deudor FROM cuentas.cuenta WHERE id_cuenta=$1)');
	$db->prepare('get_otras_personas','SELECT persona.*,cuenta_responsable.tipo_responsable FROM cuentas.cuenta_responsable JOIN personas.persona ON (persona.id_persona=cuenta_responsable.id_persona) WHERE id_cuenta=$1 AND tipo_responsable<>\'DEUDOR\'');

	// armamos contadores
	$contadores=array(
		'cuentas_nuevas'=>0,
		'cuentas_existentes'=>0,
	);

	$reporte_tpl=array(
		'cuenta'=>null,
		'esta_en_base_no_en_archivo'=>'NO',
		'nueva_cuenta'=>null,
		'valor_original_base'=>null,
		'valor_actual_base'=>null,
		'valor_actual_archivo'=>null,
		'valor_actual_db_con_actualizaciones'=>null,
		'valor_actual_db_archivo_difiere'=>null,
		'valor_actual_db_archivo_diferencia'=>null,
		'tiene_actualizaciones'=>null,
		'cant_actualizaciones'=>null,
		'suma_actualizaciones'=>null,
		'valor_actual_db_archivo_difiere_luego_de_actualizaciones'=>null,
		'valor_actual_db_archivo_luego_de_actualizaciones_diferencia'=>null,
		'deudor_archivo'=>null,
		'deudor_db'=>null,
		'deudor_coincide'=>null,
		'cambio_dato_de_deudor'=>null,
		'detalles'=>array()
	);


	$reporte_lines=array();
	$contadores=array(
		'total_cuentas'=>0,
		'cuentas_con_actualizaciones'=>0,
		'cuentas_nuevas'=>0,
		'cuentas_existentes'=>0,
		'cuentas_en_db_no_en_archivo'=>0,
		'cuentas_con_diferencia_valor_actual'=>0,
		'cuentas_con_diferencia_valor_actual_necesitan_correccion'=>0,
		'cuentas_con_diferencia_valor_actual_no_necesitan_correccion'=>0,
		'cuentas_sin_diferencia_valor_actual'=>0,
		'cuentas_con_deudor_diferente'=>0,
		'cuentas_con_deudor_igual'=>0,
		'cuentas_con_cambio_detalles_deudor'=>0,
		'cuentas_sin_cambio_detalles_deudor'=>0,						
	);
	foreach($ret as $nRec=>$rec) {
		
		if(is_null($rec)) continue;
		$consolidado_cuentas[]=$rec['cuenta']->numero_cuenta;
		// INICIO ANALISIS
		$suma_actualizaciones=0.0;
		foreach($rec['cuenta']->actualizaciones as $a) {
			$suma_actualizaciones+=$a->valor;
		}
		
		$cuenta_en_base=$db->execute('cuenta_existe',array($rec['cuenta']->numero_cuenta));
		$reporte=$reporte_tpl;
		$reporte['cuenta']=$rec['cuenta']->numero_cuenta;
		
		$reporte['tiene_actualizaciones']=(count($rec['cuenta']->actualizaciones)>0?'SI':'NO');
		$reporte['cant_actualizaciones']=count($rec['cuenta']->actualizaciones);
		$reporte['suma_actualizaciones']=$suma_actualizaciones;
		$reporte['deudor_archivo']=$rec['cuenta']->persona_responsable->tipo_identificacion.' - '.$rec['cuenta']->persona_responsable->identificacion;
		
		$contadores['total_cuentas']++;
		if(count($rec['cuenta']->actualizaciones)>0) {
			$contadores['cuentas_con_actualizaciones']++;
		}
		if($cuenta_en_base->numRows()==0) {
			// cuenta no existe
			$contadores['cuentas_nuevas']++;
			$reporte['nueva_cuenta']='SI';
			$reporte['valor_original_base']='N/A';
			$reporte['valor_actual_base']='N/A';
			$reporte['valor_actual_archivo']=$rec['cuenta']->valor_actual;
			$reporte['valor_actual_db_archivo_difiere']='NO';
			$reporte['valor_actual_db_archivo_diferencia']='0.0';
			$reporte['valor_actual_db_archivo_difiere_luego_de_actualizaciones']='NO';
			$reporte['valor_actual_db_archivo_luego_de_actualizaciones_diferencia']='0.0';
			$reporte['deudor_coincide']='SI';
			
			$reporte['detalles'][]='Cuenta nueva. Será creada.';
			if(!empty($rec['cuenta']->actualizaciones)) {
				$reporte['detalles'][]='Se aplicarán '.count($rec['cuenta']->actualizaciones).' actualizaciones.';
				$reporte['detalles'][]='El valor actual luego de actualizaciones sera de: '.$rec['cuenta']['valor_actual'].'+'.$suma_actualizaciones.'='.($rec['cuenta']['valor_actual']+$suma_actualizaciones);
			}
		}else{
			$contadores['cuentas_existentes']++;
			// cuenta existe
			$cuenta=$cuenta_en_base->current();
			// traemos al deudor
			$deudor=$db->execute('get_deudor',array($cuenta['id_cuenta']));
			if($deudor->numRows()==0)
				throw new Exception('Error fatal. No se consiguio al deudor en la cuenta '.$rec['cuenta']->numero_cuenta.' (Id: '.$cuenta['id_cuenta'].')');
			$deudor=$deudor->current();
			$reporte['deudor_db']=$deudor['tipo_identificacion'].' - '.$deudor['identificacion'];
			
			// otras personas
			$otras_personas=$db->execute('get_otras_personas',array($cuenta['id_cuenta']));
			
			$reporte['detalles'][]='Cuenta ya existe.';
			$reporte['nueva_cuenta']='NO';
			$reporte['valor_original_base']=$cuenta['valor_original'];
			$reporte['valor_actual_base']=$cuenta['valor_actual'];
			$reporte['valor_actual_archivo']=$rec['cuenta']->valor_actual;
			// validamos si el valor actual que vino en el archivo coincide con lo que hay en base
			$diferencia_db_archivo=$rec['cuenta']->valor_actual-$cuenta['valor_actual'];
			// diferencia luego de actualizaciones
			$diferencia_db_archivo_actualizaciones=$rec['cuenta']->valor_actual-($cuenta['valor_actual']+($suma_actualizaciones));
			
			if(count($rec['cuenta']->actualizaciones)>0) {
				$reporte['tiene_actualizaciones']='SI';
				$reporte['cant_actualizaciones']=count($rec['cuenta']->actualizaciones);
				$reporte['suma_actualizaciones']=$suma_actualizaciones;
				$reporte['valor_actual_db_con_actualizaciones']=$cuenta['valor_actual']+$suma_actualizaciones;
			}
			
			
			if($diferencia_db_archivo!=0) {
				$contadores['cuentas_con_diferencia_valor_actual']++;
				// valor actual en db difiere con lo que viene en el archivo
				$reporte['valor_actual_db_archivo_difiere']='SI';
				$reporte['valor_actual_db_archivo_diferencia']=$diferencia_db_archivo;
				if($diferencia_db_archivo_actualizaciones!=0) {
					$contadores['cuentas_con_diferencia_valor_actual_necesitan_correccion']++;
					// valor actual en db difiere con lo que viene en el archivo incluso luego de aplicar las actualizaciones
					$reporte['detalles'][]='Valor actual en DB luego de las actualizaciones ('.$cuenta['valor_actual'].' + '.$suma_actualizaciones.' = '.($cuenta['valor_actual']+$suma_actualizaciones).') difiere con lo que hay en el archivo ('.$rec['cuenta']->valor_actual.')';
					$reporte['detalles'][]='Se aplicarán '.(count($rec['cuenta']->actualizaciones)).' actualizaciones ('.$suma_actualizaciones.') y una CORRECCION por ('.$diferencia_db_archivo_actualizaciones.')';
					$reporte['valor_actual_db_archivo_difiere_luego_de_actualizaciones']='SI';
					$reporte['valor_actual_db_archivo_luego_de_actualizaciones_diferencia']=$diferencia_db_archivo_actualizaciones*(-1);
				}else{
					$contadores['cuentas_con_diferencia_valor_actual_no_necesitan_correccion']++;
					$reporte['detalles'][]='Valor actual en DB luego de las actualizaciones ('.$cuenta['valor_actual'].' + '.$suma_actualizaciones.' = '.($cuenta['valor_actual']+$suma_actualizaciones).') conicide con el archivo ('.$rec['cuenta']->valor_actual.')';
					$reporte['detalles'][]='Se aplicarán '.(count($rec['cuenta']->actualizaciones)).' actualizaciones ('.$suma_actualizaciones.')';
					$reporte['valor_actual_db_archivo_difiere_luego_de_actualizaciones']='NO';
					$reporte['valor_actual_db_archivo_luego_de_actualizaciones_diferencia']='0.0';
				}
			}else{
				$contadores['cuentas_sin_diferencia_valor_actual']++;
				$reporte['valor_actual_db_archivo_difiere']='NO';
				$reporte['valor_actual_db_archivo_diferencia']='0.0';
				// valor actual en db es igual a lo que viene en el archivo
				$reporte['detalles'][]='Valor actual en DB ('.$cuenta['valor_actual'].') conicide con el archivo ('.$rec['cuenta']->valor_actual.')';
				if(count($rec['cuenta']->actualizaciones)>0) {
					$reporte['detalles'][]='Se aplicarán '.(count($rec['cuenta']->actualizaciones)).' actualizaciones ('.$suma_actualizaciones.') y una CORRECCION por ('.($suma_actualizaciones*(-1)).') para mantener valor actual a lo que dice el archivo ('.$rec['cuenta']->valor_actual.')';
					$reporte['valor_actual_db_archivo_difiere_luego_de_actualizaciones']='SI';
					$reporte['valor_actual_db_archivo_luego_de_actualizaciones_diferencia']=$suma_actualizaciones*(-1);
				}
			}
			
			
			// comparacion de personas
			if($rec['cuenta']->persona_responsable->tipo_identificacion.$rec['cuenta']->persona_responsable->identificacion != $deudor['tipo_identificacion'].$deudor['identificacion']) {
				$contadores['cuentas_con_deudor_diferente']++;
				$reporte['deudor_coincide']='NO';
				$reporte['detalles'][]='Se cambiará el DEUDOR de '.$deudor['tipo_identificacion'].' - '.$deudor['identificacion'].' a '.$rec['cuenta']->persona_responsable->tipo_identificacion.' - '.$rec['cuenta']->persona_responsable->identificacion;
			}else{
				$contadores['cuentas_con_deudor_igual']++;
				$reporte['deudor_coincide']='SI';
				$cambios_en_persona=compara_persona($deudor,$rec['cuenta']->persona_responsable->getData());
				
				if(!empty($cambios_en_persona)) {
					$contadores['cuentas_con_cambio_detalles_deudor']++;
					$reporte['cambio_dato_de_deudor']='SI';
					foreach($cambios_en_persona as $k=>$v) {
						$reporte['detalles'][]='De persona DEUDOR, '.$deudor['tipo_identificacion'].' - '.$deudor['identificacion'].' (Id: '.$deudor['id_persona'].') se cambiara "'.$k.'" de "'.$v[0].'" a "'.$v[1].'"';
					}
				}else{
					$contadores['cuentas_sin_cambio_detalles_deudor']++;
					$reporte['cambio_dato_de_deudor']='NO';
				}
			}
			
			
			// otras personas en archivo vs db
			/*
			foreach($otras_personas as $op) {
				print_arr($op);
			}
			echo '-------';
			
			foreach($rec['cuenta']->otras_personas as $op) {
				print_arr($op['persona']->getData());
			}
			*/
			$aux=array();
			foreach($rec['cuenta']->otras_personas as $persona_en_archivo) {
				$existe=false;
				foreach($otras_personas as $persona_en_db) {
					if(
						$persona_en_db['tipo_identificacion']==$persona_en_archivo['persona']->tipo_identificacion
						&& $persona_en_db['identificacion']==$persona_en_archivo['persona']->identificacion
					) {
						
						$diferencia_persona=compara_persona($persona_en_db,$persona_en_archivo['persona']->getData());
						if(!empty($diferencia_persona)) {
							foreach($diferencia_persona as $k=>$v) {
								$aux='La persona '.$persona_en_archivo['persona']->tipo_identificacion.' - '.$persona_en_archivo['persona']->identificacion.' cambiará "'.$k.'" de "'.$v[0].'" a "'.$v[1].'"';
								$reporte['detalles'][]=$aux;
							}
							
							
						}
						if($persona_en_db['tipo_responsable']==$persona_en_archivo['tipo']) {
							$existe=true;
							break;
						}
					}
				}
				if(!$existe) {
					$reporte['detalles'][]='La persona '.$persona_en_archivo['persona']->tipo_identificacion.' - '.$persona_en_archivo['persona']->identificacion.' con relacion "'.$persona_en_archivo['tipo'].'" no existe en la base de datos. Sera agregada.';
				}
			}
			
			// otras personas en db vs archivo
			foreach($otras_personas as $persona_en_db) {
				$existe=false;
				foreach($rec['cuenta']->otras_personas as $persona_en_archivo) {
					if(
						$persona_en_db['tipo_identificacion']==$persona_en_archivo['persona']->tipo_identificacion
						&& $persona_en_db['identificacion']==$persona_en_archivo['persona']->identificacion
						&& $persona_en_db['tipo_responsable']==$persona_en_archivo['tipo']
					) {
						$existe=true;
						break;
					}
				}
				if(!$existe) {
					$reporte['detalles'][]='La persona '.$persona_en_db['tipo_identificacion'].' - '.$persona_en_db['identificacion'].' con tipo "'.$persona_en_db['tipo_responsable'].'" no existe en el archivo. La relacion sera eliminada.';
				}
				
			}
		}
		
		
		// FIN DE ANALISIS
		$reporte_lines[]=$reporte;
	}
	// evaluamos cuales cuentas no llegaron en el archivo
	$cuentas_en_db_no_en_archivo=get_cuentas_en_db_no_en_archivo($consolidado_cuentas,$proceso->id_proceso);
	if(!empty($cuentas_en_db_no_en_archivo)) {
		foreach($cuentas_en_db_no_en_archivo as $c) {
			$contadores['cuentas_en_db_no_en_archivo']++;

			$reporte=$reporte_tpl;
			foreach($reporte as $k=>&$r) {
				if($k=='detalles') continue;
				$r='N/A';
			}
			unset($r);
			$reporte['cuenta']=$c['numero_cuenta'];
			$reporte['esta_en_base_no_en_archivo']='SI';
			$reporte['nueva_cuenta']='NO';
			$reporte['valor_original_base']=$c['valor_original'];
			$reporte['valor_actual_base']=$c['valor_actual'];
			$reporte['deudor_db']=$c['deudor_db'];
			$reporte['detalles'][]='La cuenta está en la base pero no en el archivo. Se creará una CORRECCION de ('.($c['valor_actual']*(-1)).') para hacer valor_actual=0';
			$reporte_lines[]=$reporte;
		}
		//$reporte['detalles'][]=
	}

	// almacenamos los archivos
	$details_uid=uniqid();
	//$details_uid='xxx123';

	$output=array();
	$output[]=implode("\t",array_keys($contadores));
	$output[]=implode("\t",$contadores);
	file_put_contents(_TMP_UPLOAD_FOLDER.'/'.$details_uid.'_contadores.txt',implode("\r\n",$output));

	$first=true;
	$output=array();
	foreach($reporte_lines as $line) {
		if($first) {
			$output[]=implode("\t",array_keys($line));
			$first=false;
		}
		$line['detalles']=implode(' | ',$line['detalles']);
		$output[]=implode("\t",($line));
	}
	file_put_contents(_TMP_UPLOAD_FOLDER.'/'.$details_uid.'_detalles.txt',implode("\r\n",$output));

	$_T['maintitle']='Bases de Datos';
	$_T['maincontent']='<h3>Proceso de Carga - Resumen</h3>
	<table class="resumen_tbl">
	<tr><th>Empresa</th><td>'.$empresa->id_empresa.' - '.$empresa->nombre.'</td></tr>
	<tr><th>UDN</th><td>'.$udn->id_udn.' - '.$udn->udn.'</td></tr>
	<tr><th>Campaña</th><td>'.$campana->id_campana.' - '.$campana->campana.'</td></tr>
	<tr><th>Proceso</th><td>'.$proceso->id_proceso.' - '.$proceso->descripcion.'</td></tr>
	</table>

	<h3>Resumen:</h3>
	<div class="resume">
	';
	/*
	$_T['maincontent'].='
	'.$counters['cuentas_nuevas'].' cuentas nuevas <b>serán creadas</b>.
	<br>
	'.$counters['cuentas_existentes'].' cuentas ya existían previamente.
	<br>
	'.$counters['cuentas_existentes_con_valor_actual_diferente'].' cuentas tienen <span style="color: red;">valor actual distinto al calculado en base</span> (<b>serán actualizadas con "CORRECCION"</b>)
	<br>
	'.$counters['cuentas_existentes_con_valor_actual_igual'].' cuentas tienen valor actual igual al calculado en base
	';
	*/

	foreach($contadores as $k=>$v) {
		$_T['maincontent'].=$k.' = '.$v.'<br>';
	}

	$_T['maincontent'].='
	</div>
	<br>
	<a href="?mod='.$_GET['mod'].'&step=download_details&uid='.encrypt($details_uid).'" target="_blank" style="font-size: 14px; border: solid 1px #ccc; border-radius: 5px; padding: 10px; background-color: #D4D4FF;">Descargar archivos de detalles</a>
	<br>
	<hr>
	';
	if($data['step']!='') {
		$aux=Helpers::arr_to_url($_GET,array(),array('__upload'=>'1','step2'=>$data['step']));
	}else{
		$aux=Helpers::arr_to_url($_GET,array(),array('__upload'=>'1'));
	}
	$_T['maincontent'].='
	<form method="POST" action="?'.$aux.'">
		'.UI_Helper::array_to_hidden($_POST).'
		';
		foreach($data['hiddens'] as $k=>$v) {
			$_T['maincontent'].='<input type="hidden" name="'.$k.'" value="'.$v.'">';
		}
		$_T['maincontent'].='
		<input type="hidden" name="details_uid" value="'.encrypt($details_uid).'">
		<button class="btn btn-primary">Procesar Carga</button> <button class="btn btn-danger" type="button">Cancelar</button>
	</form>
	';