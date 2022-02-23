<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Cartera_Banco_Guayaquil extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $header;
	private $data;

	private $ptr=0;

	function __construct($fpath) {
		$required_columns=array(
			'numero_de_operacion',
			'apellidos_y_nombres_del_deudor',
			'cedula_o_ruc',
			'ciudad_de_residencia_del_cliente',
			'ciudad_de_ubicacion_de_agencia_que_otorga_la_operacion',
			'monto_inicial_del_credito',
			'saldo_de_capital_vencido',
			'interes_normal_vencido_y_por_vencer',
			'interes_de_mora_vencido',
			'no_total_de_cuotas_de_la_operacion',
			'no_de_cuotas_canceladas',
			'no_de_cuotas_vencidas',
			'no_de_cuotas_por_vencer',
			'dias_de_mora',
			'periodicidad',
			'tipo_de_credito',
			'tipo_de_garantia',
			'calificacion_de_cartera',
			'estado_juridico_de_la_operacion',
			'tipo_deudor',
			'total_deuda',
			'pagos',
			'observaciones'
		);

		if (!mb_check_encoding(file_get_contents($fpath),'UTF-8')) throw new Exception('El archivo debe estar codificado en UTF-8');
		$fhdl=fopen($fpath,'rb');
		$header=fgetcsv($fhdl,null,_UPLOADS_SEPARATOR,_UPLOADS_TEXT_QUALIFIER);
		if (count(array_unique($header))!=count($header))
			throw new Exception('El archivo subido contiene una o más columnas repetidas ('.print_r($header,true).')');

		$faltan=array();
		foreach($required_columns as $rc) {
			if(!in_array($rc,$header)) {
				$faltan[]=$rc;
			}
		}

		if (!empty($faltan)) throw new Exception('Faltan las siguientes columnas requeridas: {'.implode(', ',$faltan).'}');

		fclose($fhdl);
		$csv = new Helpers_CSV($fpath);
		$data_agrupada=array();
		foreach ($csv as $num_linea=>$linea) {
			$data_agrupada[$linea['numero_de_operacion']][]=$linea;
		}
		$this->header = $header;
		$this->data = $data_agrupada;
		$this->numRows = count($this->data);
		$this->keys=array_keys($this->data);
	}
	
	function parseTelefonos($telefonos) {
		$ret=array();
		$aux=array();
		if(preg_match_all('#[^\d]#',$telefonos,$matches)) {
			$matches=array_unique($matches[0]);
			foreach($matches as $m) {
				foreach(explode($m,$telefonos) as $t) {
					$t=trim($t);
					if(preg_match('#^\d+$#',$t)) $aux[]=$t;
				}
			}
			$aux=array_unique($aux);
		}else{
			$aux[]=$telefonos;
		}
		foreach($aux as $a) {
			$a=ltrim($a,'0');
			if(strlen($a)==7) $a='4'.$a;
			if(!preg_match('#^9[1-9]\d{7}$#',$a) && !preg_match('#^[2-7]\d{7}$#',$a)) {
				continue;
			}
			if(preg_match('#(.)\1{5}#',$a)) continue;
			$ret[]='0'.$a;
		}
		return $ret;
	}
	
	function processRecord(&$line) {

		$ret=array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);
		$cuenta = new CargaModelo_Item_Cuenta();
		foreach ($line as $registro) {

			if (trim($registro['numero_de_operacion'])=='') throw new Exception('Número de operacion vacía en línea '.$this->ptr);
			if (trim($registro['cedula_o_ruc'])=='') throw new Exception('Cedula vacía en línea '.$this->ptr);

			$cuenta->numero_cuenta = $registro['numero_de_operacion'];
			$cuenta->valor_actual = round(str_replace(",","",$registro['total_deuda']),2);
			
			$cuenta->persona_responsable=new CargaModelo_Item_Persona();
			$cuenta->persona_responsable->tipo_identificacion='CEDULA';
			$cuenta->persona_responsable->identificacion=$registro['cedula_o_ruc'];
			$cuenta->persona_responsable->primer_nombre=$registro['apellidos_y_nombres_del_deudor'];
		}
		$ret['cuenta']=$cuenta;
		$ret['otros_datos']['ciudad_de_residencia_del_cliente']=$line[0]['ciudad_de_residencia_del_cliente'];
		$ret['otros_datos']['ciudad_de_ubicacion_de_agencia_que_otorga_la_operacion']=$line[0]['ciudad_de_ubicacion_de_agencia_que_otorga_la_operacion'];
		$ret['otros_datos']['monto_inicial_del_credito']=$line[0]['monto_inicial_del_credito'];
		$ret['otros_datos']['saldo_de_capital_vencido']=$line[0]['saldo_de_capital_vencido'];
		$ret['otros_datos']['interes_normal_vencido_y_por_vencer']=$line[0]['interes_normal_vencido_y_por_vencer'];
		$ret['otros_datos']['interes_de_mora_vencido']=$line[0]['interes_de_mora_vencido'];
		$ret['otros_datos']['no_total_de_cuotas_de_la_operacion']=$line[0]['no_total_de_cuotas_de_la_operacion'];
		$ret['otros_datos']['no_de_cuotas_canceladas']=$line[0]['no_de_cuotas_canceladas'];
		$ret['otros_datos']['no_de_cuotas_vencidas']=$line[0]['no_de_cuotas_vencidas'];
		$ret['otros_datos']['no_de_cuotas_por_vencer']=$line[0]['no_de_cuotas_por_vencer'];
		$ret['otros_datos']['dias_de_mora']=$line[0]['dias_de_mora'];
		$ret['otros_datos']['periodicidad']=$line[0]['periodicidad'];
		$ret['otros_datos']['tipo_de_credito']=$line[0]['tipo_de_credito'];
		$ret['otros_datos']['tipo_de_garantia']=$line[0]['tipo_de_garantia'];
		$ret['otros_datos']['calificacion_de_cartera']=$line[0]['calificacion_de_cartera'];
		$ret['otros_datos']['estado_juridico_de_la_operacion']=$line[0]['estado_juridico_de_la_operacion'];
		$ret['otros_datos']['tipo_deudor']=$line[0]['tipo_deudor'];
		$ret['otros_datos']['total_deuda']=$line[0]['total_deuda'];
		$ret['otros_datos']['pagos']=$line[0]['pagos'];
		$ret['otros_datos']['observaciones']=$line[0]['observaciones'];
		return $ret;
	}

	// Iterator
	function next() {
		$this->ptr++;
	}
	
	function current() {
		//$this->data[$this->keys[$this->ptr]]['_processed_key']=$this->keys[$this->ptr];
		return $this->processRecord($this->data[$this->keys[$this->ptr]],$this->keys[$this->ptr]);
	}
	
	function rewind() {
		$this->ptr=0;
	}
	
	function key() {
		return $this->keys[$ptr];
	}
	
	function valid() {
		return $this->ptr<$this->numRows;
	}
	
}

class CarteraVencidaBcoGuayaquil extends CargaModelo_Handler_Abstract {

	function getTipoBase() {
		return 'Cartera';
	}
	
	function getDescripcion() {
		return 'Cartera';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader=new Cartera_Banco_Guayaquil(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '2':
				if($_FILES['data']['error']!='0') throw new Exception('Error '.$_FILES['data']['error'].' al cargar archivo');
				$aux=$SM->carga_process;
				$aux['source_file']=uniqid();
				$aux['original_filename']=$_FILES['data']['name'];
				$SM->carga_process=$aux;
				if(!move_uploaded_file($_FILES['data']['tmp_name'],_TMP_UPLOAD_FOLDER.'/'.$aux['source_file'])) throw new Exception('Error al mover archivo subido');
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
				die();
			break;
			
			case '1':
				$__data['_T']['maincontent']='<h1>Carga de Carteras - Banco de Guayaquil</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				Seleccione el archivo:
				<input type="file" name="data">
				<br>
				<button class="btn btn-primary">Cargar</button>
				</form>';
			break;
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'Modelo_Base_Bco_Guayaquil.txt'=>''
		);
		if($with_data) {
			$ret['Modelo_Base_Bco_Guayaquil.txt']=file_get_contents(dirname(__FILE__).'/Modelo_Base_Bco_Guayaquil.txt');
		}
		return $ret;
	}

}
