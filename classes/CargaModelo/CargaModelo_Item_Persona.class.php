<?php
class CargaModelo_Item_Persona extends CargaModelo_Item_Abstract {
	private $_valid_tipos;
	
	function __construct() {
		$this->_data=array(
			'tipo_identificacion'=>null,
			'identificacion'=>null,
			'primer_nombre'=>null,
			'segundo_nombre'=>null,
			'primer_apellido'=>null,
			'segundo_apellido'=>null,
			'rel_tipo_identificacion'=>null,
			'rel_identificacion'=>null,
			'rel_tipo_relacion'=>null,
			'telefonos'=>array(),
			'medios_contacto'=>array(),
			'direcciones'=>array()
		);
		
		$this->_valid_tipos=$this->getEnumValues('enum_tipo_identificacion');
	}
	
	function add_tel($tel_number) {
		$tel = new CargaModelo_Item_Telefono();
		$tel->numero = $tel_number;
		$this->_data['telefonos'][] = $tel;
	}
	
	function add_medio_contacto($tipo_medio,$contenido) {
		if ($tipo_medio=='TELEFONO') {
			$this->add_tel($contenido);
		}else{
			$mc=new CargaModelo_Item_MedioContacto();
			$mc->set($tipo_medio,$contenido);
			$this->_data['medios_contacto'][]=$mc;
		}
	}

	function add_direccion($tipo_direccion,$direccion){
		$dir = new CargaModelo_Item_Direccion($tipo_direccion,$direccion);
		$this->_data['direcciones'][] = $dir;
	}
	
	function validate() {
		die('validate');
	}
	
	function __set($k,$v) {
		if($k=='tipo_identificacion') {
			if(!in_array($v,$this->_valid_tipos))
				throw new Exception('Tipo de identificación "'.$v.'" inválida. Solo se permiten {'.implode(',',$this->_valid_tipos).'}');
		}
		parent::__set($k,$v);
	}
}