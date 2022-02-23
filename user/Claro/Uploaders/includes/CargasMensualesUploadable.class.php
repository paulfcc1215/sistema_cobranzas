<?php
class CargasMensualesUploadable extends CargaModelo_Uploadable_Abstract {
	function processRecord(&$line) {
		$skip=array(
			'cuenta',
			'total_deuda',
			'ruc',
			'nombre',
			'apellidos',
			'telefono',
			'telefono1',
			'telefono2'
		);
		$ret=array(
			//'line'=>$line,
			'cuenta'=>new CargaModelo_Item_Cuenta(),
			'otros_datos'=>array()
		);
		$ret['cuenta']->numero_cuenta=$line[1]['cuenta'];
		$ret['cuenta']->valor_actual=$line[1]['total_deuda'];		
		
		
		
		$ret['cuenta']->persona_responsable=new CargaModelo_Item_Persona();
		$ret['cuenta']->persona_responsable->tipo_identificacion='CEDULA';
		$ret['cuenta']->persona_responsable->identificacion=$line[1]['ruc'];
		$ret['cuenta']->persona_responsable->primer_nombre=$line[1]['nombres'];
		$ret['cuenta']->persona_responsable->primer_apellido=$line[1]['apellidos'];
		
		
		$ret['cuenta']->persona_responsable->add_medio_contacto('DIRECCION',$line[1]['direccion'].' '.$line[1]['direccion2']);

		if($line[1]['telefono']!='') {
			$ret['cuenta']->persona_responsable->add_tel($line[1]['telefono']);
		}
		if($line[1]['telefono1']!='') {
			$ret['cuenta']->persona_responsable->add_tel($line[1]['telefono1']);
		}
		if($line[1]['telefono2']!='') {
			$ret['cuenta']->persona_responsable->add_tel($line[1]['telefono2']);
		}
		




		foreach($line[1] as $k=>$v) {
			if(!in_array($k,$skip)) {
				$ret['otros_datos'][$k]=$v;
			}
		}
		
		return $ret;
		
	}
	
}