<?php
abstract class CargaModelo_Gestiones_Abstract implements CargaModelo_Gestiones_Interface, Iterator {
	private $_files=array();
	
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


	
}