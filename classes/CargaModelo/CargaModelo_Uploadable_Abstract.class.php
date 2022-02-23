<?php
abstract class CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator {
	protected $original_fname;
	protected $original_fpath;
	protected $_line_num;
	protected $_head;
	protected $_fhdl;
	private $_first_line;
	private $_buffer;
	private $_csv_config;
	private $_files=array();
    private $tipo_carga=null;
    
	/*
	function getOriginalFileName() {
		if($this->original_fname=='')
			throw new Exception('La variable original_fname está vacía. Hijo debe escribirla.');
		return $this->original_fname;
	}
	function getOriginalFilePath() {
		if($this->original_fpath=='')
			throw new Exception('La variable original_fpath está vacía. Hijo debe escribirla.');
		return $this->original_fpath;
		
	}
	*/
	
	
	function __construct($file_path,$csv_separator="\t",$csv_text_qualifier='"',$csv_escape="\\") {
		$this->original_fname=$original_fname;
		$this->original_fpath=$file_path;
		
		$this->_csv_config=array(
			'separator'=>$csv_separator,
			'text_qualifier'=>$csv_text_qualifier,
			'escape'=>$csv_escape
		);
		
		$this->_fhdl=fopen($file_path,'rb+');
		if(!$this->_fhdl) throw new Exception('Error al abrir archivo "'.$file_path.'"');
		$bom=fread($this->_fhdl,2);
		$bom=ord($bom[0]) << 8 | ord($bom[1]);
		if($bom==0xEFBB || $bom==0xFEFF || $bom==0xFFFE)
			throw new Exception('El archivo debe estar codificado en UTF-8 sin BOM');
		fseek($this->_fhdl,0,SEEK_SET);
		$this->_head=fgetcsv($this->_fhdl,0,$this->_csv_config['separator'],$this->_csv_config['text_qualifier'],$this->_csv_config['escape']);
		$this->_first_line=ftell($this->_fhdl);
		$this->_line_num=1;
		$this->_buffer=array();
	}
	
	final function pushFile($filename,$filepath) {
		if(!is_readable($filepath))
			throw new Exception('El archivo con ruta "'.$filepath.'" no puede ser leido');
		$this->_files[]=array(
			'filename'=>$filename,
			'filepath'=>$filepath
		);
	}
	
	final function getFiles() {
		return $this->_files;
	}
	
	private function _readline() {
		$this->_buffer=array(
			0=>fgetcsv($this->_fhdl,0,$this->_csv_config['separator'],$this->_csv_config['text_qualifier'],$this->_csv_config['escape']),
			null
		);
		foreach($this->_head as $k=>$v) {
			$this->_buffer[1][$v]=$this->_buffer[0][$k];
		}
		return $this->_buffer;
	}
	
	
	// Iterator
	function next() {
		//echo 'next<br>';
		$this->_buffer=array();
		$this->_line_num++;
	}
	
	function current() {
		//echo 'current<br>';
		if(empty($this->_buffer)) {
			$this->_readline();
		}
		if(count($this->_buffer[0])!=count($this->_head))
			throw new Exception('La línea '.$this->_line_num.' no contiene la misma cantidad de columnas que la cabecera');
		return $this->processRecord($this->_buffer);
	}
	
	function rewind() {
		//echo 'rewind<br>';
		fseek($this->_fhdl,$this->_first_line,SEEK_SET);
		$this->_line_num=1;
		$this->_buffer=array();
		//$this->_readline();
	}
	
	function key() {
		return $this->_line_num;
	}
	
	function valid() {
		//echo 'valid<br>';
		return (!feof($this->_fhdl));
	}
    
    function getTipoCarga() {
        return $this->tipo_carga;
    }
    
    function setTipoCarga($tipo) {
        $db = DB::getInstance();
        $valid_enums = $db->getEnumValues('enum_tipo_carga');
        if(!in_array($tipo,$valid_enums)) {
            throw new Exception('El tipo de carga "'.$tipo.'" no existe en el ENUM de la base de datos. {'.implode(',',$valid_enums).'}');
        }
        $this->tipo_carga=$tipo;
    }
	
	
}