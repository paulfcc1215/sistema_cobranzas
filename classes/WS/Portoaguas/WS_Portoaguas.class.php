<?php

class WS_Portoaguas {
    private $soap=null;
    function __construct() {
		$context = stream_context_create([
			'ssl' => [
				// set some SSL/TLS specific options
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		]);
		$options = array(
			'cache_wsdl' => WSDL_CACHE_NONE,
			'version' => SOAP_1_1,
			'stream_context' => $context
		);

		$this->soap = new SoapClient('https://sw.portoaguas.gob.ec:443/PORTOAGUASEP/electrofacturacion?wsdl',$options);
		// $this->soap = new SoapClient('https://sw.portoaguas.gob.ec/PORTOAGUASEP/electrofacturacion?wsdl',$options);
    }    
    
    function consulta_deuda($cuenta){
		try{
			$p = '<valores><cuenta>'.$cuenta.'</cuenta></valores>';
			$parametros = array(
				'parametro' => base64_encode($p)
			);
			$result = $this->soap->WSGETDEUDAPORTOAGUAS($parametros);
			$result = json_decode(json_encode($result),true);
			$result = $result['formato'];
			$result = base64_decode($result);
			$result = new SimpleXMLElement($result);
			$result = json_decode(json_encode($result),true);
			return $result;
		}catch(SoapFault $e){
			return false;
			// echo $e->getMessage();
		}
    }
}