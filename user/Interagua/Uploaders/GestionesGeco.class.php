<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class UploadGestionesFer implements CargaModelo_Gestiones_Interface, Iterator {
	private $tipif_map=array(
		'BUZON DE VOZ'=>'29',
		'CLIENTE OCUPADO'=>'30',
		'COMPROMISO DE PAGO'=>'6',
		'CUELGA LLAMADA'=>'31',
		'DESCONOCE DEUDA'=>'32',
		'ENVIO IVR'=>'12',
		'ENVIO SMS'=>'28',
		'FALLECIDO'=>'8',
		'INFORMA PAGO REALIZADO'=>'3',
		'MENSAJE A TERCEROS'=>'10',
		'NEGATIVA DE PAGO'=>'7',
		'NO CONTESTA'=>'19',
		'NUMERO EQUIVOCADO'=>'18',
		'POSTERGA PAGO'=>'34',
		'REALIZO CONVENIO'=>'35',
		'SOLICITA REFINANCIA'=>'38',
		'TELEFONO DANADO'=>'39',
		'TELEFONO NO EXISTE'=>'40'	
	);
	function __construct($id_proceso) {
		$this->db=DB::getInstance();
		$this->odbc=new ODBC('Gecko','sgecoaccess','Sg2019');
		$this->q=$this->odbc->query('
			SELECT
				[Gestiones].[id_gestion],
				[Gestiones].[usuario],
				[Gestiones].[telefono],
				[Gestiones].[accion],
				[Gestiones].[respuesta],
				[Gestiones].[identificacion_deudor],
				[Gestiones].[observacion],
				[Gestiones].[fecha_gestion],
				[Gestiones].[fecha_compromiso],
				[Gestiones].[saldo],
				[Creditos].[numero_operacion]
			FROM
				[dbo].[Gestiones]
			JOIN [dbo].[Creditos] ON Gestiones.id_credito = Creditos.id_credito
			JOIN [dbo].[Deudores] ON Creditos.id_deudor = Deudores.id_deudor
			WHERE
				Creditos.cedente LIKE \'%INTERAGUA%\' AND [Gestiones].[fecha_gestion]<\'2019-11-28\'
				-- AND Creditos.numero_operacion=\'695599\'
		');
		$this->db->prepare('_get_id_cuenta_by_cuenta_proceso','SELECT get_id_cuenta_by_cuenta_proceso AS id_cuenta FROM public.get_id_cuenta_by_cuenta_proceso('.$id_proceso.',$1)');
		
	}
	
	function processRecord(&$line) {
		$gestion=new CargaModelo_Item_Gestion();
		$gestion->cuenta=$line['numero_operacion'];
		$q0=$this->db->execute('_get_id_cuenta_by_cuenta_proceso',array($line['numero_operacion']));
		if(is_null($q0->current()['id_cuenta'])) return null;
		
		$gestion->id_cuenta=$q0->current()['id_cuenta'];
		$gestion->fecha_inicio=$line['fecha_gestion'];
		$gestion->fecha_fin=$line['fecha_gestion'];
		$gestion->user_name=$line['usuario'];
		$gestion->tel_number=$line['telefono'];
		$gestion->observacion=$line['observacion'];
		$gestion->id_tipificacion=$this->tipif_map[$line['respuesta']];
		if($gestion->id_tipificacion==6) {
			if(!empty($line['fecha_compromiso'])) {
				$line['fecha_compromiso']=explode(' ',$line['fecha_compromiso']);
				$gestion->fecha_compromiso=$line['fecha_compromiso'][0];
				if(!preg_match('#^\d+(\.\d+)?$#',$line['saldo'])){
					$aux_valor=explode('.', $line['saldo']);
					if (count($aux_valor)>2){
						$line['saldo']='0.00';
					}elseif ($aux_valor[0]==''){
						$line['saldo']='0.'.$aux_valor[1];
					}
				}
				$gestion->monto_compromiso=$line['saldo'];
			}
		}
		return $gestion;
	}

	function pushFile($filename,$filepath) {
		
	}

	function getFiles() {
		return array();
	}
	
	function next() {
		$this->q->next();
	}
	
	function current() {
		return $this->processRecord($this->q->current());
	}
	
	function rewind() {
		$this->q->rewind();
	}
	
	function key() {
		return $this->q->key();
	}
	
	function valid() {
		return $this->q->valid();
	}

	
}

class GestionesGeco extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Interagua - Gestiones Geco';
	}
	
	function getDescripcion() {
		return 'Interagua - Gestiones Geco';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		return new UploadGestionesFer($__data['id_proceso']);

	}
}
