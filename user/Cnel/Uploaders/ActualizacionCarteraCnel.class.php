<?php

class ActualizacionCarteraCnelCargador extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{

	private $ptr=0;
	private $data=array();


	function __construct($file_path) {
		$cabecera_requerida = array(
			'CODIGO',
			'CODIGO:CNEL',
			'CEDULA',
			'CLIENTE',
			'ESTADO_CONTRATO',
			'DEUDA_CONTRATO',
			'SEGMENTO',
			'DEUDA_BASE',
			'FACT_PEND_BASE',
			'FECHA_SALDO',
			'FECHA_GESTION',
			'RECAUDA_EFECTIVO_TOT',
			'RECAUDA_CONV_PAGO_TOT',
			'RECAUDA_DOCMTOS_TOT',
			'FECHA_ULTIMO_PAGO',
			'RECAUDA_EFECTIVO_FECHA',
			'RECAUDA_CONV_PAGO_FECHA',
			'RECAUDA_DOCMTOS_FECHA',
			'TOTAL(EFEC-CONV-DOC)',
			'PORCENTAJE RECAUDACION',
			'DEUDA_ACTUAL',
			'FACT_PEND_ACTUAL',
			'ESTADO_ACTUAL',
			'GESTIONES_REPORTADAS'
		);
		$archivo = new Helpers_CSV($file_path);

		//validar cabecera de archivo
		if (!empty(array_diff($cabecera_requerida,$archivo->getHeader()))) throw new Exception('Cabecera de archivo incorrecta');

		// campos numericos
		$campos_numericos = array('DEUDA_CONTRATO');

		$result = array();
		foreach ($archivo as $num_linea => $linea) {
			
			// eliminar espacios vacios de la linea y reemplaza , por . en valores float
			foreach ($linea as $campo => &$valor) {
				if (in_array($campo,$campos_numericos)){
					$valor = str_replace(',','.',$valor);
					$valor = str_replace('$','',$valor);
				}
				$valor = trim($valor);
			}

			if ($linea['CODIGO:CNEL']=='') {
				throw new Exception ('No existe cuenta en linea: '.$num_linea);
			}
			// if ($linea['cedula']=='') {
			// 	throw new Exception ('No existe Cedula en linea: '.$num_linea);
			// }
			
			$this->data[] = $linea;
		}
		$this->setTipoCarga('actualizacion');

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
			// VALIDACIONES
			if ($line['CODIGO:CNEL']=='') throw new exception('No existe CODIGO:CNEL en linea: '.$this->ptr);
			if ($line['CEDULA']=='') throw new exception('No existe CEDULA en linea: '.$this->ptr);
			if ($line['CLIENTE']=='') throw new exception('No existe CLIENTE en linea: '.$this->ptr);
			if ($line['ESTADO_CONTRATO']=='') throw new exception('No existe ESTADO_CONTRATO en linea: '.$this->ptr);
			if ($line['DEUDA_CONTRATO']=='') throw new exception('No existe DEUDA_CONTRATO en linea: '.$this->ptr);

			$cuenta=new CargaModelo_Item_Cuenta();
			$cuenta->numero_cuenta = $line['CODIGO:CNEL'];
			$cuenta->valor_actual = $line['DEUDA_CONTRATO'];

			$cuenta_orion = getCuentaByCuentaAndProcess($line['CODIGO:CNEL'],$_POST['id_proceso']);
			if ($cuenta_orion!==false){
				$deudor = getPersona($cuenta_orion['id_deudor']);
				$cnm = array_shift(getCargaNoMapeada($cuenta_orion['id_cuenta']));
			}
			// if (floatval($line['TOTAL_RECAUDACION_PAGOS'])!=0){
			// 	$cuenta->add_actualizacion('PAGO',floatval($line['TOTAL_RECAUDACION_PAGOS'])*(-1),$line['fecha_pago']);
			// }
			
			$cuenta->persona_responsable = new CargaModelo_Item_Persona();

			$cuenta->persona_responsable->tipo_identificacion = 'CEDULA';
			$cuenta->persona_responsable->identificacion = $line['CEDULA'];
			$cuenta->persona_responsable->primer_nombre = $line['CLIENTE'];

			$ret = array(
				'cuenta'=>$cuenta,
				'otros_datos'=>array()
			);

			$ret['otros_datos']['Item'] = $cnm['Item'];
			$ret['otros_datos']['Unidad de Negocio'] = $cnm['Unidad de Negocio'];
			$ret['otros_datos']['Estado'] = $line['ESTADO_ACTUAL'];
			$ret['otros_datos']['Facturas_Pendientes'] = $line['FACT_PEND_ACTUAL'];
			$ret['otros_datos']['Tarifa'] = $cnm['Tarifa'];
			$ret['otros_datos']['Tipo Cliente'] = $cnm['Tipo Cliente'];
			$ret['otros_datos']['Cedula valida'] = $cnm['Cedula valida'];
			$ret['otros_datos']['Rango Pla Pendientes'] = $cnm['Rango Pla Pendientes'];
			$ret['otros_datos']['Numero Medidor'] = $cnm['Numero Medidor'];
			$ret['otros_datos']['Serie Medidor'] = $cnm['Serie Medidor'];
			$ret['otros_datos']['Descripcion_Canton'] = $cnm['Descripcion_Canton'];
			$ret['otros_datos']['Latitud'] = $cnm['Latitud'];
			$ret['otros_datos']['Longitud'] = $cnm['Longitud'];
			$ret['otros_datos']['data'] = $line['SEGMENTO'];
			$ret['otros_datos']['RECAUDA_EFECTIVO_TOT'] = $line['RECAUDA_EFECTIVO_TOT'];
			$ret['otros_datos']['RECAUDA_CONV_PAGO_TOT'] = $line['RECAUDA_CONV_PAGO_TOT'];
			$ret['otros_datos']['RECAUDA_DOCMTOS_TOT'] = $line['RECAUDA_DOCMTOS_TOT'];
		}catch(Exception $e) {
			print_arr($e->getMessage());
			print_arr($line);
			die();
		}
		return $ret;
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

class ActualizacionCarteraCnel extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'CNEL - Actualizacion de Cartera';
	}
	
	function getDescripcion() {
		return 'CNEL - Actualizacion de Cartera';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader=new ActualizacionCarteraCnelCargador(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
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
				/*$__data['_T']['maincontent']='<h1>Actualización de Cartera - CNEL</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				Seleccione el archivo:
				<input type="file" name="data">
				<br>
				<button class="btn btn-primary">Cargar</button>
				</form>';*/
				$__data['_T']['maincontent']='<table>
					<tr>
						<td>
							<img src="user/Cnel/Uploaders/logo_empresa.png" width="130px" height="80px">
						</td>
						<td>
							<h1>Actualización de Cartera - CNEL</h1><br>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
								<br><br>
								Seleccione archivo de <b>ACTUALIZACIÓN</b>:
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
			'ModeloActualizacionCNEL.txt'=>''
		);
		if($with_data) {
			$ret['ModeloActualizacionCNEL.txt']=file_get_contents(dirname(__FILE__).'/ModeloActualizacionCNEL.txt');
		}
		return $ret;
		
	}	
}