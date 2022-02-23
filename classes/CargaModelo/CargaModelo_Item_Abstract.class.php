<?php
abstract class CargaModelo_Item_Abstract implements CargaModelo_Item_Interface {
	protected $_data;
	
	function __construct() {
		$this->_data=array();
	}
	
	function __set($k,$v) {
		if(!array_key_exists($k,$this->_data))
			throw new Exception('Llave '.$k.' inválida para clase "'.get_called_class().'"<hr>Solo se permiten estos campos: '.print_arr(array_keys($this->_data),true));
		$this->_data[$k]=$v;
		
	}
	
	function __get($k) {
		if(!array_key_exists($k,$this->_data))
			throw new Exception('Llave '.$k.' inválida para clase "'.get_called_class().'"<hr>Solo se permiten estos campos: '.print_arr(array_keys($this->_data),true));
		return $this->_data[$k];
	}
	
	protected function &getEnumValues($enum_name) {
		GLOBAL $_CACHE;
		if(!array_key_exists('enum_'.$enum_name,$_CACHE) || empty($_CACHE['enum_'.$enum_name])) {
			$db=DB::getInstance();
			$query='SELECT
					*
				FROM
					pg_enum
				WHERE
					enumtypid = (
						SELECT
							oid
						FROM
							pg_type
						WHERE
							typname = \''.$db->escape($enum_name).'\'
					)
			';
			foreach($db->query($query) as $t) {
				$_CACHE['enum_'.$enum_name][]=$t['enumlabel'];
			}
			
		}
		return $_CACHE['enum_'.$enum_name];
	}
	
	function validate() {
	}
	
	function __toString() {
		return print_arr($this->_data);
		$ret=array();
		foreach($this->_data as $k=>$v) {
			switch(gettype($this->_data[$k])) {
				case 'NULL':
					$ret[$k]='NULL';
				break;
				case 'array':
					$ret[$k]=print_r($this->_data[$k],true);
				break;
				case 'object':
					$ret[$k]=$this->_data[$k]->__toString();
				break;
				default:
					$ret[$k]=$this->_data[$k];
				break;
			}
		}
		
		return print_arr($ret,true);
	}
	
	function getData() {
		return $this->_data;
	}
}