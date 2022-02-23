<?php
class ODBC_Query implements Iterator {
	private $buffer=array();
	private $ptr;
	private $header;
	
	function __construct($odbc,$query) {
		$ptr=-1;
		$this->result=odbc_exec($odbc,$query);
		if(!$this->result)
			throw new Exception('Error al ejecutar query. '.$query);
	
		$this->numCols=odbc_num_fields($this->result);
		$this->header=array();
		for($j=0;$j<$this->numCols;$j++) {
			$this->header[]=odbc_field_name($this->result,$j);
		}
		$first_row=odbc_fetch_array($this->result);
		if($first_row!==false) {
			$this->hasRows=true;
			$this->buffer[]=$first_row;
		}else{
			$this->hasRows=false;
		}
		
	}
	
	function numRows() {
		if(!$this->hasRows) return 0;
		while($row=odbc_fetch_array($this->result)) {
			$this->buffer[]=$row;
		}
		return count($this->buffer);
	}
	
	function getHeader() {
		return $this->header;
	}
	
	function next() {
		$this->ptr++;
		if(!array_key_exists($this->ptr,$this->buffer)) {
			$row=odbc_fetch_array($this->result);
			if($row!==false) {
				$this->buffer[]=$row;
			}else{
				odbc_free_result($this->result);
			}
		}
	}
	
	function current() {
		return $this->buffer[$this->ptr];
	}
	
	function valid() {
		if($this->ptr==0 && $this->hasRows) return true;
		if(array_key_exists($this->ptr,$this->buffer)) return true;
		return false;
	}
	
	function key() {
		return $this->ptr;
	}
	
	function rewind() {
		$this->ptr=0;
	}
	
	function __destruct() {
		@odbc_free_result($this->result);
		unset($this->buffer);
	}
}