<?php
class CuentasUploadable extends CargaModelo_Uploadable_Abstract {
	
	function processRecord(&$line) {
		$ret=array(
			'cuenta'=>new CargaModelo_Item_Cuenta(),
			'otros_datos'=>array()
		);
		$ret['cuenta']->numero_cuenta=$line[1]['contrato'];
		$ret['cuenta']->valor_actual=$line[1]['saldo_pendiente'];
		
		$aux=new CargaModelo_Item_Persona();
		$aux->tipo_identificacion='CEDULA';
		$aux->identificacion=$line[1]['identificacion'];
		
		$line[1]['telefono']=trim($line[1]['telefono']);
		if($line[1]['telefono']!='' && Helpers::telefonoValido($line[1]['telefono'])) {
			$aux->add_tel($line[1]['telefono']);
		}
		if($line[1]['correo']!='') {
			$aux->add_medio_contacto('CORREO',$line[1]['correo']);
		}
		$ret['cuenta']->persona_responsable=$aux;
		$ret['otros_datos']=$line[1];
		
		return $ret;
	}
	
}