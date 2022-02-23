<?php
abstract class CargaModelo_Actualizaciones_Abstract implements CargaModelo_Actualizaciones_Interface, Iterator {
	private $_files=array();
    private $tipo_carga=null;
	
	function processRecord(&$line) {
		throw new Exception('Cargador Mal implementado. Hijo debe implementar processRecord(&$line)');
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

	// Iterator
	function next() {
		throw new Exception('Hijo debe implementar iterator (next)');
    }


	function current() {
		throw new Exception('Hijo debe implementar iterator (current)');
    }


	function rewind() {
		throw new Exception('Hijo debe implementar iterator (rewind)');
    }


	function key() {
		throw new Exception('Hijo debe implementar iterator (key)');
    }


	function valid() {
		throw new Exception('Hijo debe implementar iterator (valid)');
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