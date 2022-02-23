<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class CarteraMunicipio extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $spreadsheet;
	private $sheet;
	private $lastRow;
	private $lastColStr;
	private $lastCol;
	private $header;

	private $ptr=0;

	function __construct($file_path) {
		$required_columns=array(
			'contrato',
			'grupo',
			'identificacion',
			'cliente',
			'sector',
			'saldo',
			'planillas',
		);


		$cache=new Psr\SimpleCache\FileSystemImplementation(DB::getInstance());
		\PhpOffice\PhpSpreadsheet\Settings::setCache($cache);
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
		$cache->buildIndex();
		$sheet=$spreadsheet->getSheet(0);
		$lastRow=$sheet->getHighestDataRow();
		$lastColStr=$sheet->getHighestDataColumn();
		$lastCol=Coordinate::columnIndexFromString($lastColStr);

		$header=array();
		// determine head
		for($i=1;$i<=$lastCol;$i++) {
			$header[$i]=trim(preg_replace('#\r\n#','',mb_strtolower($sheet->getCellByColumnAndRow($i,1))));
		}

		$faltan=array();
		foreach($required_columns as $rc) {
			if(!in_array($rc,$header)) {
				$faltan[]=$rc;
			}
		}
		if(!empty($faltan))
			throw new Exception('Faltan las siguientes columnas requeridas: {'.implode(', ',$faltan).'}');

		$this->spreadsheet=$spreadsheet;
		$this->sheet=$sheet;
		$this->header=$header;
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
		$cuenta=new CargaModelo_Item_Cuenta();
		if(!preg_match('#^\d+$#',$line['contrato'][1]))
			throw new Exception('Contrato inválido en línea '.$this->ptr.' ('.$line['contrato'][1].')');
		$cuenta->numero_cuenta=$line['contrato'][1];
		$cuenta->valor_actual=$line['saldo'][1];

		$cuenta->persona_responsable=new CargaModelo_Item_Persona();

		if(preg_match('#^0?\d{12}$#',$line['identificacion'][1])) {
			$cuenta->persona_responsable->tipo_identificacion='RUC';
		}else if(preg_match('#^\d{10}$#',$line['identificacion'][1]) && Helpers::luhn_validate($line['identificacion'][1])) {
			$cuenta->persona_responsable->tipo_identificacion='CEDULA';
		}else if(preg_match('#^\d{9}$#',$line['identificacion'][1]) && Helpers::luhn_validate('0'.$line['identificacion'][1])) {
			$cuenta->persona_responsable->tipo_identificacion='CEDULA';
		}else if(preg_match('#[A-Za-z]#',$line['identificacion'][1])) {
			$cuenta->persona_responsable->tipo_identificacion='PASAPORTE';
		}else{
			$cuenta->persona_responsable->tipo_identificacion='OTRO';
		}

		if(preg_match('#^[1-9]\d{8}$#',$line['identificacion'][1]) && Helpers::luhn_validate('0'.$line['identificacion'][1])) {
			$line['identificacion'][1]='0'.$line['identificacion'][1];
		}
		$cuenta->persona_responsable->identificacion=$line['identificacion'][1];
		$cuenta->persona_responsable->primer_nombre=$line['cliente'][1];
		foreach($this->parseTelefonos($line['telefono'][1]) as $t) {
			$cuenta->persona_responsable->add_tel($t);
		}
		$ret=array(
			'cuenta'=>$cuenta,
			'otros_datos'=>array()
		);
		$ret['otros_datos']['grupo']=$line['grupo'][1];
		$ret['otros_datos']['sector']=$line['sector'][1];
		$ret['otros_datos']['planillas']=$line['planillas'][1];
		unset($line['grupo']);
		unset($line['sector']);
		unset($line['planillas']);

		foreach($line as $k=>$v) {
			$ret['otros_datos'][$k]=$v[1];
		}
		return $ret;
	}

	// Iterator
	function next() {
		$this->ptr++;
	}

	function current() {
		$ret=array();
		for($j=1;$j<=$this->lastCol;$j++) {
			$col=$this->sheet->getCellByColumnAndRow($j,$this->ptr);
			$ret[$this->header[$j]]=array(
				$col->getDataType(),
				$col->getValue()
			);
		}
		return $this->processRecord($ret);
	}

	function rewind() {
		$lastRow=$this->sheet->getHighestDataRow();
		$lastColStr=$this->sheet->getHighestDataColumn();
		$lastCol=Coordinate::columnIndexFromString($lastColStr);

		$this->lastRow=$lastRow;
		$this->lastColStr=$lastColStr;
		$this->lastCol=$lastCol;

		$this->ptr=2;

	}

	function key() {
		return $this->ptr;
	}

	function valid() {
		return ($this->ptr<=$this->lastRow);
	}

}

class CarteraMunicipioMensual extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Interagua - Cartera Mensual Municipio';
	}

	function getDescripcion() {
		return 'Interagua - Cartera Mensual Municipio';
	}

	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				return new CarteraMunicipio(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
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
				$__data['_T']['maincontent']='<h1>Carga de Cartera Mensual - Municipio</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				Seleccione el archivo:
				<input type="file" name="data">
				<br>
				<button class="btn btn-primary">Cargar</button>
				</form>

				';
			break;
		}

	}

	function getArchivoModelo($with_data=false) {
		$ret=array(
			'ModeloCarteraMunicipioMensual.xlsx'=>''
		);
		if($with_data) {
			$ret['ModeloCarteraMunicipioMensual.xlsx']=file_get_contents(dirname(__FILE__).'/ModeloCarteraMunicipioMensual.xlsx');
		}
		return $ret;

	}
}
