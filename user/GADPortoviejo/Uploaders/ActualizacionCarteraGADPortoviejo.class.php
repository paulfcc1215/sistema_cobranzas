<?php

class Cargador_ActualizacionCarteraGADPortoviejo extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{

	private $data = array();
	private $ptr=0;

	function __construct($fpath_files) {
		// echo 'inicio: '.date('H:i:s').'<br>';
		$cabecera_requerida = array(
			'id',
			'principal',
			'CEDULARUC',
			'NOMBRE',
			'direccion',
			'telefono',
			'celular',
			'email',
			'tipo',
			'clave',
			'añoemision',
			'AñoObligacion',
			'MesObligacion',
			// 'total',
			'valorbruto',
			'intereses',
			'recargos'
		);

		if (!mb_check_encoding(file_get_contents(_TMP_UPLOAD_FOLDER.'/'.$fpath_files['archivo_actualizacion']),'utf8')) throw new Exception('El archivo debe ser un .txt codificado en UTF-8');

		$campos_numericos = array('valorbruto','intereses','recargos');
		$file = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$fpath_files['archivo_actualizacion']);
		//validar cabecera de actualizaciones
		if (!empty(array_diff($cabecera_requerida,$file->getHeader()))) throw new Exception('Cabecera de archivo de actualizaciones incorrecta.');
		$data = array();
		foreach ($file as $num_line => $linea) {
			// limpieza de datos
			foreach ($linea as $campo => &$valor) {
				$valor = trim(str_replace("\n"," ",str_replace("\r\n","\n",str_replace("'"," ",$valor))));
				if (in_array($campo,$campos_numericos)){
					$valor = str_replace(',','.',$valor);
				}
				unset($valor);
			}
			// validaciones
			if ($linea['id']=='') $validaciones['id'][$linea['id']][]='sin_cuenta';
			if ($linea['valorbruto']=='') $validaciones['valorbruto'][$linea['id']][]='sin_deuda';
			if (!is_numeric($linea['id'])) $validaciones['id'][$linea['id']][]='no_numerico';
			if (!is_numeric($linea['valorbruto'])) $validaciones['valorbruto'][$linea['id']][]='no_numerico';

			if (!in_array($linea['id'],array_keys($this->data))){
				$this->data[$linea['id']]=$linea;
				$this->data[$linea['id']]['persona_adicional'] = array();
			}else{
				$this->data[$linea['id']]['persona_adicional'][]=$linea;
			}

		}
		// echo 'inicio: '.date('H:i:s').'<br>';
		// print_arr(count($this->data));
		// print_arr($this->data);
		// die();
		$this->setTipoCarga('actualizacion');
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

		if ($line['id']=='') throw new Exception('No hay Id de predio en linea: '.$num_linea);
		if (!is_numeric($line['id'])) throw new Exception('Id de predio incorrecto en linea: '.$num_linea);
		if ($line['valorbruto']=='') throw new Exception('No hay deuda en linea: '.$num_linea);
		if (!is_numeric($line['valorbruto'])) throw new Exception('La deuda debe ser un numero, en linea: '.$num_linea);
		if ($line['CEDULARUC']=='') throw new Exception('No hay identificacion en linea: '.$num_linea);
		

		$ret = array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);

		$cuenta = new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta = $line['id'];
		$cuenta->valor_actual = floatval($line['valorbruto'])+floatval($line['intereses'])+floatval($line['recargos']);

		// persona responsable
		$cuenta->persona_responsable = new CargaModelo_Item_Persona();
		
		$tipo_id = 'CEDULA';
		if (strlen($line['CEDULARUC'])==13) $tipo_id = 'RUC';
		$cuenta->persona_responsable->tipo_identificacion = $tipo_id;
		$cuenta->persona_responsable->identificacion=$line['CEDULARUC'];
		$cuenta->persona_responsable->primer_nombre=$line['NOMBRE'];

		// direcciones cliente
		$cuenta->persona_responsable->add_direccion('RESIDENCIA',array('SECTOR'=>$line['direccion']));
		
		// telefonos deudor
		foreach (Helpers::parseTelefonos(array($line['telefono'],$line['celular'])) as $t){
			$cuenta->persona_responsable->add_medio_contacto('TELEFONO',$t);
		}
		// correos deudor
		$correo = $this->parseCorreos($line['email']);
		if ($correo!=''){
			$cuenta->persona_responsable->add_medio_contacto('CORREO',$correo);
		}

		// otros deudores
		foreach ($line['persona_adicional'] as $ref) {
			$referencia = new CargaModelo_Item_Persona();
			$tipo_id_ref = 'CEDULA';
			if (strlen($ref['CEDULARUC'])==13) $tipo_id_ref = 'RUC';
			$referencia->tipo_identificacion = $tipo_id_ref;
			$referencia->identificacion = $ref['CEDULARUC'];
			$referencia->primer_nombre = $ref['NOMBRE'];

			// direccion referencia
			$referencia->add_direccion('RESIDENCIA',array('SECTOR'=>$ref['direccion']));

			// telefonos referencias
			foreach (Helpers::parseTelefonos(array($ref['telefono'],$ref['celular'])) as $t){
				$referencia->add_medio_contacto('TELEFONO',$t);
			}

			// correos referencia
			$correo = $this->parseCorreos($ref['email']);
			if ($correo!=''){
				$referencia->add_medio_contacto('CORREO',$correo);
			}
			
			$cuenta->pushOtraPersona($referencia,'CODEUDOR');
		}
		
		$ret['cuenta'] = $cuenta;

		$ret['otros_datos']['tipo'] = $line['tipo'];
		$ret['otros_datos']['clave'] = $line['clave'];
		$ret['otros_datos']['añoemision'] = $line['añoemision'];
		$ret['otros_datos']['AñoObligacion'] = $line['AñoObligacion'];
		$ret['otros_datos']['MesObligacion'] = $line['MesObligacion'];

		$ret['otros_datos']['valorbruto'] = $line['valorbruto'];
		$ret['otros_datos']['intereses'] = $line['intereses'];
		$ret['otros_datos']['recargos'] = $line['recargos'];

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

class ActualizacionCarteraGADPortoviejo extends CargaModelo_Handler_Abstract {

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
				$uploader = new Cargador_ActualizacionCarteraGADPortoviejo($SM->carga_process['source_file']);
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

				$__data['_T']['maincontent']='
				<table>
					<tr>
						<td>
							<img src="user/GADPortoviejo/Uploaders/logo_empresa.png" width="150px" height="160px">
						</td>
						<td>
							<h1>Actualización de Cartera - GAD Municipal de Portoviejo</h1><br>
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
			'modelo_cartera_GADPortoviejo.txt'=>file_get_contents(dirname(__FILE__).'/modelo_cartera_GADPortoviejo.txt'),
		);
		if($with_data) {
			$ret['bases_modelo']['modelo_cartera_GADPortoviejo.txt']=file_get_contents(dirname(__FILE__).'/modelo_cartera_GADPortoviejo.txt');
		}
		return $ret;
	}

}