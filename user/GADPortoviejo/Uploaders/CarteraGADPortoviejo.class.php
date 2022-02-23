<?php

class Cargador_CarteraGADPortoviejo extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{

	private $data = array();
	private $ptr=0;

	function __construct($fpath_files) {

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
			'total'
		);

		$archivo = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$fpath_files);

		if(!mb_check_encoding(file_get_contents(_TMP_UPLOAD_FOLDER.'/'.$fpath_files),'UTF-8'))
                throw new Exception('El archivo debe ser plano y estar codificado en UTF-8');

		//validar cabecera de archivo
		if (!empty(array_diff($cabecera_requerida,$archivo->getHeader()))) throw new Exception('Cabecera de archivo incorrecta, se espera ('.implode(',',$cabecera_requerida).') ha enviado ('.implode(',',$archivo->getHeader()).')');

		// campos numericos
		$campos_numericos = array('total');


		// reglas de negocio
		// - existen titulos que no tienen principal
		// - las deudas se actualizan por la columna total (N)


		// control de resultados
		$validaciones = array();
		foreach ($archivo as $num_linea => $linea) {
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
			if ($linea['total']=='') $validaciones['total'][$linea['id']][]='sin_deuda';
			if (!is_numeric($linea['id'])) $validaciones['id'][$linea['id']][]='no_numerico';
			if (!is_numeric($linea['total'])) $validaciones['total'][$linea['id']][]='no_numerico';

			if (!in_array($linea['id'],array_keys($this->data))){
				$this->data[$linea['id']]=$linea;
				$this->data[$linea['id']]['persona_adicional'] = array();
			}else{
				$this->data[$linea['id']]['persona_adicional'][]=$linea;
			}
		}
		if (!empty($validaciones)){
			echo 'El archivo tiene errores, Detalles:';
			print_arr($validaciones);
			die();
		}
		$this->setTipoCarga('cartera');
	}

	function parseCorreos($correos){
		$correos = filter_var($correos,FILTER_VALIDATE_EMAIL);
		if ($correos==false){
			return '';
		}
		return $correos;
	}

	function processRecord(&$line) {
		// if ($line['id']!=='2172546') return null;
		// print_arr($line);

		if ($line['id']=='') throw new Exception('No hay Id de predio en linea: '.$this->ptr+1);
		if ($line['total']=='') throw new Exception('No hay deuda en linea: '.$this->ptr+1);
		if ($line['CEDULARUC']=='') throw new Exception('No hay identificacion en linea: '.$this->ptr+1);
		

		$ret = array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);

		$cuenta = new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta = $line['id'];
		$cuenta->valor_actual = $line['total'];

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
			foreach (array($ref['telefono'],$ref['celular']) as $t){
				$referencia->add_medio_contacto('TELEFONO',$t);
			}

			// correos referencia
			$correo = $this->parseCorreos($ref['email']);
			if ($correo!=''){
				$referencia->add_medio_contacto('CORREO',$correo);
			}
			
			$cuenta->pushOtraPersona($referencia,'CODEUDOR');
		}
		
		$ret['cuenta']=$cuenta;

		$ret['otros_datos']['tipo'] = $line['tipo'];
		$ret['otros_datos']['clave'] = $line['clave'];
		$ret['otros_datos']['añoemision'] = $line['añoemision'];
		$ret['otros_datos']['AñoObligacion'] = $line['AñoObligacion'];
		$ret['otros_datos']['MesObligacion'] = $line['MesObligacion'];

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

class CarteraGADPortoviejo extends CargaModelo_Handler_Abstract {

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
				$uploader = new Cargador_CarteraGADPortoviejo($SM->carga_process['source_file']);
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
							<img src="user/GADPortoviejo/Uploaders/logo_empresa.png" width="150px" height="160px">
						</td>
						<td>
							<h1>Carga de Cartera - GAD Municipal de Portoviejo</h1><br>
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
			'modelo_cartera_GADPortoviejo.txt'=>file_get_contents(dirname(__FILE__).'/modelo_cartera_GADPortoviejo.txt'),
		);
		if($with_data) {
			$ret['bases_modelo']['modelo_cartera_GADPortoviejo.txt']=file_get_contents(dirname(__FILE__).'/modelo_cartera_GADPortoviejo.txt');
		}
		return $ret;
	}

}