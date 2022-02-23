<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ActualizacionSaldoExcel extends CargaModelo_Actualizaciones_Abstract implements CargaModelo_Actualizaciones_Interface, Iterator{
	private $spreadsheet;
	private $sheet;
	private $lastRow;
	private $lastColStr;
	private $lastCol;
	private $header;
	
	private $ptr=0;
    private static $preparedGetCuenta = false;
    
	function __construct($file_path) {
        $this->db = DB::getInstance();
		$required_columns=array(
			'fecha',
			'contrato',
			'saldo'
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
	
	function getInfo($id_proceso) {
		$db=DB::getInstance();
		$lastRow=$this->sheet->getHighestDataRow();
		$lastColStr=$this->sheet->getHighestDataColumn();
		$lastCol=Coordinate::columnIndexFromString($lastColStr);
		$col=array_search('contrato',$this->header);
		$col_convenio=array_search('convenio',$this->header);
		$col_pago=array_search('pago_recurrente',$this->header);
		$counts=array(
			'total_cuentas'=>0,
            'halladas'=>0,
            'no_halladas'=>0
		);
		for($i=2;$i<=$lastRow;$i++) {
			$counts['total_cuentas']++;
		}
		return $counts;
	}
	

	
	function processRecord(&$line) {
        if(!self::$preparedGetCuenta) {
            $this->db->prepare('getCuenta','SELECT c.*,p.identificacion,
            p.primer_nombre,
            p.segundo_nombre,
            p.primer_apellido,
            p.segundo_apellido
            FROM cuentas.cuenta c
            JOIN personas.persona p ON (p.id_persona = c.id_deudor)
            WHERE c.cuenta=$1 AND c.id_proceso=\''.$this->db->escape($_POST['id_proceso']).'\'
            
            ');
            self::$preparedGetCuenta = true;
        }

		if(!preg_match('#^\d+$#',$line['contrato'][1]))
			return null;
        $cuenta = $this->db->execute('getCuenta',array($line['contrato'][1]));
        if($cuenta->numRows()==0)
            return null;
        $cuentaDb=$cuenta->current();
        $cuenta = new CargaModelo_Item_Cuenta();
        $cuenta->numero_cuenta = $cuentaDb['cuenta'];
        $cuenta->valor_actual = $line['saldo'][1];
        
        $diff = $line['saldo'][1]-$cuentaDb['valor_actual'];
        if(abs($diff)<_ZERO_THRESHOLD) $diff=0;
        $cuenta->add_actualizacion('CORRECCION',$diff,$line['fecha'][1]->format('Y-m-d'));
        $persona = new CargaModelo_Item_Persona();
        
        $persona->tipo_identificacion = 'CEDULA';
        $persona->identificacion = $cuentaDb['identificacion'];
        $persona->primer_nombre = $cuentaDb['primer_nombre'];
        $persona->segundo_nombre = $cuentaDb['segundo_nombre'];
        $persona->primer_apellido = $cuentaDb['primer_apellido'];
        $persona->segundo_apellido = $cuentaDb['segundo_apellido'];

        $cuenta->setResponsable($persona);
        
        
        return $cuenta;
        
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
		if($ret['fecha'][1]!='') {
			$ret['fecha'][0]='d';
			$ret['fecha'][1]=\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($ret['fecha'][1]);
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

class ActualizacionSaldo extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Interagua - Actualizacion Saldo';
	}
	
	function getDescripcion() {
		return 'Interagua - Actualizacion Saldo';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '4':
			break;
			
			case '3':
				$uploader=new ActualizacionSaldoExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				return $uploader;

				$uploader=new ActualizacionSaldoExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				$info=$uploader->getInfo($__data['id_proceso']);
				$__data['_T']['maincontent']='
				<style>
				.table-info {
					border-collapse: collapse;
				}
				
				.table-info th {
					text-align: center;
					background-color: #A5A5A5;
					padding-left: 5px;
					padding-right: 5px;
				}
				
				.table-info td {
					padding: 5px;
				}
				
				.table-info tr td:nth-child(2), .table-info tr td:nth-child(3), .table-info tr td:nth-child(4) {
					text-align: center;
				}
				
				</style>
				<h1>Carga de Recaudaciones - Interagua</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'4','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				<table border="1" class="table-info">
				<tr><th>Descripcion</th><th>Cantidad</th></tr>
				<tr>
					<td>Total de cuentas</td>
					<td>'.$info['total_cuentas'].'</td>
				</tr>
				</table>
				<br><br>
				<button class="btn btn-primary">Cargar</button>
				</form>
				
				';
				
				
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
				$__data['_T']['maincontent']='<h1>Carga de Recaudaciones - Interagua</h1>
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
			'Modelo_RECSALDOS.xlsx'=>''
		);
		if($with_data) {
			$ret['Modelo_RECSALDOS.xlsx']=file_get_contents(dirname(__FILE__).'/Modelo_RECSALDOS.xlsx');
		}
		return $ret;
		
	}
}
