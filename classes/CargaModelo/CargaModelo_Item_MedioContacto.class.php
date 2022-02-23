<?php
class CargaModelo_Item_MedioContacto extends CargaModelo_Item_Abstract {
	private $_valid_tipos;
	function __construct() {
		$this->_data=array(
			'tipo'=>null,
			'contenido'=>null
		);
		$this->_valid_tipos=$this->getEnumValues('enum_tipo_medio_contacto');
	}
	
	function set($tipo,$contenido) {
		if(!in_array($tipo,$this->_valid_tipos))
			throw new Exception(get_called_class().'::set - El tipo "'.$tipo.'" no es válido. Debe utilizar uno de la lista {'.implode(',',$this->_valid_tipos).'}');
		$this->_data['tipo']=$tipo;
		$this->_data['contenido']=$contenido;
	}
	
	function __set($k,$v) {
		if($k=='tipo') throw new Exception('No se puede cambiar el atributo "tipo" en clase "'.get_called_class().'"');
		parent::__set($k,$v);
	}
	
	function validate() {
		die('validate');
	}
}