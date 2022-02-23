<?php
class SiPlaMWS {
	
	private $wsdl;
	private $return_array;

	function __construct($wsdl=_WS_SIPLAM,$return_array=true){
		$this->wsdl=$wsdl;
		$this->return_array=$return_array;
	}

	function sendRequest($funcion,$parametros=array()) {
		$client = new SoapClient($this->wsdl);
		if(!is_array($parametros))
			throw new Exception("$parametros debe ser un array asociativo. ej. $parametros = array('parametro_1'=>'valor_1', ...)");
		
		if (empty($parametros)){
			$result = $client->$funcion();
		}else {
			$result = $client->$funcion($parametros);
		}

		if ($this->return_array) {
			$result = json_decode(json_encode($result), true);
			$result = $result['return'];
		}
		return $result;
	}

	function setWsdl($wsdl){
		$this->wsdl=$wsdl;
	}
	function setParametros($parametros){
		if(is_array($parametros))
			$this->parametros=$parametros;
	}

}