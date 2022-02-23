<?php
class Helpers_ExcelReader implements Iterator {
	const SEEK_WHENCE_FROM_FIRST=1;
	const SEEK_WHENCE_FROM_LAST=2;
	const SEEK_WHENCE_FROM_CURRENT=4;
	const SEEK_WHENCE_SET=8;
	const SEEK_DIRECTION_FORWARD=16;
	const SEEK_DIRECTION_BACKWARD=32;
	
	
	
	private $_row=null;
	private $_col=null;
	private $_max_col=null;
	private $_max_row=null;
	private $_date_columns;
	
	private $head;
	private $spreadsheet;
	private $sheet;
	
	function __construct($file_path,$sheet_index=0) {
		require_once _BASE_SYS_PATH.'/lib/phpspreadsheet/vendor/autoload.php';
		$this->sheet_index=$sheet_index;
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
		$sheet=$spreadsheet->getSheet($sheet_index);
		
		$this->spreadsheet=$spreadsheet;
		$this->sheet=$sheet;
		
		$this->_max_col=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());
		$this->_max_row=$sheet->getHighestRow();
		
		
		$this->head=array();
		for($this->_col=1;$this->_col<=$this->_max_col;$this->_col++) {
			$val=$sheet->getCellByColumnAndRow($this->_col,1)->getValue();
			if(trim($val)=='') throw new Exception('La columna "'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->_col).'" de la cabecera está vacía');
			$this->head[]=$val;
		}
		
		$this->_row=2;
		$this->_col=1;
		$this->_date_columns=array();
	}
	
	function getHead() {
		return $this->head;
	}
	
	function setFechasCol($cols) {
		if(!is_array($cols)) $cols=array($cols);
		foreach($cols as $col) {
			if(!preg_match('#^\d+$#',$cols)) {
				$col=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(strtoupper($col));
			}
			if($col>$this->_max_col)
				throw new Exception('La columna '.$col.' es mayor a la cantidad de columnas que existen en la hoja');
		}
		$this->_date_columns[]=$col;
	}
	
	function getCol($col) {
		if(!preg_match('#^\d+$#',$col))
			$col=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(strtoupper($col));
		$aux=$this->current();
		return $aux[$col-1];
	}
	
	function seek($offset,$whence=self::SEEK_WHENCE_FROM_CURRENT | self::SEEK_DIRECTION_FORWARD) {
		if (($whence & self::SEEK_WHENCE_FROM_FIRST) > 0) {
			$this->_row=2+$offset;
			return;
		}
		
		if (($whence & self::SEEK_WHENCE_FROM_LAST) > 0) {
			$this->_row=$this->_max_row-$offset;
			return;
		}
		
		if (($whence & self::SEEK_WHENCE_FROM_CURRENT) > 0) {
			$ref=$this->_row;
			if($whence & self::SEEK_DIRECTION_BACKWARD) {
				$this->_row=$ref-$offset;
			}else{
				$this->_row=$ref+$offset;
			}
			return;
		}
		
		if (($whence & self::SEEK_WHENCE_SET) > 0) {
			if($offset > $this->_max_row)
				throw new Exception('La fila '.$offset.' es mayor que la cantidad de filas existentes en el archivo');
			$this->_row=$offset+1;
			return;
		}


		
	}
	
	function printRow($row=null) {
		if(!is_null($row)) $this->seek($row,self::SEEK_WHENCE_SET);
		echo '<table border="1">';
		echo '<tr>';
		foreach($this->head as $h) {
			echo '<th>'.$h.'</th>';
		}
		echo '<tr>';		
		foreach($this->current() as $r) {
			echo '<td>'.$r['value'].'</td>';
		}
		echo '</tr>';
		echo '</tr>';
		echo '</table>';
	}
	
	function printTable() {
		$this->_row=2;
		echo '<table border="1">';
		echo '<tr>';
		echo '<th>Fila</th>';
		foreach($this->head as $h) {
			echo '<th>'.$h.'</th>';
		}
		echo '</tr>';
		
		$i=0;
		while($this->valid()) {
			$i++;
			echo '<tr>';
			echo '<td>'.$i.'</td>';
			foreach($this->current() as $r) {
				echo '<td>'.$r['value'].'</td>';
			}
			echo '</tr>';
			$this->_row++;
		}
		echo '</table>';
	}
	
	// Iterator
	function current() {
		$row=array();
		for($this->_col=1;$this->_col<=$this->_max_col;$this->_col++) {
			$cell=$this->sheet->getCellByColumnAndRow($this->_col,$this->_row);
			$col=array(
				'head'=>$this->head[($this->_col-1)],
				'value'=>$cell->getValue(),
				'complex_value'=>null,
				'datatype'=>$cell->getDataType(),
				'coordinate'=>array(
					'row'=>$this->_row,
					'col_idx'=>$this->_col,
					'col'=>\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($this->_col),
				)
			);
			if(in_array($this->_col,$this->_date_columns)) {
				$val=\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($col['value']);
				$col['value']=$val->format('Y-m-d H:i:s');
				$col['complex_value']=$val;
				$col['datatype']='d';
			}
			$row[]=$col;
		}
		return $row;
	}
	
	function key() {
		return ($this->_row-1);
	}
	
	function next() {
		$this->_row++;
	}
	
	function rewind() {
		$this->_row=2;
	}
	
	function valid() {
		if($this->_row<=$this->_max_row) return true;
		return false;
	}
   
}