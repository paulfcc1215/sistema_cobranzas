<?php
abstract class CargaModelo_Handler_Abstract implements CargaModelo_Handler_Interface {
    function __construct() {
    }
    
    function getTipoBase() {
        echo 'Mala implementacion - Hijo debe implementar getTipoBase(), getDescripcion() y execute($step, &$__data)';
        die();
    }
    function getDescripcion() {
        echo 'Mala implementacion - Hijo debe implementar getTipoBase(), getDescripcion() y execute($step, &$__data)';
        die();
    }
	
	public function execute($step,&$__data) {
        echo 'Mala implementacion - Hijo debe implementar getTipoBase(), getDescripcion() y execute($step, &$__data)';
        die();
	}
	
	public function getArchivoModelo($with_data=false) {
		return null;
	}
    
}