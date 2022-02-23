<?php
class CargaModelo_Item_Gestion extends CargaModelo_Item_Abstract {
	function __construct() {
		$this->_data=array(
			'id_cuenta'=>null,
			'cuenta'=>null,
			'fecha_inicio'=>null,
			'telh_id'=>null,
			'user_name'=>null,
			'tel_number'=>null,
			'id_tipificacion'=>null,
			'fecha_fin'=>null,
			'servidor'=>null,
			'observacion'=>null,
			'fecha_compromiso'=>null,
			'monto_compromiso'=>null,
			'ip_cliente'=>null,
			'email'=>null,
			'latitud'=>null,
			'longitud'=>null,
			'direccion'=>null
		);
	}
	
	final function validate() {
		if(is_null($this->id_cuenta) && is_null($this->cuenta))
			throw new Exception('Debe indicar id_cuenta o cuenta '.print_arr($this,true));
		if(is_null($this->fecha_inicio))
			throw new Exception('Debe indicar fecha inicio '.print_arr($this,true));
		if(is_null($this->fecha_fin))
			throw new Exception('Debe indicar fecha fin '.print_arr($this,true));
		if(is_null($this->user_name))
			throw new Exception('Debe indicar usuario '.print_arr($this,true));
		if(is_null($this->tel_number))
			throw new Exception('Debe indicar número telefónico '.print_arr($this,true));
		if(is_null($this->id_tipificacion))
			throw new Exception('Debe indicar id tipificacion '.print_arr($this,true));
		
		return true;
	}
	
	function __set($k,$v) {
		if($k=='fecha_compromiso') {
			if(!preg_match('#^\d{4}-\d{2}-\d{2}$#',$v))
				throw new Exception('Fecha inválida "'.$v.'"');
			parent::__set($k,$v);
		}else if($k=='monto_compromiso') {
			if(!preg_match('#^\d+(\.\d+)?$#',$v))
				throw new Exception('Monto compromiso inválido "'.$v.'"');
			parent::__set($k,$v);	
		}else{
			parent::__set($k,$v);
		}
	}

}