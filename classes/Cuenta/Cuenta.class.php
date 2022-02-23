<?php
class Cuenta {
	private $db;
	private function __construct($id_cuenta) {
		$this->db=DB::getInstance();
		$query='SELECT * FROM cuentas.cuenta WHERE id_cuenta=\''.$db->escape($id_cuenta).'\'';
		$cuenta=$db->query($query);
		if($cuenta->numRows()!=1)
			throw new Exception('Id de cuenta '.$id_cuenta.' no existe');
		$cuenta=$cuenta->current();
		
		$query='SELECT * FROM cuentas.cuenta_responsable cr JOIN personas.persona p USING (id_persona) WHERE id_cuenta='.$cuenta->id_cuenta;
		foreach($db->query($query) as $p) {
			//$cuenta['responsable']=
		}
	
	
	}
	
	
	
	static function getByProcesoCuenta($id_proceso,$cuenta) {
		$query='SELECT * FROM cuentas.cuenta WHERE id_proceso=\''.$db->escape($id_proceso).'\' AND cuenta=\''.$db->escape($cuenta).'\'';
	
	}
	
	static function getById() {
		
	}
}