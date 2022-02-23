<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RecaudacionesExcel extends CargaModelo_Actualizaciones_Abstract implements CargaModelo_Actualizaciones_Interface, Iterator{
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
			'fecha',
			'convenio',
			'pago_recurrente'
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
			'total_cuentas'=>array(
				'count'=>0,
				'sum'=>array(
					'convenio'=>0,
					'pago'=>0
				)
			),
			'halladas'=>array(
				'total'=>0,
				'con_convenio_y_pago'=>array(
					'count'=>0,
					'sum'=>array(
						'convenio'=>0,
						'pago'=>0,
					)
				),
				'solo_convenio'=>array(
					'count'=>0,
					'sum'=>0
				),
				'solo_pago'=>array(
					'count'=>0,
					'sum'=>0
				),
			),
			'no_halladas'=>array(
				'count'=>0,
				'sum'=>array(
					'convenio'=>0,
					'pago'=>0
				)
			),
			'invalidas'=>0
		);
		for($i=2;$i<=$lastRow;$i++) {
			$counts['total_cuentas']['count']++;
			$counts['total_cuentas']['sum']['convenio']+=$this->sheet->getCellByColumnAndRow($col_convenio,$i)->getValue();
			$counts['total_cuentas']['sum']['pago']+=$this->sheet->getCellByColumnAndRow($col_pago,$i)->getValue();
			$cell=$this->sheet->getCellByColumnAndRow($col,$i);
			if(!preg_match('#^\d+$#',$cell->getValue())) {
				$counts['invalidas']++;
				continue;
			}
			$id_cuenta=$db->query('SELECT get_id_cuenta_by_cuenta_proceso AS id_cuenta FROM public.get_id_cuenta_by_cuenta_proceso('.$id_proceso.',\''.$db->escape($cell->getValue()).'\')');
			if(!is_null($id_cuenta->current()['id_cuenta'])) {
				$counts['halladas']['total']++;
				if($this->sheet->getCellByColumnAndRow($col_convenio,$i)->getValue()!='' && $this->sheet->getCellByColumnAndRow($col_pago,$i)->getValue()!='') {
					$counts['halladas']['con_convenio_y_pago']['count']++;
					$counts['halladas']['con_convenio_y_pago']['sum']['convenio']+=$this->sheet->getCellByColumnAndRow($col_convenio,$i)->getValue();
					$counts['halladas']['con_convenio_y_pago']['sum']['pago']+=$this->sheet->getCellByColumnAndRow($col_pago,$i)->getValue();
				}else if($this->sheet->getCellByColumnAndRow($col_convenio,$i)->getValue()!='') {
					$counts['halladas']['solo_convenio']['count']++;
					$counts['halladas']['solo_convenio']['sum']+=$this->sheet->getCellByColumnAndRow($col_convenio,$i)->getValue();
				}else if($this->sheet->getCellByColumnAndRow($col_pago,$i)->getValue()!='') {
					$counts['halladas']['solo_pago']['count']++;
					$counts['halladas']['solo_pago']['sum']+=$this->sheet->getCellByColumnAndRow($col_pago,$i)->getValue();
				}
			}else{
				$counts['no_halladas']['count']++;
				$counts['no_halladas']['sum']['convenio']+=$this->sheet->getCellByColumnAndRow($col_convenio,$i)->getValue();
				$counts['no_halladas']['sum']['pago']+=$this->sheet->getCellByColumnAndRow($col_pago,$i)->getValue();
			}
		}
		return $counts;
	}
	

	
	function processRecord(&$line) {
		if($line['pago_recurrente']['1']<_ZERO_THRESHOLD) return null;
		if($line['pago_recurrente'][1]=='' && $line['convenio'][1]=='') return null;
		$cuenta=new CargaModelo_Item_Cuenta();
		if(!preg_match('#^\d+$#',$line['contrato'][1]))
			return null;
		$cuenta->numero_cuenta=$line['contrato'][1];
		
		if($line['pago_recurrente'][1]!='') {
			$act=new CargaModelo_Item_CuentaActualizacion();
			$act->set('PAGO',$line['pago_recurrente'][1]*(-1),$line['fecha'][1]->format('Y-m-d'));
			$cuenta->pushActualizacion($act);
		}
		if($line['convenio'][1]!='') {
			/*
			$act=new CargaModelo_Item_CuentaActualizacion();
			$act->set('CONVENIO',$line['convenio'][1]*(-1),$line['fecha'][1]->format('Y-m-d'));
			$cuenta->pushActualizacion($act);
			*/
		}
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

class Recaudaciones extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Interagua - Recaudaciones';
	}
	
	function getDescripcion() {
		return 'Interagua - Recaudaciones';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '4':
				$uploader=new RecaudacionesExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '3':
				$uploader=new RecaudacionesExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				
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
				<tr><th>Descripcion</th><th>Cantidad</th><th>Suma Pagos</th><th>Suma Convenios</th></tr>
				<tr>
					<td>Total de cuentas</td>
					<td>'.$info['total_cuentas']['count'].'</td>
					<td>$'.$info['total_cuentas']['sum']['pago'].'</td>
					<td>$'.$info['total_cuentas']['sum']['convenio'].'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Total de cuentas en base</td>
					<td>'.$info['halladas']['total'].'</td>
					<td>$'.($info['halladas']['con_convenio_y_pago']['sum']['pago']+$info['halladas']['solo_pago']['sum']).'</td>
					<td>$'.($info['halladas']['con_convenio_y_pago']['sum']['convenio']+$info['halladas']['solo_convenio']['sum']).'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Con Convenio y Pago</td>
					<td>'.$info['halladas']['con_convenio_y_pago']['count'].'</td>
					<td>$'.$info['halladas']['con_convenio_y_pago']['sum']['pago'].'</td>
					<td>$'.$info['halladas']['con_convenio_y_pago']['sum']['convenio'].'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Con solo Convenio</td>
					<td>'.$info['halladas']['solo_convenio']['count'].'</td>
					<td>$'.$info['halladas']['solo_convenio']['sum'].'</td>
					<td>$'.$info['halladas']['solo_convenio']['sum'].'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Con solo Pago</td>
					<td>'.$info['halladas']['solo_pago']['count'].'</td>
					<td>$'.$info['halladas']['solo_pago']['sum'].'</td>
					<td>$'.$info['halladas']['solo_pago']['sum'].'</td>
				</tr>
				<tr>
					<td>Cuentas que no existen en base</td>
					<td>'.$info['no_halladas']['count'].'</td>
					<td>$'.$info['no_halladas']['sum']['pago'].'</td>
					<td>$'.$info['no_halladas']['sum']['convenio'].'</td>
				</tr>
				<tr>
					<td>Cuentas inválidas</td>
					<td>'.$info['invalidas'].'</td>
					<td>- -</td>
					<td>- -</td>
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
			'ModeloRecaudaciones.xlsx'=>''
		);
		if($with_data) {
			$ret['ModeloRecaudaciones.xlsx']=file_get_contents(dirname(__FILE__).'/ModeloRecaudaciones.xlsx');
		}
		return $ret;
		
	}
}
