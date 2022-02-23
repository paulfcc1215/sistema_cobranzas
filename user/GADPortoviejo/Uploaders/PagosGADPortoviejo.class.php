<?php

	class Cargador_PagosGADPortoviejo extends CargaModelo_Actualizaciones_Abstract implements CargaModelo_Actualizaciones_Interface, Iterator{

		private $data;
		private $ptr=0;

		function __construct($fpath_files) {

			// verificar codificación de archivo
			if (!mb_check_encoding(file_get_contents($fpath_files),'utf8')) throw new Exception('El archivo debe ser un .txt codificado en UTF-8');

			$file_pagos = new Helpers_CSV($fpath_files);

			$campos_requeridos = array(
				'id',
				'fechapago',
				'total'
			);
			$cabecera = $file_pagos->getHeader();

			//validar cabecera de pagos
			$campos_faltan = array();
			foreach ($campos_requeridos as $c){
				if (!in_array($c,$cabecera)){
					$campos_faltan[]=$c;
				}
			}
			if (!empty($campos_faltan)){
				throw new Exception('Revise la cabecera del archivo, se requiere las columnas: ["'.implode('" | "',$campos_requeridos).'"] y faltan ["'.implode('" | "',$campos_faltan).'"]');
			}

			foreach ($file_pagos as $line){
				$this->data[] = $line;
			}

			$this->setTipoCarga('recaudacion');
		}


		function processRecord(&$line) {

			$num_linea = $this->ptr+1;
			// GET CUENTA BY CUENTA AND PROCCESS
			if (trim($line['id'])=='') throw new Exception('No existe cuenta en la línea: '.$num_linea);
			$aux_cuenta = getCuentaByCuentaAndProcess(trim($line['id']),$_POST['id_proceso']);
			
			// if (!$aux_cuenta) throw new Exception('La cuenta "'.$line['id'].'" no existe: Linea '.$num_linea);
			if (!$aux_cuenta) return null;
			
			$line['total'] = str_replace(',','.',trim($line['total']));
			if (floatval($line['total'])==0) return null;
			// if (trim($line['fechapago'])=='') throw new Exception('No existe fecha de pago en línea: '.$num_linea);
			// pedido de operacion que si no tiene fehca de pago no lo procese. ticket R-012394
			if (trim($line['fechapago'])=='') return null;

			$aux_fecha = explode('/',trim($line['fechapago']));
			if (!checkdate($aux_fecha[1], $aux_fecha[2], $aux_fecha[0])) throw new Exception('Fecha incorrecta en linea: '.$num_linea);
			$line['fechapago'] = $aux_fecha[0].'-'.$aux_fecha[1].'-'.$aux_fecha[2];

			$cuenta = new CargaModelo_Item_Cuenta();
			
			$cuenta->numero_cuenta = $line['id'];
			// $cuenta->valor_actual = $aux_cuenta['valor_actual'];
			$cuenta->add_actualizacion(
				'PAGO',
				abs(floatval($line['total']))*-1,
				$line['fechapago']
			);

			return $cuenta;
		}

		// Iterator

		function rewind() {
			$this->ptr = 0;
			$this->keysCount = count($this->data);
		}

		function next() {
			$this->ptr++;
		}

		function current() {
			return $this->processRecord($this->data[$this->ptr]);
		}

		function key() {
			return $this->ptr;
		}

		function valid() {
			return ($this->ptr < $this->keysCount);
		}

	}

	class PagosGADPortoviejo extends CargaModelo_Handler_Abstract {
		function getTipoBase() {
			return 'Cargador de Pagos';
		}
		
		function getDescripcion() {
			return 'Cargador de Pagos';
		}
		
		function execute($step, &$__data) {

			// if ($_SERVER['REMOTE_ADDR']!='10.0.210.85')
			// 	die('Casrgador en construcción');

			$SM=SessionManager::getInstance();
			switch($step) {
				case '3':
					$uploader = new Cargador_PagosGADPortoviejo(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
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
					$__data['_T']['maincontent']='
					<table>
						<tr>
							<td>
								<img src="user/GADPortoviejo/Uploaders/logo_empresa.png" width="80px" height="100px">
							</td>
							<td>
								<h1>Pagos - GAD Portoviejo</h1><br>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
								<br><br>Seleccione el archivo:
								<input type="file" name="data">
								<br><br>
								<button class="btn btn-primary">Cargar</button>
								</form>
							</td>
						</tr>
					</table>';
				break;
			}

		}
		
		function getArchivoModelo($with_data=false) {
			$ret=array(
				'modelo_pagos_GADPortoviejo.txt'=>file_get_contents(dirname(__FILE__).'/modelo_pagos_GADPortoviejo.txt'),
			);
			if($with_data) {
				$ret['bases_modelo']['modelo_pagos_GADPortoviejo.txt']=file_get_contents(dirname(__FILE__).'/modelo_pagos_GADPortoviejo.txt');
			}
			return $ret;
			
		}
	}
