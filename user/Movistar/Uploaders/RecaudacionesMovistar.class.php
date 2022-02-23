<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class RecaudacionesMovistarExcel extends CargaModelo_Actualizaciones_Abstract implements CargaModelo_Actualizaciones_Interface, Iterator{
	private $spreadsheet;
	private $sheet;
	private $lastRow;
	private $lastColStr;
	private $lastCol;
	private $header;
	
	private $ptr=0;

	function __construct($file_path) {
		$required_columns=array(
			'cuenta_facturacion', // 'cuenta_facturacion',
			'cobro empresa1', // 'cobro_empresa',
			'pagos',
            'ajustes',
            'diferencia' // diferencia_empresa
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
		$this->setTipoCarga('recaudacion');
	}
	
	function getInfo($id_proceso) {
		$db=DB::getInstance();
		$lastRow=$this->sheet->getHighestDataRow();
		$lastColStr=$this->sheet->getHighestDataColumn();
		$lastCol=Coordinate::columnIndexFromString($lastColStr);
		$col=array_search('cuenta_facturacion',$this->header);
        $col_pagos=array_search('pagos',$this->header);
        $col_ajustes=array_search('ajustes',$this->header);
		$counts=array(
			'total_cuentas'=>array(
				'count'=>0,
				'sum'=>array(
					'pago'=>0,
					'ajuste'=>0
				)
			),
			'halladas'=>array(
				'total'=>0,
				'con_pago_y_ajuste'=>array(
					'count'=>0,
					'sum'=>array(
                        'pago'=>0,
                        'ajuste'=>0
					)
				),
				'solo_pago'=>array(
					'count'=>0,
					'sum'=>0
				),
				'solo_ajuste'=>array(
					'count'=>0,
					'sum'=>0
				)
			),
			'no_halladas'=>array(
				'count'=>0,
				'sum'=>array(
                    'pago'=>0,
                    'ajuste'=>0
				)
			),
			'invalidas'=>0
		);
		for ($i=2;$i<=$lastRow;$i++) {
            $counts['total_cuentas']['count']++;
            $counts['total_cuentas']['sum']['pago']+=$this->sheet->getCellByColumnAndRow($col_pagos,$i)->getValue();
			$counts['total_cuentas']['sum']['ajuste']+=$this->sheet->getCellByColumnAndRow($col_ajustes,$i)->getValue();
			$cell=$this->sheet->getCellByColumnAndRow($col,$i);
			if (!preg_match('#^\d+$#',$cell->getValue())) {
				$counts['invalidas']++;
				continue;
			}
			$id_cuenta=$db->query('SELECT get_id_cuenta_by_cuenta_proceso AS id_cuenta FROM public.get_id_cuenta_by_cuenta_proceso('.$id_proceso.',\''.$db->escape($cell->getValue()).'\')');
			if (!is_null($id_cuenta->current()['id_cuenta'])) {
				$counts['halladas']['total']++;
				if ($this->sheet->getCellByColumnAndRow($col_ajustes,$i)->getValue()!='' && $this->sheet->getCellByColumnAndRow($col_pagos,$i)->getValue()!='') {
                    $counts['halladas']['con_pago_y_ajuste']['count']++;
                    $counts['halladas']['con_pago_y_ajuste']['sum']['pago']+=$this->sheet->getCellByColumnAndRow($col_pagos,$i)->getValue();
                    $counts['halladas']['con_pago_y_ajuste']['sum']['ajuste']+=$this->sheet->getCellByColumnAndRow($col_ajustes,$i)->getValue();
					
				}else if ($this->sheet->getCellByColumnAndRow($col_ajustes,$i)->getValue()!='') {
					$counts['halladas']['solo_ajuste']['count']++;
					$counts['halladas']['solo_ajuste']['sum']+=$this->sheet->getCellByColumnAndRow($col_ajustes,$i)->getValue();
				}else if ($this->sheet->getCellByColumnAndRow($col_pagos,$i)->getValue()!='') {
					$counts['halladas']['solo_pago']['count']++;
					$counts['halladas']['solo_pago']['sum']+=$this->sheet->getCellByColumnAndRow($col_pagos,$i)->getValue();
				}
			}else{
				$counts['no_halladas']['count']++;
                $counts['no_halladas']['sum']['pago']+=$this->sheet->getCellByColumnAndRow($col_pagos,$i)->getValue();
                $counts['no_halladas']['sum']['ajuste']+=$this->sheet->getCellByColumnAndRow($col_ajustes,$i)->getValue();
			}
		}
		return $counts;
	}
	

	function processRecord(&$line) {
		//print_arr($line);

		if (floatval($line['pagos'][1])==0 && floatval($line['ajustes'][1])==0 && floatval($line['factura del mes'][1])==0) return null;
		if ($line['pagos'][1]=='' && $line['ajustes'][1]=='' && $line['factura del mes'][1]=='') return null;
		if (!preg_match('#^\d+$#',$line['cuenta_facturacion'][1])) return null;

		if (floatval($line['pagos'][1])<0) throw new exception ('La cuenta: '.$line['cuenta_facturacion'][1].' tiene "pago" negativo.');
		if (floatval($line['factura del mes'][1])<0) throw new exception ('La cuenta: '.$line['cuenta_facturacion'][1].' tiene "factura del mes" negativo.');

		$cuenta = new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta = $line['cuenta_facturacion'][1];
		$cuenta->valor_actual = $line['diferencia'][1];
		$procesar = false;

		// 20210218 se agrega "AJUSTE+" por factura del mes
		// if ($line['factura del mes'][1]!='' && floatval($line['factura del mes'][1])>0){
		// 	$cuenta->add_actualizacion('AJUSTE+',floatval($line['factura del mes'][1]),date('Y-m-d'));
		// 	$procesar=true;
		// }

		if ($line['pagos'][1]!='' && floatval($line['pagos'][1])>0) {
			$cuenta->add_actualizacion('PAGO',floatval($line['pagos'][1])*(-1),date('Y-m-d'));
			$procesar=true;
        }
		if ($line['ajustes'][1]!=''){
			if (floatval($line['ajustes'][1])>0){
				$cuenta->add_actualizacion('AJUSTE-',floatval($line['ajustes'][1])*(-1),date('Y-m-d'));
			}elseif(floatval($line['ajustes'][1])<0){
				$cuenta->add_actualizacion('AJUSTE+',floatval($line['ajustes'][1])*-1,date('Y-m-d'));
			}
			$procesar=true;
		}

		//print_arr($cuenta);
		//var_dump($procesar);
		//die();
		if (!$procesar) return null;

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

class RecaudacionesMovistar extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Movistar - Recaudaciones';
	}
	
	function getDescripcion() {
		return 'Movistar - Recaudaciones';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '4':
				$uploader = new RecaudacionesMovistarExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '3':
				$uploader = new RecaudacionesMovistarExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
                $info = $uploader->getInfo($__data['id_proceso']);
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
				<h1>Recaudaciones - Movistar</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'4','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				<table border="1" class="table-info">
				<tr><th>Descripcion</th><th>Cantidad</th><th>Suma Pagos</th><th>Suma Ajustes</th></tr>
				<tr>
					<td>Total de cuentas</td>
					<td>'.$info['total_cuentas']['count'].'</td>
					<td>$'.$info['total_cuentas']['sum']['pago'].'</td>
					<td>$'.$info['total_cuentas']['sum']['ajuste'].'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Total de cuentas en base</td>
					<td>'.$info['halladas']['total'].'</td>
					<td>$'.($info['halladas']['con_pago_y_ajuste']['sum']['pago']+$info['halladas']['solo_pago']['sum']).'</td>
					<td>$'.($info['halladas']['con_pago_y_ajuste']['sum']['ajuste']+$info['halladas']['solo_ajuste']['sum']).'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Con ajuste y Pago</td>
					<td>'.$info['halladas']['con_pago_y_ajuste']['count'].'</td>
					<td>$'.$info['halladas']['con_pago_y_ajuste']['sum']['pago'].'</td>
					<td>$'.$info['halladas']['con_pago_y_ajuste']['sum']['ajuste'].'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Con solo Ajustes</td>
					<td>'.$info['halladas']['solo_ajuste']['count'].'</td>
                    <td>$0</td>
                    <td>$'.$info['halladas']['solo_ajuste']['sum'].'</td>
				</tr>
				<tr style=background-color: "#FFAAD4">
					<td>Con solo Pago</td>
					<td>'.$info['halladas']['solo_pago']['count'].'</td>
					<td>$'.$info['halladas']['solo_pago']['sum'].'</td>
					<td>$0</td>
				</tr>
				<tr>
					<td>Cuentas que no existen en base</td>
					<td>'.$info['no_halladas']['count'].'</td>
					<td>$'.$info['no_halladas']['sum']['pago'].'</td>
					<td>$'.$info['no_halladas']['sum']['ajuste'].'</td>
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
				</form>';
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
				$__data['_T']['maincontent']='<h1>Recaudaciones - Movistar</h1>
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
			'ModeloCarteraMovistar.xlsx'=>''
		);
		if($with_data) {
			$ret['ModeloCarteraMovistar.xlsx']=file_get_contents(dirname(__FILE__).'/ModeloCarteraMovistar.xlsx');
		}
		return $ret;
		
	}
}
