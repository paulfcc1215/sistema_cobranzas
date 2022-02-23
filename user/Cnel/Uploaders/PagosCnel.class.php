<?php

class PagosCnelCargador extends CargaModelo_Actualizaciones_Abstract implements CargaModelo_Actualizaciones_Interface, Iterator{

	private $ptr=0;
	private $data=array();


	function __construct($file_path) {
		// $file_struct = array(
		// 	'codigo'=>array(
		// 		'type'=>'number',
		// 		'required'=>true,
		// 		'char_size'=>10,
		// 	),
		// 	'cliente'=>array(),
		// 	'deuda_actual'=>array(
		// 		'type'=>'decimal',
		// 		'required'=>true,
		// 	),
		// 	'fact_pend'=>array(),
		// 	'fecha_saldo_deuda'=>array(),
		// 	'fecha_gestion'=>array(),
		// 	'recauda_efectivo'=>array(),
		// 	'recauda_conv_pago'=>array(),
		// 	'recauda_docmtos'=>array(),
		// 	'total_pagos_post_gestion'=>array(
		// 		'type'=>'decimal',
		// 	),
		// 	'fecha_pago'=>array(
		// 		'type'=>'date',
		// 	)
		// );
		$file_struct = array(
			'CODIGO:CNEL'=>array(
				'type'=>'number',
				'required'=>true,
				'char_size'=>10,
			),
			'RECAUDA_EFECTIVO_TOT'=>array(),
			'RECAUDA_CONV_PAGO_TOT'=>array(),
			'RECAUDA_DOCMTOS_TOT'=>array(),
			'FECHA_ULTIMO_PAGO'=>array(
				'type'=>'date',
			),
			'TOTAL(EFEC-CONV-DOC)'=>array(
				'type'=>'decimal',
			),
			'DEUDA_ACTUAL'=>array(
				'type'=>'decimal',
				'required'=>true,
			),
			'FACT_PEND_ACTUAL'=>array(),
		);
		$archivo = new Helpers_CSV($file_path);
		//validar cabecera de archivo
		if (!empty(array_diff(array_keys($file_struct),$archivo->getHeader()))) throw new Exception('Cabecera de archivo incorrecta, se espera ['.implode(',',array_keys($file_struct)).']');

		$result = array();
		foreach ($archivo as $num_linea => $linea) {
			
			//aplicar validaciones segun la estructura del archivo
			foreach ($linea as $campo => &$valor) {
				$valor = trim($valor);
				if ($file_struct[$campo]['required'] && $valor=='') throw new Exception('El campo: '.$campo.' está vacio en la linea '.$num_linea);
				if ($file_struct[$campo]['type']=='decimal'){
					$valor = str_replace(',','',$valor);
					$valor = str_replace('$','',$valor);
					if (!is_numeric($valor)){
						throw new Exception('El campo: '.$campo.' debe ser un valor décimal, tiene '.$valor.' en la linea '.$num_linea);
					}
				}
				if ($file_struct[$campo]['type']=='number'){
					if (!is_numeric($valor)){
						throw new Exception('El campo: '.$campo.' debe ser un valor numérico, tiene '.$valor.' en la linea '.$num_linea);
					}
				}
				if ($file_struct[$campo]['type']=='date' && $file_struct[$campo]['required']){
					if (preg_match('#^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$#',$valor)===0){
						throw new Exception('El campo: '.$campo.' no tiene formato de fecha yyyy-mm-dd: '.$valor.' en la linea '.$num_linea);
					}
				}
				if ($file_struct[$campo]['char_size']){
					if ($file_struct[$campo]['char_size']!=strlen($valor)){
						$codigo_original=$valor;
						if (strlen($valor)==9) $valor='1'.$valor;
						if (strlen($valor)==8) $valor='11'.$valor;
						if (strlen($valor)==7) $valor='110'.$valor;
						if (strlen($valor)==6) $valor='1100'.$valor;
						if (strlen($valor)==5) $valor='11000'.$valor;
						if (strlen($valor)==4) $valor='110000'.$valor;
						if (strlen($valor)==3) $valor='1100000'.$valor;
						if (strlen($valor)==2) $valor='11000000'.$valor;
						if (preg_match('#^110\d{7}$#',$valor)===0){
							throw new Exception('El campo: '.$campo.' es incorrecto, valor: '.$codigo_original.' en linea: '.$num_linea);
						}
					}
				}
				
			}
			$this->data[] = $linea;
		}
		$this->setTipoCarga('recaudacion');

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
		try {

			// if ($line['CODIGO:CNEL']!=='1103069713') return null;
			// print_arr($line);

			$line['FECHA_ULTIMO_PAGO'] = substr($line['FECHA_ULTIMO_PAGO'],0,4).'-'.substr($line['FECHA_ULTIMO_PAGO'],4,2).'-'.substr($line['FECHA_ULTIMO_PAGO'],6,2);

			$cuenta_orion = getCuentaByCuentaAndProcess($line['CODIGO:CNEL'],$_POST['id_proceso']);
			if (!$cuenta_orion) throw new Exception('No existe la cuenta '.$line['CODIGO:CNEL']);
			$cuenta = new CargaModelo_Item_Cuenta();
			$cuenta->numero_cuenta = $cuenta_orion['cuenta'];
			$cuenta->valor_actual = $line['DEUDA_ACTUAL'];
			//get pagos by cuenta
			$aux_pagos = array();
			foreach(getActualizaciones($cuenta_orion['id_cuenta']) as $act){
				if ($act['tipo_actualizacion']=='PAGO')
					$aux_pagos[]=$act;
			}
			$valor_pago = $line['TOTAL(EFEC-CONV-DOC)'];
			//verifica que no exista el mismo valor de pago la misma fecha
			$procesar_pago = true;
			if (!empty($aux_pagos)){
				foreach($aux_pagos as $p){
					if (
						abs($p['diferencia'])==abs($line['TOTAL(EFEC-CONV-DOC)']) && 
						date('Y-m-d',strtotime($p['fecha_actualizacion']))==date('Y-m-d',strtotime($line['FECHA_ULTIMO_PAGO']))
					){
						$procesar_pago = false;
						break;
					// }else{
					// 	$valor_pago = abs($line['TOTAL(EFEC-CONV-DOC)'])-abs($p['diferencia']);
					// 	$procesar_pago = true;
					}
				}
			}
			if (!$procesar_pago) return null;
			if (floatval($line['TOTAL(EFEC-CONV-DOC)'])!=0){
				$cuenta->add_actualizacion('PAGO',floatval(abs($valor_pago))*(-1),$line['FECHA_ULTIMO_PAGO']);
			}
		}catch(Exception $ex){
			throw new exception($ex->getMessage());
		}
		// echo 'Proceso dependiente de definición de pagos por parte de la operación';
		// print_arr($cuenta);
		// die();
		return $cuenta;
	}

	// Iterator
	function next() {
		$this->ptr++;
	}
	
	function current() {
		return $this->processRecord($this->data[$this->ptr]);
	}
	
	function rewind() {
		$this->ptr=0;
		$this->cuentas_count = count($this->data);
	}
	
	function key() {
		return $this->ptr;
	}
	
	function valid() {
		return ($this->ptr < $this->cuentas_count);
	}
	
}

class PagosCnel extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'CNEL - Cargador de Pagos';
	}
	
	function getDescripcion() {
		return 'CNEL - Cargador de Pagos';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader=new PagosCnelCargador(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				return $uploader;

			break;
			
			case '2':
				if($_FILES['data']['error']!='0')
					throw new Exception('Error '.$_FILES['data']['error'].' al cargar archivo');
				
				$aux=$SM->carga_process;
				$aux['source_file']=uniqid();
				$aux['original_filename']=$_FILES['data']['name'];
				$SM->carga_process=$aux;
				
				if(!move_uploaded_file($_FILES['data']['tmp_name'],_TMP_UPLOAD_FOLDER.'/'.$aux['source_file']))
					throw new Exception('Error al mover archivo subido');
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
				die();
				
			break;
			
			case '1':
				$__data['_T']['maincontent']='<table>
					<tr>
						<td>
							<img src="user/Cnel/Uploaders/logo_empresa.png" width="130px" height="80px">
						</td>
						<td>
							<h1>Cargador de Pagos - CNEL</h1><br>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
								<br><br>
								Seleccione archivo de <b>PAGOS</b>:
								<input type="file" name="data" />
								<br><br>
								<button class="btn btn-primary" '.(!empty($error_file)?'disabled="disabled"':'').'>Cargar</button>
							</form>
						</td>
					</tr>
				</table>';
			break;
			
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'ModeloPagosCNEL.txt'=>''
		);
		if($with_data) {
			$ret['ModeloPagosCNEL.txt']=file_get_contents(dirname(__FILE__).'/ModeloPagosCNEL.txt');
		}
		return $ret;
		
	}	
}