<?php

	class CargadorPagosJEPTDC extends CargaModelo_Actualizaciones_Abstract implements CargaModelo_Actualizaciones_Interface, Iterator{

		private $data;
		private $ptr=0;

		function __construct($fpath_files) {

			$cabecera_pagos = array(
				'Numero Identificacion',
                'Numero Solicitud',
                'Fecha de Pago',
                'Hora de cobro',
                'Valor pago',
                'Canal Cobro',
                'Forma de pago',
                'Total'
			);

			$file_pagos = new Helpers_CSV($fpath_files);

			//validar cabecera de pagos
            $cabecera = $file_pagos->getHeader();
			foreach ($cabecera as $c){
				if (!in_array($c,$cabecera_pagos)) throw new Exception('Cabecera de archivo de pagos incorrecta. Debe ser: ['.implode('|',$cabecera_pagos).']');
			}
			
			foreach ($file_pagos as $num_linea => $line){
                foreach ($line as &$v){
                    $v = trim($v);
                    unset($v);
                }
                if (count($cabecera)!= count($line)) throw new exception ('No coinciden las filas con la cabecera en linea: '.$num_linea);
				$line['Valor pago'] = str_replace(',','.',$line['Valor pago']);
				$this->data[] = $line;
			}

			$this->setTipoCarga('recaudacion');
		}


		function processRecord(&$line) {

            // if ($line['Numero Solicitud']!='066200058661') return null;

			// GET CUENTA BY CUENTA AND PROCCESS
			if ($line['Numero Solicitud']=='') throw new Exception('No existe cuenta en la línea: '.$this->ptr);
			if ($line['Fecha de Pago']=='') throw new Exception('No existe fecha de pago en la línea: '.$this->ptr);
			$aux_cuenta = getCuentaByCuentaAndProcess($line['Numero Solicitud'],$_POST['id_proceso']);
			if (!$aux_cuenta) throw new Exception('La cuenta "'.$line['Numero Solicitud'].'" no existe: Linea '.$this->ptr);
            if (floatval($line['Valor pago'])<_ZERO_THRESHOLD) throw new Exception('Pago de 0 en linea: '.$this->ptr);

			$cuenta = new CargaModelo_Item_Cuenta();
			$cuenta->numero_cuenta = $line['Numero Solicitud'];
			$cuenta->valor_actual = $aux_cuenta['valor_actual'];
			$cuenta->add_actualizacion(
				'PAGO',
				abs(floatval($line['Valor pago']))*-1,
				Helpers::dmy2ymd($line['Fecha de Pago']),
				($line['Hora de cobro']==''?null:$line['Hora de cobro'])
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

	class CargaPagosJEPTDC extends CargaModelo_Handler_Abstract {
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
					$uploader = new CargadorPagosJEPTDC(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
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
								<img src="user/CooperativaJEP/Uploaders/jep_logo.jpg" width="100px" height="100px">
							</td>
							<td>
								<h1>Pagos - JEP TDC</h1><br>
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
				'modelo_pagos.txt'=>file_get_contents(dirname(__FILE__).'/modelo_pagos.txt'),
			);
			if($with_data) {
				$ret['bases_modelo']['modelo_pagos.txt']=file_get_contents(dirname(__FILE__).'/modelo_pagos.txt');
			}
			return $ret;
			
		}
	}