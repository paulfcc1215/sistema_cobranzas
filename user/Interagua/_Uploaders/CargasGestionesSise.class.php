<?php
class CargasGestionesSise extends CargaModelo_Handler_Abstract {
	
	private $db;
	function __construct() {
		DB::connect('pgsql',array(
			//'host'=>'192.168.180.230',
			'host'=>'127.0.0.1',
			'user'=>'postgres',
			'password'=>'postgres',
			'dbname'=>'sise',
		),'sise');
		$this->db=DB::getInstance('sise');
		
		
	}
	
    function getTipoBase() {
		return 'Gestiones From Sise 180.230';
    }
    function getDescripcion() {
        return 'Gestiones del Sise';
        die();
    }
	
	public function execute($step,&$__data) {
		require dirname(__FILE__).'/uploadable/GestionesUploadable.class.php';
		return new GestionesUploadable($this->db);
	}
	
	
}