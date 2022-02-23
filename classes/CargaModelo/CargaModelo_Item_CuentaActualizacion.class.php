<?php
class CargaModelo_Item_CuentaActualizacion extends CargaModelo_Item_Abstract {
	private $_valid_tipos=array();
	
	function __construct() {
		/*
		GLOBAL $_CACHE;
		if(!array_key_exists('cuenta_actualizacion_tipo',$_CACHE) || empty($_CACHE['cuenta_actualizacion_tipo'])) {
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
							typname = \'enum_tipo_actualizacion\'
					);
			';
			foreach($db->query($query) as $t) {
				$_CACHE['cuenta_actualizacion_tipo'][]=$t['enumlabel'];
			}
			
		}
		*/
		$this->_valid_tipos=$this->getEnumValues('enum_tipo_actualizacion');
		
		$this->_data=array(
			'tipo_actualizacion'=>null,
			'valor'=>null,
			'fecha_actualizacion'=>null,
			'hora_actualizacion'=>null
		);
		
		
	}
	
	function set($tipo,$valor,$fecha,$hora='00:00:00') {
		if(!in_array($tipo,$this->_valid_tipos))
			throw new Exception(get_called_class().' - El tipo "'.$tipo.'" no es válido. Debe utilizar alguno de los siguientes: {'.implode(',',$this->_valid_tipos).'}');

		if(!in_array(substr($valor,0,1),array('+','-'))) $valor='+'.$valor;
		
		if(!preg_match('#^(\-|\+)?\d+(\.\d+)?$#',$valor)) 
			throw new Exception(get_called_class().' - El valor indicado "'.$valor.'" no es válido. Debe ser un flotante, con punto (.) como separador de decimales');
			
		$this->_data['tipo_actualizacion']=$tipo;
		

		$this->_data['valor']=$valor;
		
		if(!preg_match('#^\d{2}:\d{2}:\d{2}$#',$hora))
			throw new Exception(get_called_class().' - La hora indicada no es válida');
		$hora=explode(':',$hora);
		if($hora[0]+0<0 || $hora[0]+0>24)
			throw new Exception(get_called_class().' - La hora indicada no es válida (Hora fuera de rango)');
		if($hora[1]+0<0 || $hora[1]+0>59)
			throw new Exception(get_called_class().' - La hora indicada no es válida (Minutos fuera de rango)');
		if($hora[2]+0<0 || $hora[2]+0>59)
			throw new Exception(get_called_class().' - La hora indicada no es válida (Segundos fuera de rango)');
		$hora=implode(':',$hora);
		$this->_data['hora_actualizacion']=$hora;

		if(!preg_match('#^\d{4}-\d{2}-\d{2}$#',$fecha))
			throw new Exception(get_called_class().' - La fecha debe estar en formato año-mes-día');
		$fecha=explode('-',$fecha);
		
		if(!checkdate($fecha[1],$fecha[2],$fecha[0]))
			throw new Exception(get_called_class().' - La fecha indicada no es válida');
		$fecha=implode('-',$fecha);
		
		$aux=new DateTime($fecha.' '.$hora);
		$now=new DateTime();
		$diff=$now->diff($aux);
		if($diff->invert!=1)
			throw new Exception(get_called_class().' - La fecha de actualización no puede estar en el futuro');
		$this->_data['fecha_actualizacion']=$fecha;
		
		if($tipo=='AJUSTE-' && $valor>0) {
			throw new Exception(get_called_class().' - No pueden haber AJUSTE- con valor > 0');
		}else if($tipo=='AJUSTE+' && $valor<0) {
			throw new Exception(get_called_class().' - No pueden haber AJUSTE+ con valor < 0');
		}
		


	}
	
	function __set($k,$v) {
		throw new Exception('Debe utilizar el método '.get_called_class().'::set');
	}
}