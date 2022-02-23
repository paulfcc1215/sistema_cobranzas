<?php

class Cargador_TDCCooperativaJEP extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $header;
	private $data;

	private $catalogos = array();
	private $ptr=0;

	function __construct($fpath_files) {

		$cat_requeridos = array(
			'TCestadosciviles.txt',
			'TCprofesiones.txt',
			'TCprovincias.txt',
			'TCciudades.txt',
			'TCestadostarjeta.txt',
			'TCestadosoperacion.txt',
			'TCtiporeferencia.txt',
			'TCtipotelefono.txt',
		);

		foreach($cat_requeridos as $name){
			if (!in_array($name,array_keys($fpath_files))){
				throw new exception('Falta el catalogo: '.$name);
			}
			$catalogo = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$fpath_files[$name]);
			foreach($catalogo as $c){
				switch ($name){
					case 'TCestadosciviles.txt':
						$this->catalogos[$name][$c['CESTADOCIVIL']]=$c['DESCRIPCION'];
					break;
					case 'TCprofesiones.txt':
						$this->catalogos[$name][$c['CPROFESION']]=$c['DESCRIPCION'];
					break;
					case 'TCprovincias.txt':
						$this->catalogos[$name][$c['cprovincia']]=$c['Nombre'];
					break;
					case 'TCciudades.txt':
						$this->catalogos[$name][$c['cprovincia']][$c['cciudad']]=$c['Nombre'];
					break;
					case 'TCestadostarjeta.txt':
						$this->catalogos[$name][$c['Codigo']][$c['Descripcion']]=$c['Motivo'];
					break;
					case 'TCestadosoperacion.txt':
						$this->catalogos[$name][$c['Codigo']]=$c['Descripcion'];
					break;
					case 'TCtiporeferencia.txt':
						$this->catalogos[$name][$c['CTIPOREFERENCIA']]=$c['DESCRIPCION'];
					break;
					case 'TCtipotelefono.txt':
						$this->catalogos[$name][$c['Tipo']]=$c['Descripcion'];
					break;
				}
			}
		}
		

		$dir = _TMP_UPLOAD_FOLDER;
		$files = array();

		foreach ($fpath_files as $name => $file){
			if(in_array($name,$cat_requeridos)) continue;
			$archivo = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$file);
			$aux = array();
			switch ($name){
				case 'TCcliente.txt':
					foreach($archivo as $line => $value){
						$value['Ciudad'] = $this->catalogos['TCciudades.txt'][$value['Provincia']][$value['Ciudad']];
						$value['Provincia'] = $this->catalogos['TCprovincias.txt'][$value['Provincia']];
						
						$value['Estado civil'] = $this->catalogos['TCestadosciviles.txt'][$value['Estado civil']];
						$value['Profesion'] = $this->catalogos['TCprofesiones.txt'][$value['Profesion']];
						$aux[$value['Numero Operacion']] = $value;
					}
				break;
				case 'TCvalores.txt':
					foreach($archivo as $line => $value){
						$value['CESTADOOPERACION'] = $this->catalogos['TCestadosoperacion.txt'][$value['CESTADOOPERACION']];
						$aux[$value['CSOLICITUD']] = $value;
					}
				break;
				case 'TCpagos.txt':
					foreach($archivo as $line => $value){
						$aux[$value['Numero Solicitud']][] = $value;
					}
				break;
				case 'TCreferencia.txt':
					foreach($archivo as $line => $value){
						$value['Tipo Referencia'] = $this->catalogos['TCtiporeferencia.txt'][$value['Tipo Referencia']];
						$aux[$value['Numero Identificacion']][] = $value;
					}
				break;
				case 'TCtelefono.txt':
					foreach($archivo as $line => $value){
						$value['Tipo telefono'] = $this->catalogos['TCtipotelefono.txt'][$value['Tipo telefono']];
						$aux[$value['Numero Identificacion']][] = $value;
						
					}
				break;
			}
			$files[$name]=$aux;
		}

		$data = array();
		foreach ($files['TCcliente.txt'] as $num_operacion => $value){

			if (!in_array($num_operacion,array_keys($data))){
				// cliente
				$data[$num_operacion]['cliente'] = $value;
				// referencias
				$data[$num_operacion]['cliente']['_referencias'] = $files['TCreferencia.txt'][$value['Numero Identificacion']];
				// telefono
				$data[$num_operacion]['cliente']['_telefonos'] = $files['TCtelefono.txt'][$value['Numero Identificacion']];
				// credito
				$data[$num_operacion]['tdc'] = $files['TCvalores.txt'][$num_operacion];
				// pagos
				$data[$num_operacion]['tdc']['_pagos'] = $files['TCpagos.txt'][$num_operacion];

				$count++;
			}
			
		}
		$this->data = $data;
		$this->setTipoCarga('cartera');

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
		// if ($line['cliente']['Numero Identificacion']!='0104141841') return null;
		// print_arr($line);
		// die();
		

		if ($line['cliente']['Numero Operacion']=='') throw new Exception('Número de operacion vacía en línea: '.$this->ptr);
		if ($line['cliente']['Numero Identificacion']=='') throw new Exception('Número de identificación vacía en línea: '.$this->ptr);
		if ($line['tdc']['CSOLICITUD']=='') throw new Exception('No existe la cuenta: '.$line['cliente']['Numero Operacion'].' En el archivo Cvalores.txt');
		if ($line['tdc']['TOTAL A PAGAR']=='') throw new Exception('No existe "VALOR A PAGAR" para la cuenta: '.$line['tdc']['CSOLICITUD'].' En el archivo Cvalores.txt');

		$ret = array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);

		$cuenta = new CargaModelo_Item_Cuenta();

		$cuenta->numero_cuenta = $line['tdc']['CSOLICITUD'];
		$cuenta->valor_actual = round(str_replace(',','',$line['tdc']['TOTAL A PAGAR']),2);

		$total_pagos = 0.0;
		foreach ($line['tdc']['_pagos'] as $p){
			$total_pagos += round(str_replace(",","",$p['Valor pago']),2);
		}

		if ($cuenta->valor_actual > $cuenta->valor_actual+$total_pagos){
			$cuenta->add_actualizacion('AJUSTE-',ABS($total_pagos)*-1,date('Y-m-d'));
		}else if ($cuenta->valor_actual < $cuenta->valor_actual+$total_pagos){
			$cuenta->add_actualizacion('AJUSTE+',ABS($total_pagos),date('Y-m-d'));
		}

		// PAGOS
		foreach ($line['tdc']['_pagos'] as $p){
			$pago = round(str_replace(",","",$p['Valor pago']),2);
			$fecha_pago = Helpers::dmy2ymd($p['Fecha de Pago']);
			$cuenta->add_actualizacion('PAGO',ABS($pago)*-1,$fecha_pago,$p['Hora de cobro']);
		}

		// persona responsable
		$cuenta->persona_responsable = new CargaModelo_Item_Persona();
		
		$tipo_id = 'CEDULA';
		if ($line['cliente']['TIPODOCUMENTO']!='C') $tipo_id = 'OTRO';
		$cuenta->persona_responsable->tipo_identificacion = $tipo_id;
		$cuenta->persona_responsable->identificacion=$line['cliente']['Numero Identificacion'];
		$cuenta->persona_responsable->primer_nombre=$line['cliente']['Nombre Completo'];
		// telefonos deudor
		foreach ($line['cliente']['_telefonos'] as $t){
			foreach ($this->parseTelefonos($t['Numero de telefono']) as $t){
				$cuenta->persona_responsable->add_medio_contacto('TELEFONO',$t);
			}
		}

		// referencias
		foreach ($line['cliente']['_referencias'] as $ref) {
			if ($ref['Numero Identificacion Ref']=='') continue;
			$referencia = new CargaModelo_Item_Persona();
			$referencia->tipo_identificacion = 'CEDULA';
			$referencia->identificacion = $ref['Numero Identificacion Ref'];
			$referencia->primer_nombre = $ref['Nombre Completo'];
			// telefonos referencias
			foreach($this->parseTelefonos($ref['Telefono']) as $t){
				$referencia->add_medio_contacto('TELEFONO',$t);
			}
			$cuenta->pushOtraPersona($referencia,$ref['Tipo Referencia']);
		}
		
		$ret['cuenta']=$cuenta;

		$ret['otros_datos']['Agencia']=$line['cliente']['Agencia'];
		$ret['otros_datos']['Ciudad']=$line['cliente']['Ciudad'];
		$ret['otros_datos']['Provincia']=$line['cliente']['Provincia'];
		$ret['otros_datos']['Sexo']=$line['cliente']['Sexo'];
		$ret['otros_datos']['Estado civil']=$line['cliente']['Estado civil'];
		$ret['otros_datos']['Profesion']=$line['cliente']['Profesion'];

		$ret['otros_datos']['CESTADOTARJETA']=$line['tdc']['CESTADOTARJETA'];
		$ret['otros_datos']['CESTADOOPERACION']=$line['tdc']['CESTADOOPERACION'];
		$ret['otros_datos']['CALIFICACION']=$line['tdc']['CALIFICACION'];
		$ret['otros_datos']['DIAS VENCIDOS']=$line['tdc']['DIAS VENCIDOS'];
		$ret['otros_datos']['PAGOS VENCIDOS']=$line['tdc']['PAGOS VENCIDOS'];
		$ret['otros_datos']['PAGO MINIMO']=$line['tdc']['PAGO MINIMO'];
		$ret['otros_datos']['VALOR VENCIDO']=$line['tdc']['VALOR VENCIDO'];
		$ret['otros_datos']['TOTAL A PAGAR']=$line['tdc']['TOTAL A PAGAR'];
		$ret['otros_datos']['CAPITAL PROVISION']=$line['tdc']['CAPITAL PROVISION'];
		$ret['otros_datos']['CUPO UTILIZADO']=$line['tdc']['CUPO UTILIZADO'];
		$ret['otros_datos']['INT REC']=$line['tdc']['INT REC'];
		$ret['otros_datos']['MARCA TC']=$line['tdc']['MARCA TC'];
		$ret['otros_datos']['FECHA TOPE PAGO']=$line['tdc']['FECHA TOPE PAGO'];

		//print_arr($ret);
		// die();
		return $ret;
	}

	// Iterator

	function rewind() {
		$this->ptr = 0;
		$this->keys = array_keys($this->data);
		$this->keysCount = count($this->data);
	}

	function next() {
		$this->ptr++;
	}

	function current() {
        return $this->processRecord($this->data[$this->keys[$this->ptr]]);
	}

	function key() {
        return $this->keys[$this->ptr];
	}

	function valid() {
		return ($this->ptr < $this->keysCount);
	}

}

class TDC_CooperativaJEP extends CargaModelo_Handler_Abstract {

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
				$uploader = new Cargador_TDCCooperativaJEP($SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '2':
				error_reporting(E_ALL);
				$dir = _BASE_USER_PATH.'/CooperativaJEP_TDC/Cargas/cartera/';
				$__files = explode(',',$_POST['files']);
				$aux = $SM->carga_process;
				foreach ($__files as $f){
					$aux['source_file'][$f] = uniqid();
					$aux['original_filename'][$f] = $f;
					if(!copy($dir.$f,_TMP_UPLOAD_FOLDER.'/'.$aux['source_file'][$f])) throw new Exception('Error al mover archivo subido '.$f);
					//if(!move_uploaded_file($dir.$f,_TMP_UPLOAD_FOLDER.'/'.$aux['source_file'][$f])) throw new Exception('Error al mover archivo subido '.$f);
				}
				$SM->carga_process=$aux;
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
			break;
			
			case '1':
				
				$archivos_permitidos = array(
					'TCcliente.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero Operacion','Nombre Completo','Apellidos','Nombres','Agencia','Ciudad','Provincia','Sexo','Estado civil','Profesion'),
					'TCvalores.txt'=> array('TIPODOCUMENTO','Numero Identificacion','CSOLICITUD','CESTADOTARJETA','CESTADOOPERACION','CALIFICACION','DIAS VENCIDOS','PAGOS VENCIDOS','PAGO MINIMO','VALOR VENCIDO','TOTAL A PAGAR','CAPITAL PROVISION','CUPO UTILIZADO','INT REC','MARCA TC','FECHA TOPE PAGO'),
					'TCpagos.txt'=> array('Numero Identificacion','Numero Solicitud','Fecha de Pago','Hora de cobro','Valor pago','Canal Cobro','Forma de pago','Total'),
					'TCreferencia.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero Identificacion Ref','Nombre Completo','Telefono','Tipo Referencia'),
					'TCtelefono.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero de telefono','Tipo telefono'),

					'TCciudades.txt'=> array('cprovincia','cciudad','Nombre'),
					'TCprovincias.txt'=> array('cprovincia','Nombre'),
					'TCprofesiones.txt'=> array('CPROFESION','DESCRIPCION'),
					'TCestadosciviles.txt'=> array('CESTADOCIVIL','DESCRIPCION'),
					'TCestadosoperacion.txt'=> array('Codigo','Descripcion'),
					'TCestadostarjeta.txt'=> array('Codigo','Descripcion','Motivo'),
					'TCtiporeferencia.txt'=> array('CTIPOREFERENCIA','DESCRIPCION'),
					'TCtipotelefono.txt'=> array('Tipo','Descripcion')
				);
				$dir = _BASE_USER_PATH.'/CooperativaJEP_TDC/Cargas/cartera/';
				$archivos_existentes = array();
				foreach (scandir($dir) as $file){
					if (!is_file($dir.$file)) continue;
					$archivos_existentes[]=$file;
				}
				
				$error_file = array();
				$archivos_ok = array();
				foreach($archivos_permitidos as $fname => $cols){
					$add_file_ok=true;
					if (!in_array($fname, $archivos_existentes)){
						$error_file[$fname][] = 'Base de '.$fname.' no existe';
						$add_file_ok=false;
						continue;
					}else{
						$aux = new Helpers_CSV($dir.$fname);
						$aux_head = $aux->getHeader();
						if (count($cols)!=count($aux_head)) {
							$add_file_ok=false;
							$error_file[$fname][]='Las columnas no coinciden con el formato de la base';
						}
						foreach ($cols as $c){
							if (!in_array($c,$aux_head)){
								$add_file_ok=false;
								$error_file[$fname][$c]='Falta Columna';
							}
						}
					}
					$archivos_ok[] = $fname;
				}

				$__data['_T']['maincontent']='<h1>Carga de Cartera - Cooperativa JEP</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
					<label>Verificación de archivos:</label><br>';
					foreach ($archivos_permitidos as $file => $campos){
						$__data['_T']['maincontent'].='<img src="/cobranzas/user/CooperativaJEP/Uploaders/'.(in_array($file,array_keys($error_file))?'error.png':'ok.png').'" width="21" height="21">  '.$file.'<br>';
						if (in_array($file,array_keys($error_file))){
							$__data['_T']['maincontent'].='<ul>';
							foreach ($error_file[$file] as $campo => $observacion){
								$__data['_T']['maincontent'].='<li>'.$campo.' - '.$observacion.'</li>';
							}
							$__data['_T']['maincontent'].='</ul>';
						}
					}
					$__data['_T']['maincontent'].='<input type="hidden" name="files" value="'.implode(',',$archivos_ok).'"/>';
					$__data['_T']['maincontent'].='<br><br>
					<button class="btn btn-primary" '.(!empty($error_file)?'disabled="disabled"':'').'>Cargar</button>
				</form>';

			break;
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'modelo_TCcliente.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCcliente.txt'),
			'modelo_TCvalores.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCvalores.txt'),
			'modelo_TCpagos.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCpagos.txt'),
			'modelo_TCreferencia.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCreferencia.txt'),
			'modelo_TCtelefono.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCtelefono.txt'),

			'modelo_TCciudades.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCciudades.txt'),
			'modelo_TCprovincias.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCprovincias.txt'),
			'modelo_TCprofesiones.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCprofesiones.txt'),
			'modelo_TCestadosciviles.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCestadosciviles.txt'),
			'modelo_TCestadosoperacion.txt'=>file_get_contents(dirname(__FILE__).'/modelo_TCestadosoperacion.txt'),
			'modelo_TCestadostarjeta'=>file_get_contents(dirname(__FILE__).'/modelo_TCestadostarjeta.txt'),
			'modelo_TCtiporeferencia'=>file_get_contents(dirname(__FILE__).'/modelo_TCtiporeferencia.txt'),
			'modelo_TCtipotelefono'=>file_get_contents(dirname(__FILE__).'/modelo_TCtipotelefono.txt'),
		);
		if($with_data) {
			$ret['bases_modelo']['modelo_TCcliente.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCcliente.txt');
			$ret['bases_modelo']['modelo_TCvalores.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCvalores.txt');
			$ret['bases_modelo']['modelo_TCpagos.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCpagos.txt');
			$ret['bases_modelo']['modelo_TCreferencia.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCreferencia.txt');
			$ret['bases_modelo']['modelo_TCtelefono.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCtelefono.txt');

			$ret['bases_modelo']['modelo_TCciudades.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCciudades.txt');
			$ret['bases_modelo']['modelo_TCprovincias.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCprovincias.txt');
			$ret['bases_modelo']['modelo_TCprofesiones.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCprofesiones.txt');
			$ret['bases_modelo']['modelo_TCestadosciviles.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCestadosciviles.txt');
			$ret['bases_modelo']['modelo_TCestadostarjeta.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCestadostarjeta.txt');
			$ret['bases_modelo']['modelo_TCtiporeferencia.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCtiporeferencia.txt');
			$ret['bases_modelo']['modelo_TCtipotelefono.txt']=file_get_contents(dirname(__FILE__).'/modelo_TCtipotelefono.txt');
		}
		return $ret;
	}

}
