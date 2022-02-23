<?php

class CarteraCnelCargador extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{

	private $ptr=0;
	private $data=array();


	function __construct($file_path) {
		$cabecera_requerida = array(
			'item',
			'unidad_de_negocio',
			'numero_servicio',
			'cedula',
			'cliente',
			'direccion',
			'estado',
			'deuda_total',
			'facturas_pendientes',
			'tarifa',
			'tipo_cliente',
			'cedula_valida',
			'rango_pla_pendientes',
			'coordenada_x',
			'coordenada_y',
			'numero_medidor',
			'serie_medidor',
			'descripcion_canton',
			'latitud',
			'longitud',
			'correo',
			'telefono1',
			'telefono2',
			'data'
		);
		$archivo = new Helpers_CSV($file_path);

		//validar cabecera de archivo
		if (!empty(array_diff($cabecera_requerida,$archivo->getHeader()))) throw new Exception('Cabecera de archivo incorrecta');

		// campos numericos
		$campos_numericos = array('deuda_total');

		$result = array();
		foreach ($archivo as $num_linea => $linea) {
			
			// eliminar espacios vacios de la linea y reemplaza , por . en valores float
			foreach ($linea as $campo => &$valor) {
				if (in_array($campo,$campos_numericos)){
					$valor = str_replace(',','.',$valor);
					$valor = str_replace('$','',$valor);
				}
				$valor = trim($valor);
			}


			if ($linea['numero_servicio']=='') {
				throw new Exception ('No existe cuenta en linea: '.$num_linea);
			}
			if ($linea['cedula']=='') {
				throw new Exception ('No existe Cedula en linea: '.$num_linea);
			}
			
			$this->data[] = $linea;
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
	
	function processRecord(&$line) {
		// print_arr($line);

		try {
			// VALIDACIONES
			if ($line['numero_servicio']=='') throw new exception('No existe numero de servicio en linea: '.$this->ptr);
			if ($line['deuda_total']=='' || floatval($line['deuda_total'])==0.0) throw new exception('No existe deuda total en linea: '.$this->ptr);
			if ($line['cedula']=='') throw new exception('No existe cedula en linea: '.$this->ptr);
			if ($line['cliente']=='') throw new exception('No existe cliente en linea: '.$this->ptr);

			$cuenta=new CargaModelo_Item_Cuenta();
			$cuenta->numero_cuenta = $line['numero_servicio'];
			$cuenta->valor_actual = floatval($line['deuda_total']);
			
			$cuenta->persona_responsable = new CargaModelo_Item_Persona();

			$cuenta->persona_responsable->tipo_identificacion='CEDULA';

			$cuenta->persona_responsable->identificacion = $line['cedula'];
			$cuenta->persona_responsable->primer_nombre=$line['cliente'];

			if ($line['correo']!=''){
				$cuenta->persona_responsable->add_medio_contacto('CORREO',$line['correo']);
			}

			if ($line['direccion']!=''){
				$cuenta->persona_responsable->add_direccion('OTROS',array('REFERENCIA'=>$line['direccion']));
			}

			if (trim($line['telefono1'])!=''){
				foreach ($this->parseTelefonos($line['telefono1']) as $t){
					$cuenta->persona_responsable->add_tel($t);
				}
			}
			if (trim($line['telefono2'])!=''){
				foreach ($this->parseTelefonos($line['telefono2']) as $t){
					$cuenta->persona_responsable->add_tel($t);
				}
			}

			$ret = array(
				'cuenta'=>$cuenta,
				'otros_datos'=>array()
			);

			$ret['otros_datos']['Item'] = $line['item'];
			$ret['otros_datos']['Unidad de Negocio'] = $line['unidad_de_negocio'];
			$ret['otros_datos']['Estado'] = $line['estado'];
			$ret['otros_datos']['Facturas_Pendientes'] = $line['facturas_pendientes'];
			$ret['otros_datos']['Tarifa'] = $line['tarifa'];
			$ret['otros_datos']['Tipo Cliente'] = $line['tipo_cliente'];
			$ret['otros_datos']['Cedula valida'] = $line['cedula_valida'];
			$ret['otros_datos']['Rango Pla Pendientes'] = $line['rango_pla_pendientes'];
			$ret['otros_datos']['Coordenada X'] = $line['coordenada_x'];
			$ret['otros_datos']['Coordenada Y'] = $line['coordenada_y'];
			$ret['otros_datos']['Numero Medidor'] = $line['numero_medidor'];
			$ret['otros_datos']['Serie Medidor'] = $line['serie_medidor'];
			$ret['otros_datos']['Descripcion_Canton'] = $line['descripcion_canton'];
			$ret['otros_datos']['Latitud'] = $line['latitud'];
			$ret['otros_datos']['Longitud'] = $line['longitud'];
			$ret['otros_datos']['data'] = $line['data'];
			
		}catch(Exception $e) {
			print_arr($e->getMessage());
			print_arr($line);
			die();
		}
		// print_arr($this->cuentas_count);
		// print_arr($ret);
		// die();

		return $ret;
	}

	// Iterator
	function next() {
		$this->ptr++;
	}
	
	function current() {
		return $this->processRecord($this->data[$this->ptr]);
	}
	
	function rewind() {
		$this->ptr=0;
		$this->cuentas_count = count($this->data);
	}
	
	function key() {
		return $this->ptr;
	}
	
	function valid() {
		return ($this->ptr < $this->cuentas_count);
	}
	
}

class CarteraCnel extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'CNEL - Carga de Cartera';
	}
	
	function getDescripcion() {
		return 'CNEL - Carga de Cartera';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader=new CarteraCnelCargador(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				return $uploader;

			break;
			
			case '2':
				if($_FILES['data']['error']!='0')
					throw new Exception('Error '.$_FILES['data']['error'].' al cargar archivo');
				
				$aux=$SM->carga_process;
				$aux['source_file']=uniqid();
				$aux['original_filename']=$_FILES['data']['name'];
				$SM->carga_process=$aux;
				
				if(!move_uploaded_file($_FILES['data']['tmp_name'],_TMP_UPLOAD_FOLDER.'/'.$aux['source_file']))
					throw new Exception('Error al mover archivo subido');
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
				die();
				
			break;
			
			case '1':
				$__data['_T']['maincontent']='<h1>Carga de Cartera - CNEL</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				Seleccione el archivo:
				<input type="file" name="data">
				<br>
				<button class="btn btn-primary">Cargar</button>
				</form>';
			break;
			
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'ModeloCarteraCNEL.txt'=>''
		);
		if($with_data) {
			$ret['ModeloCarteraCNEL.txt']=file_get_contents(dirname(__FILE__).'/ModeloCarteraCNEL.txt');
		}
		return $ret;
		
	}	
}