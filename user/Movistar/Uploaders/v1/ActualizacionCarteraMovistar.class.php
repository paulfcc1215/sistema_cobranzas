<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ActualizacionCarteraMovistarExcel extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $spreadsheet;
	private $sheet;
	private $lastRow;
	private $lastColStr;
	private $lastCol;
	private $header;
	
	private $ptr=0;

	function __construct($file_path) {
		$required_columns=array(
			'cuenta_facturacion',
			'identificacion',
			'venc_gestion',
			'nombres',
			'cobro_empresa',
			'pagos',
			'ajustes',
			'diferencia_empresa',
			'telf_casa',
			'telf_oficina'
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
	
	function processRecord(&$line) {
		
		if (!preg_match('#^\d+$#',$line['cuenta_facturacion'][1]))
			throw new Exception('Contrato inválido en línea '.$this->ptr.' ('.$line['cuenta_facturacion'][1].')');
		if (trim($line['cobro_empresa'][1])=='')
			throw new Exception('Cobro_Empresa sin valor en línea '.$this->ptr.' ('.$line['cobro_empresa'][1].')');
		if (!is_numeric(trim($line['cobro_empresa'][1])))
			throw new Exception('Cobro_Empresa inválido en línea '.$this->ptr.' ('.$line['cobro_empresa'][1].')');
		if (trim($line['venc_gestion'][1])=='' or !in_array(trim($line['venc_gestion'][1]),array('0','30','60','90','120','150','180')))
			throw new Exception('Venc_Gestion inválido debe ser (0,30,60,90,120,150 ó 180) en línea '.$this->ptr.' ('.$line['venc_gestion'][1].')');
		$identificacion=trim($line['identificacion'][1]);
		if ($identificacion=='')
			throw new Exception('Identificación sin valor en línea '.$this->ptr.' ('.$line['identificacion'][1].')');

		$cuenta=new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta=$line['cuenta_facturacion'][1];
		$cuenta->valor_actual=floatval($line['cobro_empresa'][1]);
		//$cuenta->add_actualizacion('PAGO',(floatval($line['pagos'][1])>0?floatval($line['pagos'][1])*(-1):floatval($line['pagos'][1])),date('Y-m-d'));
		//$cuenta->add_actualizacion((floatval($line['ajustes'][1])<0?'AJUSTE+':'AJUSTE-'),(floatval($line['ajustes'][1])>0?floatval($line['ajustes'][1])*(-1):floatval($line['ajustes'][1])),date('Y-m-d'));

		$cuenta->persona_responsable=new CargaModelo_Item_Persona();

		if (preg_match('#^\d{13}$#',$identificacion)) {
			$cuenta->persona_responsable->tipo_identificacion='RUC';
		}else if (preg_match('#^\d{10}$#',$identificacion) && Helpers::luhn_validate($identificacion)) {
			$cuenta->persona_responsable->tipo_identificacion='CEDULA';
		}else if (preg_match('#^\d{9}$#',$identificacion) && Helpers::luhn_validate('0'.$identificacion)) {
			$cuenta->persona_responsable->tipo_identificacion='CEDULA';
		}else if (preg_match('#[A-Za-z]#',$identificacion)) {
			$cuenta->persona_responsable->tipo_identificacion='PASAPORTE';
		}else{
			$cuenta->persona_responsable->tipo_identificacion='OTRO';
		}
		
		$cuenta->persona_responsable->identificacion=$identificacion;
		$cuenta->persona_responsable->primer_nombre=$line['nombres'][1];
		foreach($this->parseTelefonos(array($line['telf_casa'][1],$line['telf_oficina'][1])) as $t) {
			$cuenta->persona_responsable->add_tel($t);
		}
		if (trim($line['e-mail'][1])!=''){
			$cuenta->persona_responsable->add_medio_contacto('CORREO',trim($line['e-mail'][1]));
		}
		$ret = array(
			'cuenta'=>$cuenta,
			'otros_datos'=>array()
		);
		$ret['otros_datos']['forma_pago']=$line['forma_pago'][1];
		$ret['otros_datos']['f_act_cuenta']=$line['f_act_cuenta'][1];
		$ret['otros_datos']['estado_cuenta']=$line['estado_cuenta'][1];
		$ret['otros_datos']['direccion1']=$line['direccion1'][1];
		$ret['otros_datos']['ciudad']=$line['ciudad'][1];
		$ret['otros_datos']['credit_class']=$line['credit_class'][1];
		$ret['otros_datos']['venc_gestion']=$line['venc_gestion'][1];
		$ret['otros_datos']['saldo a diferir']=$line['saldo a diferir'][1];
		$ret['otros_datos']['saldo_diferido']=$line['saldo_diferido'][1];
		$ret['otros_datos']['facturas_delmes']=$line['facturas_delmes'][1];
		$ret['otros_datos']['ejecutivo_asignado']=$line['ejecutivo_asignado'][1];
		$ret['otros_datos']['diferencia janus']=$line['diferencia janus'][1];
		$ret['otros_datos']['mes_periodo']=$line['mes_periodo'][1];
		$ret['otros_datos']['ciclo_periodo']=$line['ciclo_periodo'][1];
		$ret['otros_datos']['adendum_predictivo']=$line['adendum_predictivo'][1];
		$ret['otros_datos']['financiamiento']=$line['financiamiento'][1];
		unset($line['forma_pago']);
		unset($line['f_act_cuenta']);
		unset($line['estado_cuenta']);
		unset($line['direccion1']);
		unset($line['ciudad']);
		unset($line['credit_class']);
		unset($line['venc_gestion']);
		unset($line['saldo a diferir']);
		unset($line['saldo_diferido']);
		unset($line['facturas_delmes']);
		unset($line['ejecutivo_asignado']);
		unset($line['diferencia janus']);
		unset($line['mes_periodo']);
		unset($line['ciclo_periodo']);
		unset($line['adendum_predictivo']);
		unset($line['financiamiento']);
		
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

class ActualizacionCarteraMovistar extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Movistar - Actualización de Cartera';
	}
	
	function getDescripcion() {
		return 'Movistar - Actualización de Cartera';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader=new ActualizacionCarteraMovistarExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
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
				$__data['_T']['maincontent']='<h1>Actualización de Carteras - Movistar</h1>
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
			'ModeloCarteraMovistar.xlsx'=>''
		);
		if($with_data) {
			$ret['ModeloCarteraMovistar.xlsx']=file_get_contents(dirname(__FILE__).'/ModeloCarteraMovistar.xlsx');
		}
		return $ret;
		
	}	
}