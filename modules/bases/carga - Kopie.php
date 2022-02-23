<?php
$_AM['udns']=AutoModel::getInstance('estructura','udn',DB::getInstance());
$_AM['campanas']=AutoModel::getInstance('campanas','campana',DB::getInstance());
$_AM['proceso']=AutoModel::getInstance('campanas','proceso',DB::getInstance());
$_AM['empresa']=AutoModel::getInstance('estructura','empresa',DB::getInstance());
$_AM['metadata_usable']=AutoModel::getInstance('metadata','metadata_usable',DB::getInstance());
$_AM['carga']=AutoModel::getInstance('cargas','carga',DB::getInstance());
$_AM['cuenta']=AutoModel::getInstance('cuentas','cuenta',DB::getInstance());

$BM=new Benchmark();
function compara_persona($persona_a,$persona_b) {
	$cambios_en_persona=array();
	foreach(array(
		'primer_nombre','segundo_nombre','primer_apellido','segundo_apellido',
	) as $f) {
		if($persona_a[$f]!=$persona_b[$f]) {
			$cambios_en_persona[$f]=array(
				$persona_a[$f],
				$persona_b[$f]
			);
		}
	}
	return $cambios_en_persona;
}

function get_cuentas_en_db_no_en_archivo($en_archivo,$id_proceso) {
	GLOBAL $db;
	$temp=tempnam(_TMP_UPLOAD_FOLDER,'uploads');
	$tname=basename($temp);
	file_put_contents($temp,implode("\r\n",$en_archivo));
	chmod($temp,0666);
	$db->query('CREATE TEMPORARY TABLE "'.$tname.'_en_archivo" (numero_cuenta TEXT)');
	$db->query('COPY "'.$tname.'_en_archivo" FROM \''.$temp.'\'');
	unlink($temp);
	$db->query('CREATE INDEX idx_'.uniqid().' ON "'.$tname.'_en_archivo" USING BTREE(numero_cuenta)');
	

	
	$db->query('
	CREATE TEMPORARY TABLE "'.$tname.'_en_db_no_en_archivo" AS
	SELECT
	cuenta::text AS numero_cuenta,
	valor_original::float AS valor_original,
	valor_actual::float AS valor_actual,
	(
		SELECT CONCAT(tipo_identificacion,\' - \',identificacion) FROM personas.persona p WHERE p.id_persona=(
			SELECT id_persona FROM cuentas.cuenta_responsable WHERE tipo_responsable=\'DEUDOR\' AND id_cuenta=c.id_cuenta
		)
	) AS deudor_db
	FROM
	cuentas.cuenta c JOIN cargas.carga u ON (c.id_carga=u.id_carga)
	WHERE
	u.id_proceso='.$id_proceso.'
	AND NOT EXISTS (
		SELECT numero_cuenta FROM "'.$tname.'_en_archivo" WHERE "'.$tname.'_en_archivo".numero_cuenta=c.cuenta
	)
	');
	$ret=array();
	foreach($db->query('SELECT * FROM "'.$tname.'_en_db_no_en_archivo"') as $r) {
		$ret[]=$r;
	}
	return $ret;
}

switch($_GET['step']) {
    case 'ajax':
        $ret=array(
            'success'=>true,
            'data'=>null
        );
        try {
            switch($_GET['a']) {
                case 'getCampanas':
                    if(!preg_match('#^\d+$#',$_POST['id_udn'])) throw new Exception('Id UDN Inválida');
                    $campanas=$_AM['campanas']->getByAndCond(array('id_udn'=>$_POST['id_udn']));
                    $ret['data']=array();
                    foreach($campanas as $c) {
                        $ret['data'][]=array(
                            'id_campana'=>$c->id_campana,
                            'campana'=>$c->campana
                        );
                    }
                break;
                case 'getProcesos':
                    if(!preg_match('#^\d+$#',$_POST['id_camp'])) throw new Exception('Id Campaña Inválida');
					$ret['data']=getProcesosByCampId($_POST['id_camp']);
                break;
                
                
                case 'getCargasHandler':
					$ret['data']=getCargasHandler($_POST['id_camp']);
					foreach($ret['data'] as &$d) {
						$d['fname']=encrypt($d['fname']);
						unset($d);
					}
                break;
            }
        }catch(Exception $e) {
            $ret['success']=false;
            $ret['data']=$e->getMessage();
            echo json_encode($ret);
			die();
        }
        echo json_encode($ret);
        die();
    break;
	
	case 'download_details':
		$uid=decrypt($_GET['uid']);
		$dhdl=opendir(_TMP_UPLOAD_FOLDER);
		if(!$dhdl) die('Error al abrir tmp folder');
		while($ptr=readdir($dhdl)) {
			if($ptr=='.' || $ptr=='..') continue;
			$aux=explode('_',$ptr);
			if($aux[0]==$uid) $list[]=$ptr;
		}
		$tempfile=tempnam('/tmp','zip');
		$zip=new ZipArchive();
		$zip->open($tempfile,ZipArchive::CREATE);
		foreach($list as $l) {
			$aux=explode('_',$l);
			unset($aux[0]);
			
			$zip->addFile(_TMP_UPLOAD_FOLDER.'/'.$l,implode('_',$aux));
		}
		$zip->close();
		header('Content-Disposition: Attachment; filename="detalles.zip"');
		header('Content-Type: application/octect-stream');
		header('Content-Length: '.strlen(file_get_contents($tempfile)));
		
		
		readfile($tempfile);
		die();
	break;
	
	case '2':
		$_T['css']='
		.resumen_tbl th {
			background-color: #ccc;
			text-align: right;
			padding: 5px;
		}
		.resumen_tbl td {
			padding: 5px;
			border:solid 1px #ccc;
		}
		
		.resume {
			font-size: 16px;
			margin-left: 18px;
		}
		';
		try {
			if($_GET['step2']!='1' && $_GET['step2']!='') {
				foreach($SM->carga_process as $k=>$v) {
					$_POST[$k]=$v;
				}
			}
			if(!preg_match('#^\d+$#',$_POST['id_udn'])) throw new Exception('La UDN indicada es inválida');
			if(!preg_match('#^\d+$#',$_POST['id_campana'])) throw new Exception('La Campana indicada es inválida');
			if(!preg_match('#^\d+$#',$_POST['id_proceso'])) throw new Exception('El Proceso indicado es inválido');
			$carga_handler=decrypt($_POST['carga_handler']);
			if($carga_handler===false) throw new Exception('Carga handler invalido');

			$campana=$_AM['campanas']->getByAndCond(array('id_campana'=>$_POST['id_campana']));
			$campana=$campana[0];
			$udn=$_AM['udns']->getByAndCond(array('id_udn'=>$campana->id_udn));
			$udn=$udn[0];
			
			if(!Auth::hasEmpresa($udn->id_empresa))
				throw new Exception('No tiene permiso para subir datos en esta empresa');
			
			$proceso=$_AM['proceso']->getByAndCond(array('id_proceso'=>$_POST['id_proceso']));
			$proceso=$proceso[0];
			
			if($_GET['step2']=='1' || $_GET['step2']=='') {
				$SM->carga_process=$_POST;
			}
			
			$empresa=$_AM['empresa']->getById($udn->id_empresa);
			

			require $carga_handler;
			$class=str_replace('.class.php','',basename($carga_handler));
			$clazz=new $class();
			$data=array(
				'_T'=>&$_T,
				'id_udn'=>$_POST['id_udn'],
				'id_campana'=>$_POST['id_campana'],
				'id_proceso'=>$_POST['id_proceso'],
				'udn'=>$udn,
				'campana'=>$campana,
				'proceso'=>$proceso,
			);
			$ret=$clazz->execute(($_GET['step2']==''?'1':$_GET['step2']),$data);
			if(!is_null($ret)) {
				/*
				$parents=class_parents($ret);
				if(!in_array('CargaModelo_Uploadable_Abstract',$parents))
					throw new Exception('El handler de subidas debe devolver una clase que extienda "CargaModelo_Uploadable_Abstract"');
				*/
				$implements=class_implements($ret);
				if(!in_array('CargaModelo_Uploadable_Interface',$implements))
					throw new Exception('El handler de subidas debe devolver una clase que implemente "CargaModelo_Uploadable_Interface" y "Iterator" (No está implementando CargaModelo_Uploadable_Interface)');
				if(!in_array('Iterator',$implements))
					throw new Exception('El handler de subidas debe devolver una clase que implemente "CargaModelo_Uploadable_Interface" y "Iterator" (No está implementando Iterator)');

				
				
				foreach($ret->getFiles() as $f) {
					if(!is_readable($f['filepath']))
						throw new Exception('El archivo "'.$f['filepath'].'" no se puede leer');
				}
				
				if($_GET['__upload']!='1') {
					// FASE DE ANALISIS
					// preparamos statements
					$db->prepare('cuenta_existe','SELECT id_cuenta,valor_original,valor_actual FROM cuentas.cuenta WHERE cuenta=$1 AND id_proceso='.$db->escape($_POST['id_proceso']));
					$db->prepare('persona_existe','SELECT id_persona FROM personas.persona WHERE tipo_identificacion=$1 AND identificacion=$2 AND id_proceso=$3');
					$db->prepare('contacto_existe','SELECT id_medio_contacto FROM medios_contacto.medio_contacto WHERE tipo_medio=$1 AND id_persona=$2');
					$db->prepare('get_deudor','SELECT * FROM personas.persona WHERE id_persona=(SELECT id_persona FROM cuentas.cuenta_responsable WHERE id_cuenta=$1 AND tipo_responsable=\'DEUDOR\')');
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
				}else{
					$timer=microtime(true);
					//$BM->mark('inicio');
					// PROCESO NORMAL DE CARGA
					$db->startTransaction();

					// creamos la carga
					$carga=$_AM['carga']->insert(
						array(
							'id_proceso'=>$db->escape($_POST['id_proceso']),
							'descripcion'=>$db->escape($_POST['descripcion_carga']),
							'fecha_carga'=>'NOW()',
							'usuario'=>Auth::getUser(),
							'status'=>'1'
						)
					);
					
					
					
					// persona
					// {in_id_carga,in_id_proceso,in_tipo_identificacion,in_identificacion,in_primer_nombre,in_segundo_nombre,in_primer_apellido,in_segundo_apellido}
					$db->prepare('persona','SELECT cargas.carga_new_persona('.$carga->id_carga.','.$proceso->id_proceso.',$1::personas.enum_tipo_identificacion,$2,$3,$4,$5,$6) AS id_persona');
					
					// medios_contacto
					// {in_id_carga INT,in_id_persona INT,in_tipo_medio medios_contacto.enum_tipo_medio_contacto,in_contenido TEXT}
					$db->prepare('medio_contacto','SELECT cargas.new_medio_contacto('.$carga->id_carga.',$1,$2::medios_contacto.enum_tipo_medio_contacto,$3) AS id_medio_contacto');
					
					// carga_new_cuenta
					// {in_id_carga,in_id_proceso,in_cuenta,in_id_responsable}
					$db->prepare('cuenta','SELECT cargas.carga_new_cuenta('.$carga->id_carga.','.$carga->id_proceso.',$1,$2) AS id_cuenta');
					
					// carga_cuenta_set_valor_original
					// {in_id_carga,in_id_cuenta,in_valor_orignal}
					$db->prepare('cuenta_set_valor','SELECT cargas.carga_cuenta_set_valor_original('.$carga->id_carga.',$1,$2)');
					
					// cuenta_set_valor_actual
					$db->prepare('cuenta_set_valor_actual','UPDATE cuentas.cuenta SET valor_actual=$2,fecha_valor_actual=NOW(),id_carga_valor_actual='.$carga->id_carga.' WHERE id_cuenta=$1');
					
					// carga_add_cuenta_actualizacion
					// {in_id_carga,in_id_proceso,in_id_cuenta,in_diferencia,in_tipo_actualizacion,in_fecha_actualizacion}
					$db->prepare('carga_add_cuenta_actualizacion','SELECT cargas.carga_add_cuenta_actualizacion('.$carga->id_carga.','.$carga->id_proceso.',$1,$2,$3::cuentas.enum_tipo_actualizacion,$4::timestamp without time zone,$5)');
					
					// carga_add_telefono
					// {in_id_carga,in_id_persona,in_tipo_telefono,in_telefono}
					$db->prepare('carga_add_telefono','SELECT cargas.carga_add_telefono('.$carga->id_carga.',$1,$2::"medios_contacto"."enum_tipo_telefono",$3,$4)');

					// carga_set_cuenta_deudor
					// {in_id_carga,in_id_proceso,in_id_cuenta,in_id_persona}
					$db->prepare('carga_set_cuenta_deudor','SELECT cargas.carga_set_cuenta_deudor('.$carga->id_carga.','.$carga->id_proceso.',$1,$2)');
					
					// carga_set_responsables
					// {in_id_carga INT,in_id_proceso INT,in_id_cuenta INT,in_ids_personas carga_add_responsable_in_tuple[]}
					//	Composite type "cargas.carga_add_responsable_in_tuple"
					//		  Column      |  Type   | Modifiers
					//	------------------+---------+-----------
					//	 id_persona       | integer |
					//	 tipo_responsable | text    |
					$db->prepare('carga_set_responsables','SELECT * FROM cargas.carga_set_responsables('.$carga->id_carga.','.$carga->id_proceso.',$1,$2::cargas.carga_add_responsable_in_tuple[])');
					
					$db->prepare('empty_cuenta_responsables','DELETE FROM cuentas.cuenta_responsable WHERE id_cuenta=$1 AND tipo_responsable<>\'DEUDOR\'');
					
					// otros datos
					$db->prepare('otros_datos','INSERT INTO cargas.carga_no_mapeada (id_carga,id_cuenta,campo,valor) VALUES ('.$carga->id_carga.',$1,$2,$3)');
					$consolidado_cuentas=array();
					foreach($ret as $nRec=>$rec) {
						if(is_null($rec)) continue;
						$consolidado_cuentas[]=$rec['cuenta']->numero_cuenta;
						//$BM->mark('record');
						// CREACION DE PERSONAS
						// persona responsable
						if(is_null($rec['cuenta']->persona_responsable))
							throw new Exception('La cuenta '.$rec['cuenta']->numero_cuenta.' no tiene persona responsable!');
						$BM->mark('persona_responsable_persona');
						$_carga['ids']['persona_responsable']=$db->execute('persona',array(
							$rec['cuenta']->persona_responsable->tipo_identificacion,
							$rec['cuenta']->persona_responsable->identificacion,
							$rec['cuenta']->persona_responsable->primer_nombre,
							$rec['cuenta']->persona_responsable->segundo_nombre,
							$rec['cuenta']->persona_responsable->primer_apellido,
							$rec['cuenta']->persona_responsable->segundo_apellido,
						))->current();
						$BM->mark('persona_responsable_persona');
						
						// medio contacto persona responsable
						foreach($rec['cuenta']->persona_responsable->medios_contacto as $mc) {
							$BM->mark('persona_responsable_medio_contacto');
							$_carga['ids']['persona_responsable']['medios_contacto'][]=$db->execute('medio_contacto',
								array(
									$_carga['ids']['persona_responsable']['id_persona'],
									$mc->tipo,
									$mc->contenido
								)
							)->current()['id_medio_contacto'];
							$BM->mark('persona_responsable_medio_contacto');
						}
						
						// telefono persona responsable
						foreach($rec['cuenta']->persona_responsable->telefonos as $telefono) {
							$BM->mark('persona_responsable_telefono');
							$_carga['ids']['persona_responsable']['telefonos'][]=$db->execute('carga_add_telefono',array(
								$_carga['ids']['persona_responsable']['id_persona'],
								$telefono->tipo,
								$telefono->numero,
								$telefono->origen
							))->current()['carga_add_telefono'];
							$BM->mark('persona_responsable_telefono');
						}
												
						
						// personas adicionales
						foreach($rec['cuenta']->otras_personas as $persona) {
							$BM->mark('persona_adicional_persona');
							// personas adicionales - persona
							$aux=array();
							$aux['id_persona']=$db->execute('persona',array(
								$persona['persona']->tipo_identificacion,
								$persona['persona']->identificacion,
								$persona['persona']->primer_nombre,
								$persona['persona']->segundo_nombre,
								$persona['persona']->primer_apellido,
								$persona['persona']->segundo_apellido,
							))->current()['id_persona'];
							$BM->mark('persona_adicional_persona');
							
						
							
							// personas adicionales - medio contacto
							foreach($persona['persona']->medios_contacto as $mc) {
								$BM->mark('persona_adicional_medio_contacto');
								$aux['medios_contacto'][]=$db->execute('medio_contacto',
									array(
										$aux['id_persona'],
										$mc->tipo,
										$mc->contenido
									)
								)->current()['id_medio_contacto'];
								$BM->mark('persona_adicional_medio_contacto');
							}
							

							
							// personas adicionales - telefonos
							foreach($persona['persona']->telefonos as $tel) {
								$BM->mark('persona_adicional_telefono');
								$aux['telefonos'][]=$db->execute('carga_add_telefono',array(
									$aux['id_persona'],
									$tel->tipo,
									$tel->numero,
									$tel->origen
								))->current()['carga_add_telefono'];
								$BM->mark('persona_adicional_telefono');
							}
							$aux['tipo']=$persona['tipo'];
							$_carga['ids']['otras_personas'][]=$aux;
						}
							
						
						// creamos la cuenta
						// cuenta
						$BM->mark('cuenta_create');
						$_carga['ids']['cuenta']['id']=$db->execute('cuenta',array(
							$rec['cuenta']->numero_cuenta,
							$_carga['ids']['persona_responsable']['id_persona']
						))->current();
						$_carga['ids']['cuenta']['id']=strtr($_carga['ids']['cuenta']['id']['id_cuenta'],array('('=>'',')'=>''));
						// separamos valores retornados de <id_cuenta,is_new>
						$_carga['ids']['cuenta']['id']=explode(',',$_carga['ids']['cuenta']['id']);
						// convertimos a booleano el is_new
						$_carga['ids']['cuenta']['is_new']=($_carga['ids']['cuenta']['id'][1]==1);
						// asignamos id_cuenta
						$_carga['ids']['cuenta']['id']=$_carga['ids']['cuenta']['id'][0];
						$BM->mark('cuenta_create');

						// traemos la cuenta
						$BM->mark('cuenta_get_by_id');
						$_carga['cuenta']=$_AM['cuenta']->getById($_carga['ids']['cuenta']['id']);
						$BM->mark('cuenta_get_by_id');

						// seteamos a la persona responsable
						$BM->mark('cuenta_persona_responsable');
						$db->execute('carga_set_cuenta_deudor',array(
							$_carga['ids']['cuenta']['id'],
							$_carga['ids']['persona_responsable']['id_persona']
						));
						$BM->mark('cuenta_persona_responsable');
						
						
						// almacenamos las actualizaciones
						$diff_acum=0;
						foreach($rec['cuenta']->actualizaciones as $actualizacion) {
							$BM->mark('cuenta_actualizacion');
							$db->execute('carga_add_cuenta_actualizacion',array(
								$_carga['ids']['cuenta']['id'],
								$actualizacion->valor,
								$actualizacion->tipo_actualizacion,
								$actualizacion->fecha_actualizacion.' '.$actualizacion->hora_actualizacion,
								'Actualizacion de cartera. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso
							));
							$diff_acum+=$actualizacion->valor;
							$BM->mark('cuenta_actualizacion');
						}
						
						
						if(is_null($_carga['cuenta']->valor_original) || $_carga['ids']['cuenta']['is_new']) {
							$BM->mark('cuenta_set_valor_original');
							// si la cuenta es nueva, se forza el valor actual a lo que viene en el archivo
							$db->execute('cuenta_set_valor',array($_carga['ids']['cuenta']['id'],$rec['cuenta']->valor_actual));
							$db->execute('cuenta_set_valor_actual',array($_carga['ids']['cuenta']['id'],($rec['cuenta']->valor_actual+$diff_acum)));
							$BM->mark('cuenta_set_valor_original');
						}else{
							// la cuenta no es nueva
							if($rec['cuenta']->valor_actual != $_carga['cuenta']->valor_actual) {
								// si no coincide valor actual en base con valor actual en archivo
								$va_luego_de_actualizaciones=$_carga['cuenta']->valor_actual+$diff_acum;
								if($va_luego_de_actualizaciones != $rec['cuenta']->valor_actual) {
									// valor actual luego de actualizaciones sigue sin coincidir con lo que esta en la base de datos
									$diff=$rec['cuenta']->valor_actual-$va_luego_de_actualizaciones;
									// se debe agregar una correccion para poder hacer que todo coincida
									$db->execute('carga_add_cuenta_actualizacion',array(
										$_carga['ids']['cuenta']['id'],
										$diff,
										'CORRECCION',
										date('Y-m-d h:i:s'),
										'Valor actual no coincide con archivo subido luego de actualizaciones. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso

									));						
								}
							}else{
								// el valor actual en base coincide con lo que vino en el archivo
								if(count($rec['cuenta']->actualizaciones)>0) {
									// si es que hay actualizaciones, de ley hay que crear la correccion
									$db->execute('carga_add_cuenta_actualizacion',array(
										$_carga['ids']['cuenta']['id'],
										$diff_acum*(-1),
										'CORRECCION',
										date('Y-m-d h:i:s'),
										'Valor actual coincide con lo que hay en archivo. Pero hay actualizaciones. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso

									));						
								}
							}
						}
						
						
						// se setean las personas relacionadas
						// {"(8,\"GARANTE\")","(9,\"AYUDANTE\")","(10,\"CONTACTO\")"}
						$BM->mark('cuenta_otras_personas');
						$aux=array();
						foreach($_carga['ids']['otras_personas'] as $persona) {
							$aux[]='"('.$persona['id_persona'].',\\"'.$persona['tipo'].'\\")"';
						}
						
						if(!empty($aux)) {
							$db->execute('carga_set_responsables',array(
								$_carga['ids']['cuenta']['id'],
								'{'.implode(',',$aux).'}'
							));
						}else{
							// no hay responsables adicionales en el archivo
							// se limpian de la base
							$db->execute('empty_cuenta_responsables',array($_carga['ids']['cuenta']['id']));
						}
						$BM->mark('cuenta_otras_personas');
						
						// se almacenan los "otros datos"
						$BM->mark('otros_datos');
						/*
						foreach($rec['otros_datos'] as $k=>$v) {
							$db->execute('otros_datos',array(
								$_carga['ids']['cuenta']['id'],
								$k,
								$v
							));
						}
						*/
						if(!empty($rec['otros_datos'])) {
							$aux=array();
							$query='INSERT INTO cargas.carga_no_mapeada (id_carga,id_cuenta,campo,valor) VALUES ';
							foreach($rec['otros_datos'] as $k=>$v) {
								$aux[]=implode(',',array(
									$carga->id_carga,
									$_carga['ids']['cuenta']['id'],
									'\''.$db->escape($k).'\'',
									'\''.$db->escape($v).'\'',
								));
							}
							$query.='('.implode('),(',$aux).')';
							$db->query($query);
						}
						$BM->mark('otros_datos');
						//$BM->mark('record');
					}
					
					// obtenemos el listado de las cuentas que no vinieron en el archivo
					// evaluamos cuales cuentas no llegaron en el archivo
					$cuentas_en_db_no_en_archivo=get_cuentas_en_db_no_en_archivo($consolidado_cuentas,$proceso->id_proceso);
					if(!empty($cuentas_en_db_no_en_archivo)) {
						foreach($cuentas_en_db_no_en_archivo as $c) {
							// se aplica la correccion *(-1) del valor actual para encerar
							$db->execute('carga_add_cuenta_actualizacion',array(
								$_carga['ids']['cuenta']['id'],
								$c['valor_actual']*(-1),
								'CORRECCION',
								date('Y-m-d h:i:s'),
								'Cuenta no vino en el archivo de carga. Carga: '.$carga->id_carga.' Proceso: '.$proceso->id_proceso

							));								
						}
					}	
					
					// ya se cargaron todos los datos
					// procedemos a almacenar el archivo completo original
					foreach($ret->getFiles() as $file) {
						$data=file_get_contents($file['filepath']);
						if($data===false)
							throw new Exception('Error al leer "'.$file['filepath'].'"');
						$row=array(
							'id_carga'=>$carga->id_carga,
							'nombre_archivo'=>'\''.$db->escape($file['filename']).'\'',
							'md5'=>'\''.md5($data).'\'',
							'raw_data'=>null,
							'original_size'=>strlen($data),
							'compressed_size'=>null,
							'md5_compressed'=>null,
							'tipo'=>'\'DATA\''
						);
						$data=gzcompress($data,9);
						$row['raw_data']='\''.$db->escape_bytea($data).'\'';
						$row['compressed_size']=strlen($data);
						$row['md5_compressed']='\''.md5($data).'\'';
						
						
						$query='INSERT INTO cargas.carga_data ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')';
						$db->query($query);
					}
					
					$BM->mark('raw_files');
					// cargamos el procesamiento
					$uid=decrypt($_POST['details_uid']);
					$dhdl=opendir(_TMP_UPLOAD_FOLDER);
					if(!$dhdl) throw new Exception('Error al abrir tmp folder');
					$list=array();
					while($ptr=readdir($dhdl)) {
						if($ptr=='.' || $ptr=='..') continue;
						$aux=explode('_',$ptr);
						if($aux[0]==$uid) $list[]=$ptr;
					}
					$tempfile=tempnam('/tmp','zip');
					$zip=new ZipArchive();
					$zip->open($tempfile,ZipArchive::CREATE);
					foreach($list as $l) {
						$aux=explode('_',$l);
						unset($aux[0]);
						$zip->addFile(_TMP_UPLOAD_FOLDER.'/'.$l,implode('_',$aux));
					}
					$zip->close();
					foreach($list as $l) {
						unlink(_TMP_UPLOAD_FOLDER.'/'.$l,implode('_',$aux));
					}

					$data=file_get_contents($tempfile);
					$row=array(
						'id_carga'=>$carga->id_carga,
						'nombre_archivo'=>'\'detalles.zip\'',
						'md5'=>'\''.md5($data).'\'',
						'raw_data'=>null,
						'original_size'=>strlen($data),
						'compressed_size'=>null,
						'md5_compressed'=>null,
						'tipo'=>'\'DETALLES\''
					);
					$data=gzcompress($data,9);
					$row['raw_data']='\''.$db->escape_bytea($data).'\'';
					$row['compressed_size']=strlen($data);
					$row['md5_compressed']='\''.md5($data).'\'';					
					$query='INSERT INTO cargas.carga_data ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')';
					$db->query($query);
					
					//$db->rollback();
					$db->commit();
					$BM->mark('raw_files');
					//$BM->mark('inicio');
					$_T['maincontent']='<h1 style="color: green;"> Los datos han sido almacenados satisfactoriamente</h1><br>Tomé '.((microtime(true)-$timer)/60).' minutos';
					$_T['maincontent'].='<hr>'.$BM->resume();
				}
			}
			
			
		}catch(Exception $e) {
			$_T['maincontent']='<h2 style="color: maroon; font-weight: bold;">ERROR<br>'.$e->getMessage().'</h2>';
		}
		
		
	break;

    
    default:
        lbl_default:
        $_T['maintitle']='Bases de Datos';
        $_T['maincontent'].='
        <script>
        function updateGUICampanas() {
            var ctl_udn=$("#id_udn");
            var ctl_camp=$("#id_campana");
            
            ctl_camp.html("<option value=\'\'>Seleccione...</option>");
            
            if(ctl_udn.val()=="") return;
            
            $.ajax({
                "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getCampanas')).'",
                "method":"POST",
                "data": { 
                    "id_udn":ctl_udn.val()
                },
                "success":function(d) {
                    try {
                        d=$.parseJSON(d);
                        if(!d.success) throw d.data;
                        var html=new Array("<option value=\'\'>Seleccione...</option>");
                        for(var i in d.data) {
                            var ptr=d.data[i];
                            html.push("<option value=\'"+ptr.id_campana+"\'>"+ptr.id_campana+" - "+ptr.campana+"</option>");
                        }
                        ctl_camp.html(html.join(""));
                    }catch(err) {
                        alert(err);
                    }
                }
            });
        }
        
        function updateGUIprocesos() {
            var ctl_udn=$("#id_udn");
            var ctl_camp=$("#id_campana");
            var ctl_tipocarga=$("#id_tipocarga");
            var ctl_proceso=$("#id_proceso");
            
            ctl_tipocarga.html("<option value=\'\'>Seleccione...</option>");
            ctl_proceso.html("<option value=\'\'>Seleccione...</option>");
            
            if(ctl_udn.val()=="") return;
            if(ctl_camp.val()=="") return;            
            $.ajax({
                "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getProcesos')).'",
                "method":"POST",
                "data": { 
                    "id_udn":ctl_udn.val(),
                    "id_camp":ctl_camp.val()
                },
                "success":function(d) {
                    try {
                        d=$.parseJSON(d);
                        if(!d.success) throw d.data;
                        var html=new Array("<option value=\'\'>Seleccione...</option>");
                        for(var i in d.data) {
                            var ptr=d.data[i];
                            html.push("<option value=\'"+ptr.id_proceso+"\'>"+ptr.descripcion+"</option>");
                        }
                        ctl_proceso.html(html.join(""));
						updateGUIcargas();
                    }catch(err) {
                        alert(err);
                    }
                }
            });
        }
        
        function updateGUIcargas() {
            var ctl_udn=$("#id_udn");
            var ctl_camp=$("#id_campana");
            var ctl_tipocarga=$("#id_tipocarga");
            
            ctl_tipocarga.html("<option value=\'\'>Seleccione...</option>");
            
            if(ctl_udn.val()=="") return;
            if(ctl_camp.val()=="") return;            
            $.ajax({
                "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getCargasHandler')).'",
                "method":"POST",
                "data": { 
                    "id_udn":ctl_udn.val(),
                    "id_camp":ctl_camp.val()
                },
                "success":function(d) {
                    try {
                        d=$.parseJSON(d);
                        if(!d.success) throw d.data;
                        var html=new Array("<option value=\'\'>Seleccione...</option>");
                        for(var i in d.data) {
                            var ptr=d.data[i];
							console.log(ptr);
                            html.push("<option value=\'"+ptr.fname+"\'>"+ptr.tipo+"</option>");
                        }
                        ctl_tipocarga.html(html.join(""));
                    }catch(err) {
                        alert(err);
                    }
                }
            });
        }
            
        </script>
        
        <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2')).'" id="theForm" enctype="multipart/form-data">
		<input type="hidden" name="metadata" id="config_hidden_target">
        <b>Seleccione UDN:</b>
        <select class="form-control" id="id_udn" name="id_udn" onchange="updateGUICampanas()">
        <option value="">Seleccione...</option>
        ';
		
        $aux=array();
        foreach(
            $db->query('SELECT u.id_udn,u.udn,e.nombre AS nombre_empresa,id_empresa FROM estructura.udn u JOIN estructura.empresa e USING (id_empresa) ORDER BY e.nombre ASC,u.udn ASC') as $udn
        ) {
			if(!Auth::hasEmpresa($udn['id_empresa'])) continue;
            $aux[$udn['nombre_empresa']][]=$udn;
        }
        foreach($aux as $k=>$v) {
            $_T['maincontent'].='<optgroup label="'.$k.'">'; 
            foreach($v as $vv) {
                $_T['maincontent'].='<option value="'.$vv['id_udn'].'">'.$vv['id_udn'].' - '.$vv['udn'].'</option>'; 
            }
            $_T['maincontent'].='</optgroup>'; 
        }
		
        
        $_T['maincontent'].='
        </select>
        
        <br>
        <b>Seleccione Campana:</b>
        <select class="form-control" id="id_campana" name="id_campana" onchange="updateGUIprocesos()">
        <option value="">Seleccione...</option>
        </select>
        <br>
        <b>Seleccione Proceso:</b>
        <select class="form-control" id="id_proceso" name="id_proceso">
        <option value="">Seleccione...</option>
        </select>
        <br>
        <b>Seleccione Tipo Carga:</b>
        <select class="form-control" id="id_tipocarga" name="carga_handler">
        <option value="">Seleccione...</option>
        </select>

        <br>
        <b>Descripción para la Carga:</b>
        <input type="text" name="descripcion_carga" class="form-control" id="id_descripcion_carga" name="descripcion_carga">

    	<br>
		<b>Metadata:</b>
		';
		
		$select=new UIComponents_ConfigSelector();
        $aux=array();
        foreach($_AM['metadata_usable']->getByAndCond(array('aplicable_a'=>'subida')) as $r) {
            $aux[]=$r->toArray();
        }
        $select->source_data=$aux;
        $select->form_id='theForm';
        $select->hidden_target='config_hidden_target';
        $select->value=$_POST['metadata'];
        
        $_T['maincontent'].=$select->draw();
		
		$_T['maincontent'].='
        <button class="btn btn-primary">Siguiente</button>
        </form>
        ';
        
        
    break;
}