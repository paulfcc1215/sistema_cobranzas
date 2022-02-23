<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class UploadGestionesGeco implements CargaModelo_Gestiones_Interface, Iterator {
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
		'TELEFONO NO EXISTE'=>'40',
		'SI ACEPTA'=>'50'
	);
	function __construct($id_proceso,$_cedente,$f_desde,$f_hasta) {
		$this->db=DB::getInstance();
		$this->odbc=new ODBC('Gecko','sgecoaccess','Sg2019');
		$q='
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
				Creditos.cedente = \''.$_cedente.'\' AND [Gestiones].[fecha_gestion] BETWEEN \''.$f_desde.'\' AND \''.$f_hasta.'\'';
		$this->q=$this->odbc->query($q);
		$this->proceso = $id_proceso;
		$this->db->prepare('_get_id_cuenta_by_cuenta_proceso','SELECT get_id_cuenta_by_cuenta_proceso AS id_cuenta FROM public.get_id_cuenta_by_cuenta_proceso('.$id_proceso.',$1)');
	}

	function processRecord(&$line) {
		$q='SELECT * FROM gestiones.gestion g JOIN cuentas.cuenta c USING (id_cuenta) WHERE c.cuenta=\''.$line['numero_operacion'].'\' AND g.user_name=\''.$line['usuario'].'\' AND g.fecha_inicio=\''.$line['fecha_gestion'].'\' AND c.id_proceso='.$this->proceso.' AND g.tel_number=\''.$line['telefono'].'\'';
		$q0=$this->db->query($q);
		if (!is_null($q0->current()['id_cuenta'])) return null;

		$gestion=new CargaModelo_Item_Gestion();
		$gestion->cuenta=$line['numero_operacion'];
		$q1=$this->db->execute('_get_id_cuenta_by_cuenta_proceso',array($line['numero_operacion']));
		if(is_null($q1->current()['id_cuenta'])) return null;

		$gestion->id_cuenta=$q1->current()['id_cuenta'];
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

class GenericoGestionesGeco extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Generico - Gestiones Geco';
	}

	function getDescripcion() {
		return 'Generico - Gestiones Geco';
	}

	function execute($step, &$__data) {

		switch ($step) {
			case '2':
				if($_POST['cedente']=='') throw new Exception('No ha seleccionado cedente');
				if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_POST['fecha_desde'])) throw new Exception('Fecha desde inválida');
				if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_POST['fecha_hasta'])) throw new Exception('Fecha hasta inválida');
				return new UploadGestionesGeco($__data['id_proceso'],$_POST['cedente'],Helpers::dmy2ymd($_POST['fecha_desde']),Helpers::dmy2ymd($_POST['fecha_hasta']));
			break;
			
			case '1':
				$odbc_geco = new ODBC('Gecko','sgecoaccess','Sg2019');
				$__data['_T']['maincontent']='<h1>Seleccione parámetros de migración</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2')).'" enctype="multipart/form-data">
					<br><br>Seleccione Cedente:<br>
					<select class="form-control" name="cedente" id="cedente" onchange="change_cedente()">
					<option value="">Seleccione Cedente...</option>';
					$q='SELECT DISTINCT(cr.cedente) FROM dbo.Deudores d JOIN dbo.Creditos cr on (d.id_deudor=cr.id_deudor)';
					foreach ($odbc_geco->query($q) as $value) {
					$__data['_T']['maincontent'].='<option value="'.$value['cedente'].'">'.$value['cedente'].'</option>';
					}
					$__data['_T']['maincontent'].='
					</select>
					<br><br><br>
					Desde:<input type="text" name="fecha_desde" class="fecha" value="'.date('d/m/Y').'">
					<br><br>
					Hasta:<input type="text" name="fecha_hasta" class="fecha" value="'.date('d/m/Y').'">
				<br><br><br>
				<button class="btn btn-primary">Cargar</button>
				</form>';
				$__data['_T']['bottom_jscript'].='
				';
			break;
		}
		

	}
}