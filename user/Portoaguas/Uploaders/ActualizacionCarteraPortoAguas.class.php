<?php

class CargadorActualizacion_CarteraPortoAguas extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{

	private $data = array();
	// private $pagos = array();
	private $ptr=0;
	private $campos_numericos = array();

	function __construct($fpath_files) {

		$cabecera_requerida = array(
			'IDENTIFICACION',
			'CLIENTE',
			'CUENTA',
			'CATASTRO',
			'CIUDADELA',
			'CALLE1',
			'CALLE2',
			'CORREO_CLIENTE',
			'TLF_CONV',
			'CELULAR',
			'TIPO_CONSUMO',
			'SERVICIO',
			'ESTADO_CONEXION',
			'ESTADO',
			'RECLAMO',
			'NUM_MEDIDOR',
			'FACTURAS_VENCIDAS',
			'OBLIGACIONES_CORRIENTES',
			'OBLIGACIONES_VENCIDAS',
			'DEUDA_PORTOAGUAS',
			'SALDO_CONVENIO',
			'fecha de facturacion',
			'VENCIMIENTO_FACTURA',
			'LATITUD',
			'LONGITUD',
			'fecha_emision_mes',
			'condonacion'
		);

		// $cabecera_pagos = array(
		// 	'REFERENCIA',
		// 	'CUENTA',
		// 	'FECHA DEL PAGO',
		// 	'PAGO'
		// );

		$this->campos_numericos = array('FACTURAS_VENCIDAS','OBLIGACIONES_CORRIENTES','OBLIGACIONES_VENCIDAS','DEUDA_PORTOAGUAS','SALDO_CONVENIO');

		$actualizaciones = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$fpath_files['archivo_actualizacion']);
		// $pagos = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$fpath_files['archivo_pagos']);

		//validar cabecera de actualizaciones
		if (!empty(array_diff($cabecera_requerida,$actualizaciones->getHeader()))) throw new Exception('Cabecera de archivo de actualizaciones incorrecta.');
		//validar cabecera de pagos
		// foreach ($pagos->getHeader() as $c){
		// 	if (!in_array($c,$cabecera_pagos)) throw new Exception('Cabecera de archivo de pagos incorrecta. Debe ser: ['.implode('|',$cabecera_pagos).']');
		// }

		$data = array();
		foreach ($actualizaciones as $num_line => $line) {
			$data[] = $line;
		}
		// $referencias = array();
		// $pagosduplicados = array();
		// foreach ($pagos as $num_linea => $line){
		// 	if (!in_array($line['REFERENCIA'],$referencias)){
		// 		$line['PAGO'] = str_replace(',','.',$line['PAGO']);
		// 		$referencias[]=$line['REFERENCIA'];
		// 		$this->pagos[$line['CUENTA']][] = $line;
		// 	}else{
		// 		$pagosduplicados[]=$line;
		// 	}
		// }
		// if (!empty($pagosduplicados)) throw new Exception ('Existen referencias duplicadas: '.implode(',',$pagosduplicados));

		$this->data = $data;
		$this->setTipoCarga('actualizacion');

	}
	
	function parseTelefonos($telefonos) {
		$ret=array();
		$aux=array();
		if(preg_match_all('#[^\d]#',$telefonos,$matches)) {
			$matches=array_unique($matches[0]);
			foreach($matches as $m) {
				foreach(explode($m,$telefonos) as $t) {
					$t=trim($t);
					if(preg_match('#^\d+$#',$t)) $aux[]=$t;
				}
			}
			$aux=array_unique($aux);
		}else{
			$aux[]=$telefonos;
		}
		foreach($aux as $a) {
			$a=ltrim($a,'0');
			if(strlen($a)==7) $a='4'.$a;
			if(!preg_match('#^9[1-9]\d{7}$#',$a) && !preg_match('#^[2-7]\d{7}$#',$a)) {
				continue;
			}
			if(preg_match('#(.)\1{5}#',$a)) continue;
			$ret[]='0'.$a;
		}
		return $ret;
	}

	function parseCorreos($correos){
		$correos = filter_var($correos,FILTER_VALIDATE_EMAIL);
		if ($correos==false){
			return '';
		}
		return $correos;
	}
	
	function processRecord(&$line) {

		$num_linea = $this->ptr+1;
		$ret = array(
			'cuenta' => null,
			'otros_datos' => array()
		);
		foreach ($line as $campo => &$valor) {
			$valor = str_replace('null','',$valor);
			if (in_array($campo,$this->campos_numericos))
				$valor = str_replace(',','.',$valor);
			$valor = trim($valor);
		}

		if ($line['IDENTIFICACION']=='') throw new Exception('Número de identificación vacía en línea: '. $num_linea);
		if ($line['CUENTA']=='') throw new Exception('Número de cuenta vacía en línea: '. $num_linea);
		if ($line['DEUDA_PORTOAGUAS']=='') throw new Exception('Cuenta "'.$line['CUENTA'].'" no tiene valor a pagar en linea: '. $num_linea);

		$cuenta = new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta = $line['CUENTA'];
		$cuenta->valor_actual = round(str_replace(',','',$line['DEUDA_PORTOAGUAS']),2);

		// PAGOS
		// foreach ($this->pagos[$line['CUENTA']] as $pago){
		// 	$_pago=0.00;
		// 	if (doubleval($pago['PAGO'])>_ZERO_THRESHOLD){
		// 		$_pago = doubleval($pago['PAGO']);
		// 		$fecha_pago = explode(' ',$pago['FECHA DEL PAGO'])[0];
		// 		$cuenta->add_actualizacion('PAGO',abs($_pago)*-1,Helpers::dmy2ymd($fecha_pago));
		// 	}
		// }

		$cuenta->persona_responsable = new CargaModelo_Item_Persona();
		
		$cuenta->persona_responsable->tipo_identificacion = 'CEDULA';
		$cuenta->persona_responsable->identificacion = $line['IDENTIFICACION'];
		$cuenta->persona_responsable->primer_nombre = $line['CLIENTE'];
		// direcciones cliente
		$cuenta->persona_responsable->add_direccion('RESIDENCIA',array('CIUDADELA'=>$line['CIUDADELA'],'CALLE PRINCIPAL'=>$line['CALLE1'],'CALLE SECUNDARIA'=>$line['CALLE2']));
		
		
		// telefonos deudor
		foreach ($this->parseTelefonos($line['TLF_CONV'].','.$line['CELULAR']) as $t) {
			$cuenta->persona_responsable->add_medio_contacto('TELEFONO',$t);
		}

		// correos deudor
		$correo = $this->parseCorreos($line['CORREO_CLIENTE']);
		if ($correo!=''){
			$cuenta->persona_responsable->add_medio_contacto('CORREO',$line['CORREO_CLIENTE']);
		}
		
		$ret['cuenta'] = $cuenta;

		$ret['otros_datos']['CUENTA'] = $line['CUENTA'];
		$ret['otros_datos']['CATASTRO'] = $line['CATASTRO'];
		$ret['otros_datos']['TIPO_CONSUMO'] = $line['TIPO_CONSUMO'];
		$ret['otros_datos']['SERVICIO'] = $line['SERVICIO'];
		$ret['otros_datos']['ESTADO'] = $line['ESTADO'];
		$ret['otros_datos']['RECLAMO'] = $line['RECLAMO'];
		$ret['otros_datos']['NUM_MEDIDOR'] = $line['NUM_MEDIDOR'];
		$ret['otros_datos']['FACTURAS_VENCIDAS'] = $line['FACTURAS_VENCIDAS'];
		$ret['otros_datos']['OBLIGACIONES_CORRIENTES'] = $line['OBLIGACIONES_CORRIENTES'];
		$ret['otros_datos']['OBLIGACIONES_VENCIDAS'] = $line['OBLIGACIONES_VENCIDAS'];
		$ret['otros_datos']['DEUDA_PORTOAGUAS'] = $line['DEUDA_PORTOAGUAS'];
		$ret['otros_datos']['SALDO_CONVENIO'] = $line['SALDO_CONVENIO'];
		$ret['otros_datos']['fecha de facturacion'] = $line['fecha de facturacion'];
		$ret['otros_datos']['VENCIMIENTO_FACTURA'] = $line['VENCIMIENTO_FACTURA'];
		$ret['otros_datos']['fecha_emision_mes'] = $line['fecha_emision_mes'];
		$ret['otros_datos']['CIUDADELA'] = $line['CIUDADELA'];
		$ret['otros_datos']['condonacion'] = $line['condonacion'];

		return $ret;
	}

	// Iterator

	function rewind() {
		$this->ptr = 0;
		$this->keysCount = count($this->data);
	}

	function next() {
		$this->ptr++;
	}

	function current() {
        return $this->processRecord($this->data[$this->ptr]);
	}

	function key() {
        return $this->ptr;
	}

	function valid() {
		return ($this->ptr < $this->keysCount);
	}

}

class ActualizacionCarteraPortoAguas extends CargaModelo_Handler_Abstract {

	function getTipoBase() {
		return 'Cargador de Actualizaciones';
	}
	
	function getDescripcion() {
		return 'Cargador de Actualizaciones';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader = new CargadorActualizacion_CarteraPortoAguas($SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '2':
				// if (count($_FILES)!=2) throw new Exception('Error, deben cargarse 2 archivos');
				$aux = $SM->carga_process;
				foreach($_FILES as $name => $file){
					if ($file['error']!='0') throw new Exception('Error '.$file['error'].' al cargar archivo '.$name);
					$aux['source_file'][$name]=uniqid();
					$aux['original_filename'][$name]=$file['name'];
					if(!move_uploaded_file($file['tmp_name'],_TMP_UPLOAD_FOLDER.'/'.$aux['source_file'][$name]))
						throw new Exception('Error al mover archivo '.$name);
				}
				$SM->carga_process=$aux;
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
				die();
			break;
			
			case '1':

				// if ($_SERVER['REMOTE_ADDR']!='10.0.210.85') die('Cargador en mantenimiento');

				$__data['_T']['maincontent']='
				<table>
					<tr>
						<td>
							<img src="user/Portoaguas/Uploaders/logo_empresa.png" width="80px" height="100px">
						</td>
						<td>
							<h1>Actualización de Cartera - Portoaguas</h1><br>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
								<br><br>
								Seleccione archivo de <b>ACTUALIZACIÓN</b>:
								<input type="file" name="archivo_actualizacion" />
								<!--<br><br>
								Seleccione archivo de <b>PAGOS</b>:
								<input type="file" name="archivo_pagos" />-->
								<br><br>
								<button class="btn btn-primary" '.(!empty($error_file)?'disabled="disabled"':'').'>Cargar</button>
							</form>
						</td>
					</tr>
				</table>';
			break;
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'modelo_cartera_portoagua.txt'=>file_get_contents(dirname(__FILE__).'/modelo_cartera_portoagua.txt'),
			// 'modelo_pagos_portoagua.txt'=>file_get_contents(dirname(__FILE__).'/modelo_pagos_portoagua.txt'),
		);
		if($with_data) {
			$ret['bases_modelo']['modelo_cartera_portoagua.txt']=file_get_contents(dirname(__FILE__).'/modelo_cartera_portoagua.txt');
			// $ret['bases_modelo']['modelo_pagos_portoagua.txt']=file_get_contents(dirname(__FILE__).'/modelo_pagos_portoagua.txt');
		}
		return $ret;
	}

}