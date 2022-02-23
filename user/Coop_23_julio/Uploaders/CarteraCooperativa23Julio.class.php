<?php

class Cargador_CarteraCooperativa23Julio extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $header;
	private $data;

	private $catalogos = array();
	private $ptr=0;

	function __construct($fpath_files) {

		$dir = _TMP_UPLOAD_FOLDER;
		$files = array();
		// echo 'inicio leyendo archivos '.date('H:i:s');

		/*
		CREDITOS.txt
		CUOTAS.txt
		DEUDORES.txt
		DIRECCIONES.txt
		GARANTES.txt
		REFERENCIAS.txt
		TELEFONOS.txt
		*/

		// print_arr($fpath_files);
		// die();
		$cuentas = array();
		$op_ced = array();
		foreach ($fpath_files as $name => $file){

			$archivo = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$file);
			
			$new_fname = explode('.',$name);
			$new_fname = explode('_',$new_fname[0])[0];
			
			switch ($new_fname){
				case 'CREDITOS':
					foreach($archivo as $num_linea => $linea){
						foreach ($linea as $k => &$v){
							$v = trim($v);
							unset($v);
						}
						// verifica si existen cuentas duplicadas
						if (array_key_exists($linea['operacion'],$cuentas)){
							throw new exception('Operacion duplicada "'.$linea['operacion'].'" en linea: '.$num_linea);
						}
						$cuentas[$linea['operacion']]['credito'] = $linea;
						$op_ced[$linea['operacion']] = $linea['Identificacion'];
					}
				break;
				case 'CUOTAS':
					foreach($archivo as $num_linea => $linea){
						foreach ($linea as $k => &$v){
							$v = trim($v);
							unset($v);
						}
						if (array_key_exists($linea['Operacion'],$cuentas))
							$cuentas[$linea['Operacion']]['cuotas'][] = $linea;
					}
				break;
				case 'DEUDORES':
					foreach($archivo as $num_linea => $linea){
						foreach ($linea as $k => &$v){
							$v = trim($v);
							unset($v);
						}
						$num_operacion = array_search($linea['Numero identificacion'],$op_ced);
						if ($num_operacion!==false){
							$cuentas[$num_operacion]['deudores'][] = $linea;
						}
					}
				break;
				case 'DIRECCIONES':
					foreach($archivo as $num_linea => $linea){
						foreach ($linea as $k => &$v){
							$v = trim($v);
							unset($v);
						}
						if (array_key_exists($linea['operacion'],$cuentas))
							$cuentas[$linea['operacion']]['direciones'][] = $linea;
					}
				break;
				case 'GARANTES':
					foreach($archivo as $num_linea => $linea){
						foreach ($linea as $k => &$v){
							$v = trim($v);
							unset($v);
						}
						if (array_key_exists($linea['operacion'],$cuentas))
							$cuentas[$linea['operacion']]['garantes'][] = $linea;
					}
				break;
				case 'REFERENCIAS':
					foreach($archivo as $num_linea => $linea){
						foreach ($linea as $k => &$v){
							$v = trim($v);
							unset($v);
						}
						if (array_key_exists($linea['operacion'],$cuentas))
							$cuentas[$linea['operacion']]['referencias'][] = $linea;
					}
				break;
				case 'TELEFONOS':
					foreach($archivo as $num_linea => $linea){
						foreach ($linea as $k => &$v){
							$v = trim($v);
							unset($v);
						}
						if (array_key_exists($linea['operacion'],$cuentas))
							$cuentas[$linea['operacion']]['telefonos'][] = $linea;
					}
				break;
			}
		}

		// echo 'Fin Armando Data '.date('H:i:s');
		$this->data = $cuentas;
		$this->setTipoCarga('cartera');

	}
	
	function processRecord(&$line) {
		$num_linea = $this->ptr+1;
		print_arr($line);
		die();
		if ($line['credito']['operacion']=='') throw new Exception('No existe "operacion" en línea: '.$num_linea);
		if ($line['credito']['Identificacion']=='') throw new Exception('No existe "identificación" para la operacion : '.$$line['credito']['operacion']);
		if ($line['credito']['Monto Deuda']=='') throw new Exception('No existe "Monto deuda" para la opertacion: '.$line['credito']['operacion']);
		if ($line['credito']['Total Pagar']=='') throw new Exception('No existe "Total Pagar" para la operacion: '.$line['credito']['operacion']);

		if (!is_numeric($line['credito']['Monto Deuda'])) throw new Exception('"Monto deuda" debe ser número, para la opertacion: '.$line['credito']['operacion']);
		if (!is_numeric($line['credito']['Total Pagar'])) throw new Exception('"Total Pagar" debe ser número para la operacion: '.$line['credito']['operacion']);

		$ret = array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);

		$cuenta = new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta = $line['credito']['operacion'];
		$cuenta->valor_actual = round(str_replace(array(' ',','),'',$line['credito']['Monto Deuda']),2);
//  HASTA AQUI


		$total_a_pagar = round(str_replace(array(' ',','),'',$line['credito']['Total Pagar']),2);
		
		// ajustamos para que vaya a monto a pagar
		$total_pagos=0.0;
		foreach ($line['credito']['_pagos'] as $p){
			$total_pagos+=round(str_replace(",","",$p['Total']),2);
		}
		
		if($cuenta->valor_actual > ($total_a_pagar+$total_pagos)) {
			$cuenta->add_actualizacion('AJUSTE-',-1*abs($cuenta->valor_actual-($total_a_pagar+$total_pagos)),date('Y-m-d'));
		} else if($cuenta->valor_actual < $total_a_pagar+$total_pagos) {
			$cuenta->add_actualizacion('AJUSTE+',abs($cuenta->valor_actual-($total_a_pagar+$total_pagos)),date('Y-m-d'));
		}
			
		// PAGOS
		foreach ($line['credito']['_pagos'] as $p){
			$pago = round(str_replace(",","",$p['Total']),2);
			$cuenta->add_actualizacion('PAGO',$pago*-1,$p['Fecha Pago']);
		}

		// persona responsable
		$cuenta->persona_responsable = new CargaModelo_Item_Persona();
		
		$tipo_id = 'CEDULA';
		if ($line['cliente']['TIPODOCUMENTO']!='C') $tipo_id = 'OTRO';
		$cuenta->persona_responsable->tipo_identificacion = $tipo_id;
		$cuenta->persona_responsable->identificacion=$line['cliente']['Numero Identificacion'];
		$cuenta->persona_responsable->primer_nombre=$line['cliente']['Nombre Completo'];
		// telefonos deudor
		foreach ($line['cliente']['_telefonos'] as $t){
			foreach (Helpers::parseTelefonos($t['Numero de telefono']) as $t){
				$cuenta->persona_responsable->add_medio_contacto('TELEFONO',$t);
			}
		}
		// referencias
		foreach ($line['cliente']['_referencias'] as $ref) {
			$referencia = new CargaModelo_Item_Persona();
			$referencia->tipo_identificacion = 'CEDULA';
			$referencia->identificacion = $ref['Numero Identificacion Ref'];
			$referencia->primer_nombre = $ref['Nombre Completo'];
			// telefonos referencias
			foreach(Helpers::parseTelefonos($ref['Telefono']) as $t){
				$referencia->add_medio_contacto('TELEFONO',$t);
			}
			$cuenta->pushOtraPersona($referencia,$ref['Tipo Referencia']);
		}
		
		$ret['cuenta']=$cuenta;

		$ret['otros_datos']['Agencia']=$line['cliente']['Agencia'];
		$ret['otros_datos']['Plaza']=$line['cliente']['Plaza'];
		$ret['otros_datos']['Provincia']=$line['cliente']['Provincia'];
		$ret['otros_datos']['sexo']=$line['cliente']['sexo'];
		$ret['otros_datos']['Estado civil']=$line['cliente']['Estado civil'];
		$ret['otros_datos']['profesion']=$line['cliente']['profesion'];

		$ret['otros_datos']['Numero cuota mas vencido']=$line['credito']['Numero cuota mas vencido'];
		$ret['otros_datos']['Numero cuotas vencidas']=$line['credito']['Numero cuotas vencidas'];
		$ret['otros_datos']['Tasa credito']=$line['credito']['Tasa credito'];
		$ret['otros_datos']['Fecha creacion']=$line['credito']['Fecha creacion'];
		$ret['otros_datos']['Tipo credito']=$line['credito']['Tipo credito'];
		$ret['otros_datos']['Fecha Vcto.']=$line['credito']['Fecha Vcto.'];
		$ret['otros_datos']['Estado de la operacion']=$line['credito']['Estado de la operacion'];
		$ret['otros_datos']['Descripcion producto']=$line['credito']['Descripcion producto'];
		$ret['otros_datos']['Nombre agencia']=$line['credito']['Nombre agencia'];
		$ret['otros_datos']['Plazo']=$line['credito']['Plazo'];
		$ret['otros_datos']['Coactiva']=$line['credito']['_coactivas'];
		$ret['otros_datos']['Cao']=$line['credito']['_cao']['CAO'];
		// cuotas
		usort($line['credito']['_cuotas'],function ($a, $b) {
			return $a['Numero cuota'] > $b['Numero cuota'];
		});
		foreach ($line['credito']['_cuotas'] as $c){
			$ret['otros_datos']['cuota_numero_'.$c['Numero cuota']]=$c['Numero cuota'];
			$ret['otros_datos']['cuota_capital_'.$c['Numero cuota']]=$c['Capital cuota'];
			$ret['otros_datos']['cuota_fecha_vencimiento_'.$c['Numero cuota']]=$c['Fecha Vcto.'];
		}
		return $ret;
	}

	// Iterator

	function rewind() {
		$this->ptr = 0;
		$this->keys = array_keys($this->data);
		$this->keysCount = count($this->data);
	}

	function next() {
		$this->ptr++;
	}

	function current() {
        return $this->processRecord($this->data[$this->keys[$this->ptr]]);
	}

	function key() {
        return $this->keys[$this->ptr];
	}

	function valid() {
		return ($this->ptr < $this->keysCount);
	}

}

class CarteraCooperativa23Julio extends CargaModelo_Handler_Abstract {

	function getTipoBase() {
		return 'Cartera';
	}
	
	function getDescripcion() {
		return 'Cartera';
	}
	
	function execute($step, &$__data) {
		if ($_SERVER['REMOTE_ADDR']!='10.1.217.4') die('Cargadoren construcción');
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader = new Cargador_CarteraCooperativa23Julio($SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '2':
				$dir = _BASE_USER_PATH.'/Coop_23_julio/Cargas/';
				$__files = explode(',',$_POST['files']);
				$aux = $SM->carga_process;
				foreach ($__files as $f){
					$aux['source_file'][$f] = uniqid();
					$aux['original_filename'][$f] = $f;
					if(!copy($dir.$f,_TMP_UPLOAD_FOLDER.'/'.$aux['source_file'][$f])) throw new Exception('Error al mover archivo subido '.$f);
					//if(!move_uploaded_file($dir.$f,_TMP_UPLOAD_FOLDER.'/'.$aux['source_file'][$f])) throw new Exception('Error al mover archivo subido '.$f);
				}
				$SM->carga_process=$aux;
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
			break;
			
			case '1':
				$dir = _BASE_USER_PATH.'/Coop_23_julio/Cargas/';
				$archivos_requeridos = array(
					'CREDITOS.txt'=> array('Tipo D','Identificacion','operacion','Monto Deuda','Cuotas Vencidas','tasa','Fecha Creacion','Tipo Credito','Fecha Vencimiento','Estado','Total Pagar','Descripcion Producto','Oficina','Plazo','Calificacion','Oficial','Detalle','Saldo Capital','Segmento','Dias mora'),
					'CUOTAS.txt'=> array('Tipo','Identificacion','Operacion','Concepto','Cuotas','Fecha Vencimiento','tasa','SALDO CAP VENCIDO'),
					'DEUDORES.txt'=> array('Tipo documento','Numero identificacion','Nombre completo','Apellidos','Nombres','Agencia','Canton','Provincia','Parroquia','Sexo','Estado civil','Profesion','Actividad economica','Correo'),
					'DIRECCIONES.txt'=> array('tipo','identificacion','Direccion','Secundaria','Sector','Tipo Ref','Parroquia','Canton','Provincia','operacion','nombre'),
					'GARANTES.txt'=> array('tipo','identificacion','nombres','Fijo','Celular','operacion'),
					'REFERENCIAS.txt'=> array('tipo','Identificacion','Nombres','Apellidos','Telefono1','Telefono2','Telefono3','parentesco','operacion'),
					'TELEFONOS.txt'=> array('Tipo D','Identificacion','telefono','tipo_telefono','operacion'),
				);
				$archivos_error = array();
				$archivos_ok = array();
				foreach (scandir($dir) as $tmpfile){
					if (!is_file($dir.$tmpfile)) continue;
					$real_file = $tmpfile;
					$file = explode('.',$tmpfile);
					$ext = $file[1];
					$file = explode('_',$file[0])[0];
					$file = $file.'.'.$ext;
					//verificar si el archivo esta en los permitidos
					if (array_key_exists($file,$archivos_requeridos)){
						//verificar codificacion utf-8 de archivo
						if (!mb_check_encoding(file_get_contents($dir.$real_file),'UTF-8')){
							$archivos_error[$file][]='Archivo debe estar con codificación utf8';
							continue;
						}
						// verificar las columnas del archivo
						$cols = $archivos_requeridos[$file];
						$file_resource = new Helpers_CSV($dir.$real_file);
						$header = $file_resource->getHeader();
						if (count($cols)!=count($header)) {
							$archivos_error[$file][]='Las columnas no coinciden con el formato de la base';
						}
						foreach ($cols as $c){
							if (!in_array($c,$header)){
								$archivos_error[$file][$c]='Falta Columna';
							}
						}
						$archivos_ok[$file] = $real_file;
					}
				}
				// verificar que los archivos existan
				foreach (array_keys(array_diff_key($archivos_requeridos,$archivos_ok)) as $file_faltante){
					$archivos_error[$file_faltante][] = 'NO existe archivo "'.$file_faltante.'"';
				}
				$__data['_T']['maincontent']='
				
				<table>
					<tr>
						<td>
							<img src="user/Coop_23_julio/Uploaders/logo_empresa.jpg" width="150px" height="160px">
						</td>
						<td style="padding-left:30px;">
							<h1>Carga de Cartera</h1>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
								<label>Verificación de archivos:</label><br>';
								foreach ($archivos_requeridos as $file => $campos){
									$__data['_T']['maincontent'].='<img src="/cobranzas/user/Coop_23_julio/Uploaders/'.(in_array($file,array_keys($archivos_error))?'error.png':'ok.png').'" width="21" height="21">  '.$file.'<br>';
									if (in_array($file,array_keys($archivos_error))){
										$__data['_T']['maincontent'].='<ul>';
										foreach ($archivos_error[$file] as $campo => $observacion){
											$__data['_T']['maincontent'].='<li>'.$campo.' - '.$observacion.'</li>';
										}
										$__data['_T']['maincontent'].='</ul>';
									}
								}
								$__data['_T']['maincontent'].='<input type="hidden" name="files" value="'.implode(',',$archivos_ok).'"/>';
								$__data['_T']['maincontent'].='<br><br>
								<button class="btn btn-primary" '.(!empty($archivos_error)?'disabled="disabled"':'').'>Cargar</button>
							</form>
						</td>
					</tr>
				</table>
				';

			break;
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'modelo_creditos.txt' => file_get_contents(dirname(__FILE__).'/modelo_creditos.txt'),
			'modelo_cuotas.txt' => file_get_contents(dirname(__FILE__).'/modelo_cuotas.txt'),
			'modelo_deudores.txt' => file_get_contents(dirname(__FILE__).'/modelo_deudores.txt'),
			'modelo_direcciones.txt' => file_get_contents(dirname(__FILE__).'/modelo_direcciones.txt'),
			'modelo_garantes.txt' => file_get_contents(dirname(__FILE__).'/modelo_garantes.txt'),
			'modelo_referencias.txt' => file_get_contents(dirname(__FILE__).'/modelo_referencias.txt'),
			'modelo_telefonos.txt' => file_get_contents(dirname(__FILE__).'/modelo_telefonos.txt'),
		);
		return $ret;
	}

}