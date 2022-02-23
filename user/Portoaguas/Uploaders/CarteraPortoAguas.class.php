<?php

class Cargador_CarteraPortoAguas extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{

	private $data = array();
	private $ptr=0;

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
			'fecha_emision_mes'
		);

		$archivo = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$fpath_files);

		//validar cabecera de archivo
		if (!empty(array_diff($cabecera_requerida,$archivo->getHeader()))) throw new Exception('Cabecera de archivo incorrecta');

		// campos numericos
		$campos_numericos = array('FACTURAS_VENCIDAS','OBLIGACIONES_CORRIENTES','OBLIGACIONES_VENCIDAS','DEUDA_PORTOAGUAS','SALDO_CONVENIO');

		$result = array();
		foreach ($archivo as $num_linea => $linea) {

			// eliminar espacios vacios de la linea y reemplaza , por . en valores float
			foreach ($linea as $campo => &$valor) {
				if (in_array($campo,$campos_numericos))
					$valor = str_replace(',','.',$valor);
				$valor = trim($valor);
			}
			
			//CEDULA IGUAL A CUENTA
			// if ($linea['IDENTIFICACION']==$linea['CUENTA']){
			// 	$result['identificacion_igual_cuenta'][] = $linea['CUENTA'];
			// 	continue;
			// }

			//valida que la cedula no este vacia
			// $cedula=trim($linea['IDENTIFICACION']);
			// if (strlen($cedula)!=10 && strlen($cedula)!=13){
			// 	$cedula = trim($linea['IDENTIFICACION'],'0');
			// }
			// if ($cedula=='' || trim($linea['IDENTIFICACION'],'0')=='') {
			// 	$result['sin_identificacion'][] = $linea['CUENTA'];
			// 	continue;
			// 	throw new Exception ('Cuenta '.$linea['CUENTA'].' sin IDENTIFICACION: '.$num_linea);
			// }
			// VALIDAR LHUN
			// if (is_numeric($cedula)){
			// 	if (!Helpers::luhn_validate(substr($cedula, 0, 10))) {
			// 		$result['luhn'][]=$linea['CUENTA'];
			// 		continue;
			// 		throw new Exception ('Identificación no cumple LUHN: '.$linea['IDENTIFICACION'].' linea: '.$num_linea);
			// 	}
			// }
			//valida que la cuenta no este vacia
			if ($linea['CUENTA']=='') {
				$result['no_cuenta'][] = $num_linea;
				throw new Exception ('No existe cuenta en linea: '.$num_linea);
			}
			// valida que la cuenta no este duplicada
			if (array_key_exists($linea['CUENTA'],$this->data)) {
				$result['cuenta_duplicada'][] = $linea['CUENTA'];
				continue;
				throw new Exception ('Cuenta '.$linea['CUENTA'].' duplicada en linea: '.$num_linea);
			}
			//valida que los campos de valores tengan datos numéricos
			foreach ($campos_numericos as $c) {
				if ($linea[$c]=='') continue;
				if (!is_numeric($linea[$c])) {
					$result['campo_no_numerico'][] = $linea['CUENTA'];
					continue;
					throw new exception('El valor del campo "'.$c.'" debe ser numérico en línea: '.$num_linea);
				}
			}

			$data = array(
				'cliente' => array(
					'IDENTIFICACION' => $linea['IDENTIFICACION'],
					'CLIENTE' => $linea['CLIENTE'],
					'CATASTRO' => $linea['CATASTRO'],
					'_direcciones' => array(
						array(
							'CIUDADELA' => $linea['CIUDADELA'],
							'CALLE PRINCIPAL' => $linea['CALLE1'],
							'CALLE SECUNDARIA' => $linea['CALLE2'],
							'LATITUD' => $linea['LATITUD'],
							'LONGITUD' => $linea['LONGITUD']
						),
					),
					'_correos' => array(
						$linea['CORREO_CLIENTE']
						// 'paul.fcc@hotmail.com'
					),
					'_telefonos' => array(
						$linea['TLF_CONV'],
						$linea['CELULAR']
						// '0939941936'
					)
				),
				'cuenta' => array(
					'CUENTA' => $linea['CUENTA'],
					'TIPO_CONSUMO' => $linea['TIPO_CONSUMO'],
					'SERVICIO' => $linea['SEFVICIO'],
					'ESTADO' => $linea['ESTADO'],
					'RECLAMO' => $linea['RECLAMO'],
					'NUM_MEDIDOR' => $linea['NUM_MEDIDOR'],
					'FACTURAS_VENCIDAS' => $linea['FACTURAS_VENCIDAS'],
					'OBLIGACIONES_CORRIENTES' => $linea['OBLIGACIONES_CORRIENTES'],
					'OBLIGACIONES_VENCIDAS' => $linea['OBLIGACIONES_VENCIDAS'],
					'DEUDA_PORTOAGUAS' => $linea['DEUDA_PORTOAGUAS'],
					'SALDO_CONVENIO' => $linea['SALDO_CONVENIO'],
					'fecha de facturacion' => $linea['fecha de facturacion'],
					'VENCIMIENTO_FACTURA' => $linea['VENCIMIENTO_FACTURA'],
					'fecha_emision_mes' => $linea['fecha_emision_mes']
				)
			);
			$this->data[$linea['CUENTA']] = $data;
			
		}
		if (!empty($result)) {
			echo '<b>El archivo tiene errores, revisar el detalle:</b>';
			print_arr($result);
			die();
		}

		$this->setTipoCarga('cartera');

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

		if ($line['cliente']['IDENTIFICACION']=='') throw new Exception('Número de identificación vacía en línea: '.$this->ptr);
		if ($line['cuenta']['CUENTA']=='') throw new Exception('Número de cuenta vacía en línea: '.$this->ptr);
		if ($line['cuenta']['CUENTA']['DEUDA_PORTOAGUAS']=='') throw new Exception('Cuenta "'.$line['cuenta']['CUENTA'].'" no tiene valor a pagar en linea: '.$this->ptr);

		$ret = array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);

		$cuenta = new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta = $line['cuenta']['CUENTA'];
		$cuenta->valor_actual = round(str_replace(',','',$line['cuenta']['DEUDA_PORTOAGUAS']),2);

		// persona responsable
		$cuenta->persona_responsable = new CargaModelo_Item_Persona();
		
		$tipo_id = 'CEDULA';
		$cuenta->persona_responsable->tipo_identificacion = $tipo_id;
		$cuenta->persona_responsable->identificacion=$line['cliente']['IDENTIFICACION'];
		$cuenta->persona_responsable->primer_nombre=$line['cliente']['CLIENTE'];
		// direcciones cliente
		foreach ($line['cliente']['_direcciones'] as $direccion){
			$cuenta->persona_responsable->add_direccion('RESIDENCIA',$direccion);
		}
		
		// telefonos deudor
		foreach ($line['cliente']['_telefonos'] as $tels){
			foreach ($this->parseTelefonos($tels) as $t){
				$cuenta->persona_responsable->add_medio_contacto('TELEFONO',$t);
			}
		}
		// correos deudor
		foreach ($line['cliente']['_correos'] as $correo){
			$correo = $this->parseCorreos($correo);
			if ($correo!=''){
				$cuenta->persona_responsable->add_medio_contacto('CORREO',$correo);
			}
		}
		
		$ret['cuenta']=$cuenta;

		$ret['otros_datos']['CUENTA'] = $line['cuenta']['CUENTA'];
		$ret['otros_datos']['CATASTRO'] = $line['cliente']['CATASTRO'];
		$ret['otros_datos']['TIPO_CONSUMO'] = $line['cuenta']['TIPO_CONSUMO'];
		$ret['otros_datos']['SERVICIO'] = $line['cuenta']['SERVICIO'];
		$ret['otros_datos']['ESTADO'] = $line['cuenta']['ESTADO'];
		$ret['otros_datos']['RECLAMO'] = $line['cuenta']['RECLAMO'];
		$ret['otros_datos']['NUM_MEDIDOR'] = $line['cuenta']['NUM_MEDIDOR'];
		$ret['otros_datos']['FACTURAS_VENCIDAS'] = $line['cuenta']['FACTURAS_VENCIDAS'];
		$ret['otros_datos']['OBLIGACIONES_CORRIENTES'] = $line['cuenta']['OBLIGACIONES_CORRIENTES'];
		$ret['otros_datos']['OBLIGACIONES_VENCIDAS'] = $line['cuenta']['OBLIGACIONES_VENCIDAS'];
		$ret['otros_datos']['DEUDA_PORTOAGUAS'] = $line['cuenta']['DEUDA_PORTOAGUAS'];
		$ret['otros_datos']['SALDO_CONVENIO'] = $line['cuenta']['SALDO_CONVENIO'];
		$ret['otros_datos']['fecha de facturacion'] = $line['cuenta']['fecha de facturacion'];
		$ret['otros_datos']['VENCIMIENTO_FACTURA'] = $line['cuenta']['VENCIMIENTO_FACTURA'];
		$ret['otros_datos']['fecha_emision_mes'] = $line['cuenta']['fecha_emision_mes'];
		$ret['otros_datos']['CIUDADELA'] = $line['cliente']['_direcciones'][0]['CIUDADELA'];

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

class CarteraPortoAguas extends CargaModelo_Handler_Abstract {

	function getTipoBase() {
		return 'Cartera';
	}
	
	function getDescripcion() {
		return 'Cartera';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader = new Cargador_CarteraPortoAguas($SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '2':
				if ($_FILES['archivo']['error']!='0') throw new Exception('Error '.$_FILES['archivo']['error'].' al cargar archivo de cartera');
				$aux = $SM->carga_process;
				$aux['source_file']=uniqid();
				$aux['original_filename']=$_FILES['archivo']['name'];
				$SM->carga_process=$aux;
				if(!move_uploaded_file($_FILES['archivo']['tmp_name'],_TMP_UPLOAD_FOLDER.'/'.$aux['source_file']))
					throw new Exception('Error al mover archivo subido');
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
				die();
			break;
			
			case '1':
				$__data['_T']['maincontent']='
				<table>
					<tr>
						<td>
							<img src="user/Portoaguas/Uploaders/logo_empresa.png" width="80px" height="100px">
						</td>
						<td>
							<h1>Carga de Cartera - Portoaguas</h1><br>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
								<br><br>
								<input type="file" name="archivo" />
								<br>
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
		);
		if($with_data) {
			$ret['bases_modelo']['modelo_cartera_portoagua.txt']=file_get_contents(dirname(__FILE__).'/modelo_cartera_portoagua.txt');
		}
		return $ret;
	}

}