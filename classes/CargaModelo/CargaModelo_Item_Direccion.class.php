<?php
class CargaModelo_Item_Direccion extends CargaModelo_Item_Abstract {
	private $_tipos_direccion;
	private $_tipos_ubicacion;

	/*
	// $tipo_direccion: enum (RESIDENCIA,TRABAJO,OTROS)
	// El key del arreglo corresponde al tipo_ubicación que debe existir en la tabla medios_contacto.tipo_ubicacion
	// El value corresponde al valor de la tipo de ubicacion
	// $direccion: array (
		// 'CIUDADELA' => 'OBRAS PUBLICAS',
		// 'CALLE_PRINCIPAL'=>'AV. 10 DE AGOSTO',
		// 'CALLE_SECUNDARIA'=>'CALLE JUAN PABLO SANZ',
		// 'NUMERACION'=>'N103-65',
	// )
	*/
	function __construct($tipo_direccion,$direccion) {
		
		$this->_tipos_direccion = $this->getEnumValues('enum_tipo_direccion');
		$db = DB::getInstance();
		$q = 'SELECT * FROM medios_contacto.tipo_ubicacion';
		foreach ($db->query($q) as $d){
			$this->_tipos_ubicacion[$d['id_tipo_ubicacion']] = $d['descripcion'];
		}
		if (!in_array($tipo_direccion,$this->_tipos_direccion)){
			throw new Exception(get_called_class().'::set - tipo_direccion "'.$tipo_direccion.'" no es válido. Debe utilizar uno de la lista {'.implode(',',$this->_tipos_direccion).'}');
		}
		$aux = array();
		foreach ($direccion as $tipo_ubicacion => $valor){
			if (!in_array($tipo_ubicacion,$this->_tipos_ubicacion)){
				throw new Exception(get_called_class().'::set - tipo_direccion "'.$tipo_ubicacion.'" no es válido. Debe utilizar uno de la lista {'.implode(',',$this->_tipos_ubicacion).'}');
			}
			$aux[array_search($tipo_ubicacion,$this->_tipos_ubicacion)] = $valor;
		}
		$this->_data['tipo_direccion'] = $tipo_direccion;
		$this->_data['direccion'] = $aux;
	}
	
	function validate() {
		die('validate');
	}
}