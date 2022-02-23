<?php
class CargaModelo_Item_Cuenta extends CargaModelo_Item_Abstract {
	private $actualizaciones_mirror=array();
	
	function __construct() {
		$this->_data=array(
			'numero_cuenta'=>null,
			'valor_actual'=>null,
			'actualizaciones'=>array(),
			'persona_responsable'=>null,
			'otras_personas'=>array(),
			'cuotas'=>array()
		);
	}
	
	function pushCuota($cuota) {
		if(!is_object($cuota))
			throw new Exception('$cuota debe ser una clase que extienda CargaModelo_Item_Abstract');
		$parents=class_parents($cuota);
		if(!in_array('CargaModelo_Item_Abstract',$parents))
			throw new Exception('$cuota debe ser una clase que extienda CargaModelo_Item_Abstract');
		$this->_data['cuotas'][]=$cuota;
	}

	function add_cuota($cuota) {
		$aux = new CargaModelo_Item_Cuota();
		foreach ($cuota as $atrib => $valor){
			$aux->set($atrib,$valor);
		}
		$this->pushCuota($aux);
	}

	function pushActualizacion($actualizacion) {
		if(!is_object($actualizacion))
			throw new Exception('$actualizacion debe ser una clase que extienda CargaModelo_Item_Abstract');
		$parents=class_parents($actualizacion);
		if(!in_array('CargaModelo_Item_Abstract',$parents))
			throw new Exception('$actualizacion debe ser una clase que extienda CargaModelo_Item_Abstract');
		
		$this->_data['actualizaciones'][]=$actualizacion;
		$this->actualizaciones_mirror[]=$actualizacion;
	}
	
	function add_actualizacion($tipo,$valor,$fecha,$hora='00:00:00') {
		$aux=new CargaModelo_Item_CuentaActualizacion();
		$aux->set($tipo,$valor,$fecha,$hora);
		$this->pushActualizacion($aux);
	}
	
	function pushOtraPersona($persona,$tipo) {
		if(!is_object($persona))
			throw new Exception('$persona debe ser una clase que extienda CargaModelo_Item_Persona');
		$parents=class_parents($persona);
		if(!is_a($persona,'CargaModelo_Item_Persona'))
			throw new Exception('$persona debe ser una instancia de la clase CargaModelo_Item_Persona');
		$this->_data['otras_personas'][]=array(
			'tipo'=>$tipo,
			'persona'=>$persona
		);
	}
	
	function setResponsable($persona) {
		if(!is_object($persona))
			throw new Exception('$persona debe ser una clase que extienda CargaModelo_Item_Persona');
		$parents=class_parents($persona);
		if(!is_a($persona,'CargaModelo_Item_Persona'))
			throw new Exception('$persona debe ser una instancia de la clase CargaModelo_Item_Persona');
		$this->_data['persona_responsable']=$persona;
	}

	function __set($k,$v) {
		if($k=='actualizaciones')
			throw new Exception('Para agregar actualizaciones debe utilizar el metodo pushActualizacion($actualizacion)');
		parent::__set($k,$v);
		
	}
	
	function __get($k) {
		if($k=='actualizaciones') {
			if($this->actualizaciones_mirror!=$this->_data['actualizaciones'])
				throw new Exception('Para agregar actualizaciones debe utilizar el metodo pushActualizacion($actualizacion)');
		}
		return parent::__get($k);
	}
	
}