<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Cartera_Banco_Desarrollo extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator {
	private $header;
	private $data;

	private $ptr=0;
    private $SM;
    private $db;
    
	function __construct($fpath) {
        $this->SM = SessionManager::getInstance();
        $this->db = DB::getInstance();
        
		$required_columns=array(
			'identificacion_deudor',
			'nombre_deudor',
			'numero_prestamo',
			'total_a_pagar',
			'comision_cobranza',
			'dias_mora',
			'fecha_ultimo_pago',
			'telefono',
			'agencia',
			'tipo_titular_garante_codeudor'
		);

		if (!mb_check_encoding(file_get_contents($fpath),'UTF-8'))
			throw new Exception('El archivo debe estar codificado en UTF-8');
		$fhdl=fopen($fpath,'rb');
		$header=fgetcsv($fhdl,null,_UPLOADS_SEPARATOR,_UPLOADS_TEXT_QUALIFIER);
		if (count(array_unique($header))!=count($header))
			throw new Exception('El archivo subido contiene una o más columnas repetidas ('.print_r($header,true).')');

		$faltan=array();
		foreach($required_columns as $rc) {
			if(!in_array($rc,$header)) {
				$faltan[]=$rc;
			}
		}

		if (!empty($faltan))
			throw new Exception('Faltan las siguientes columnas requeridas: {'.implode(', ',$faltan).'}');

		fclose($fhdl);
		$csv = new Helpers_CSV($fpath);
		$data_agrupada=array();
		foreach ($csv as $num_linea=>$linea) {
			$data_agrupada[$linea['numero_prestamo']][]=$linea;
		}
		
		$this->header = $header;
		$this->data = $data_agrupada;
		$this->numRows = count($this->data);
		$this->keys=array_keys($this->data);
        
        $this->pushFile($this->SM->carga_process['original_filename'],$fpath);
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
		$ret=array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);
		$cuenta = new CargaModelo_Item_Cuenta();
		foreach ($line as $registro) {
			/*          
			agencia
			numero_prestamo
			identificacion_deudor
			nombre_deudor
			genero
			edad
			tipo_educacion
			total_a_pagar
			segmento_sbs
			comision_cobranza
			dias_mora
			fecha_ultimo_pago
			*/
            if(!preg_match('#^\d+$#',$registro['numero_prestamo']))
                throw new Exception('Número de préstamo inválido en línea '.$this->ptr.' ('.$registro['numero_prestamo'].'), debe ser campo numérico.');
            $cuenta->numero_cuenta = $registro['numero_prestamo'];
            $cuenta->valor_actual = round($registro['total_a_pagar'],2);
            
            $cuenta->persona_responsable=new CargaModelo_Item_Persona();
            $cuenta->persona_responsable->tipo_identificacion='CEDULA';
            if(preg_match('#^[1-9]\d{8}$#',$registro['identificacion_deudor']) && Helpers::luhn_validate('0'.$registro['identificacion_deudor'])) {
                $registro['identificacion_deudor']='0'.$registro['identificacion_deudor'];
            }
            $cuenta->persona_responsable->identificacion=$registro['identificacion_deudor'];
            $cuenta->persona_responsable->primer_nombre=$registro['nombre_deudor'];
            foreach($this->parseTelefonos($registro['telefono']) as $t) {
                $cuenta->persona_responsable->add_tel($t);
            }

            if(strtolower($registro['tipo_titular_garante_codeudor'])!='titular') {
                // es un codeudor
                
                $codeudor = new CargaModelo_Item_Persona();
                $codeudor->tipo_identificacion='CEDULA';
                if(preg_match('#^[1-9]\d{8}$#',$registro['identificacion_codeudor']) && Helpers::luhn_validate('0'.$registro['identificacion_codeudor'])) {
                    $registro['identificacion_codeudor']='0'.$registro['identificacion_codeudor'];
                }
                $codeudor->identificacion=$registro['identificacion_codeudor'];
                $codeudor->primer_nombre=$registro['nombres_codeudor'];
                foreach($this->parseTelefonos($registro['telefono']) as $t) {
                    $codeudor->add_tel($t);
                }
                $cuenta->pushOtraPersona($codeudor,$registro['tipo_titular_garante_codeudor']);
            }
		}
		$ret['cuenta']=$cuenta;
		$ret['otros_datos']['agencia']=$line[0]['agencia'];
		$ret['otros_datos']['genero']=$line[0]['genero'];
		$ret['otros_datos']['edad']=$line[0]['edad'];
		$ret['otros_datos']['tipo_educacion']=$line[0]['tipo_educacion'];
		$ret['otros_datos']['segmento_sbs']=$line[0]['segmento_sbs'];
		$ret['otros_datos']['comision_cobranza']=$line[0]['comision_cobranza'];
		$ret['otros_datos']['dias_mora']=$line[0]['dias_mora'];
		$ret['otros_datos']['fecha_ultimo_pago']=$line[0]['fecha_ultimo_pago'];
		return $ret;
	}

	// Iterator
	function next() {
		$this->ptr++;
	}
	
	function current() {
		//$this->data[$this->keys[$this->ptr]]['_processed_key']=$this->keys[$this->ptr];
		return $this->processRecord($this->data[$this->keys[$this->ptr]],$this->keys[$this->ptr]);
	}
	
	function rewind() {
		$this->ptr=0;
	}
	
	function key() {
		return $this->keys[$ptr];
	}
	
	function valid() {
		return $this->ptr<$this->numRows;
	}

	
}

class Cartera extends CargaModelo_Handler_Abstract {
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
				$uploader=new Cartera_Banco_Desarrollo(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
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
				$__data['_T']['maincontent']='<h1>Carga de Carteras - Banco Desarrollo</h1>
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
			'ModeloCartera.xlsx'=>''
		);
		if($with_data) {
			$ret['ModeloCartera.xlsx']=file_get_contents(dirname(__FILE__).'/modelo_cartera.xlsx');
		}
		return $ret;
	}
    
}
