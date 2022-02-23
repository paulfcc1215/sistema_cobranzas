<?php
class Uploadable implements CargaModelo_Uploadable_Interface, Iterator {
	private $db;
	private $res;
	private $ptr;
	function __construct($db) {
		$this->db=$db;
		$this->res=array(
			'cuentas'=>null
		);
	}
	
	function processRecord(&$line) {
		try {
			//if($line['crd_id']!=321054) return null;
			$cuota=$this->db->query('SELECT * FROM db_cuota WHERE crd_id='.$line['crd_id']);
			if($cuota->numRows()!=1)
				throw new Exception('Error hay mas de una cuota para crd_id='.$line['crd_id']);
			
			$aux=$this->db->query('SELECT * FROM db_contacto WHERE concli=\''.$line['concli'].'\'');
			$persona=new CargaModelo_Item_Persona();
			$persona->primer_nombre=$aux->current()['ncfcli'];
			$persona->tipo_identificacion='CEDULA';
			$persona->identificacion=$aux->current()['concli'];
			$aux2=$aux->current()['idecli'];
			$aux=$this->db->query('select * from db_telefono where idecli=\''.$aux2.'\'');
			foreach($aux as $tel) {
				if(Helpers::telefonoValido($tel['numtel']))
					$persona->add_tel($tel['numtel']);
			}
			
			$ret=array(
				'cuenta'=>new CargaModelo_Item_Cuenta(),
				'otros_datos'=>array()
			);
			
			$ret['cuenta']->numero_cuenta=$line['crd_hsc_credito'];
			$ret['cuenta']->valor_actual=$cuota->current()['cta_saldo_pendiente'];
			$ret['cuenta']->setResponsable($persona);
			
			$ret['otros_datos']['producto']=$line['crd_producto'];
			$ret['otros_datos']['estado_producto']=$line['crd_estado_producto'];
			$ret['otros_datos']['ciclo']=$cuota->current()['cta_ciclo'];
			$ret['otros_datos']['num_cuota']=$cuota->current()['cta_num_cuota'];
			
			$aux_pagos=$this->db->query('SELECT * FROM db_pago_credito WHERE crd_id=\''.$line['crd_id'].'\'');
			foreach($aux_pagos as $pago) {
				/*
				function add_actualizacion($tipo,$valor,$fecha,$hora='00:00:00') {
					$aux=new CargaModelo_Item_CuentaActualizacion();
					$aux->set($tipo,$valor,$fecha,$hora);
					$this->pushActualizacion($aux);
				}
				*/
				if(!is_numeric($pago['pgcr_valor'])) continue;
				$pago['pgcr_valor']=(float)$pago['pgcr_valor'];
				if($pago['pgcr_valor']<0.000001) continue;
				$pago['pgcr_valor']=(float)$pago['pgcr_valor'];
				if($pago['pgcr_valor']>0) {
					$pago['pgcr_valor']*=-1;
				}
				
				
				$ret['cuenta']->add_actualizacion('PAGO',$pago['pgcr_valor'],$pago['pgcr_fecha_pago']);
				
			}
			
			/*
			$aux=$this->db->query('SELECT * FROM "public"."db_detalle_campana" WHERE crd_id=\''.$line['crd_id'].'\'')->current();
			$aux_gestion=$this->db->query('SELECT * FROM db_gestion WHERE (cdtcam = \''.$aux['cdtcam'].'\' and coddeu = \''.$aux['coddeu'].'\'');
			if(
			*/
			
			//$this->db->query('SELECT * FROM db_gestion WHERE id_
		}catch(Exception $e) {
			$str=$e->getMessage();
			$str.='<h2>Line</h2>'.print_arr($line,true);
			$str.='<h2>Pago</h2>'.print_arr($pago,true);
			throw new Exception($str);
		}
		
		return $ret;
		
    }

	function pushFile($filename,$filepath) {
    }

	function getFiles() {
		return array();
    }


	// Iterator
	function next() {
		$this->ptr++;
		$this->res['cuentas']->next();
    }

	function current() {
		return $this->processRecord($this->res['cuentas']->fetchOne());
    }

	function rewind() {
		$this->res['cuentas']=$this->db->query('
			SELECT * FROM db_credito
				WHERE crd_codper :: TEXT = (
					SELECT
					dbCampana.codper :: TEXT
					FROM
					db_campana dbCampana
					WHERE
					dbCampana.codcam = db_credito.codcam
				)
				/*
				AND
				crd_id in (
				select crd_id from db_pago_credito
				)
				*/
				-- AND crd_id=\'311400\'
		
		');
		$this->ptr=1;
    }

	function key() {
		return $this->ptr;
    }

	function valid() {
		return ($this->ptr <= $this->res['cuentas']->numRows());
    }

	
}



class DelSise extends CargaModelo_Handler_Abstract {
	function getDescripcion() {
		return '';
	}
	
	function getTipoBase() {
		return 'Import from SISE 180.230';
	}
	
	function __construct() {
		DB::connect('pgsql',array(
			'host'=>'192.168.180.230',
			//'host'=>'127.0.0.1',
			'user'=>'postgres',
			'password'=>'postgres',
			'dbname'=>'sise',
		),'sise');
	}
	
	function execute($step,&$__data) {
		return new Uploadable(DB::getInstance('sise'));
	}
}
