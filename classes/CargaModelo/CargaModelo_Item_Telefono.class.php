<?php
class CargaModelo_Item_Telefono extends CargaModelo_Item_Abstract {
	static private $pregs=array(
		array(
			'tipo'=>'CONVENCIONAL',
			'preg'=>'#^0[2-7]\d{7}$#'
		),
		array(
			'tipo'=>'CELULAR',
			'preg'=>'#^09\d{8}$#'
		),
	);
	
	function __construct() {
		$this->_data=array(
			'tipo'=>null,
			'numero'=>null,
			'origen'=>'BASE'
		);
	}
	
	function __set($k,$v) {
		if($k=='tipo') throw new Exception('No se puede cambiar el atributo "tipo" en clase "'.get_called_class().'"');
		if($k=='numero') {
			foreach (self::$pregs as $preg) {
				if (preg_match($preg['preg'],$v)) {
					$this->_data['tipo']=$preg['tipo'];
					$this->_data['numero']=$v;
					return;
				}
			}
			throw new Exception('El número "'.$v.'" no es válido (clase '.get_called_class().')');
		}
	}
	
	function validate() {
		die('validate');
	}
}