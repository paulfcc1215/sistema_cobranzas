<?php

class Cargador_CarteraCooperativaJEP extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $header;
	private $data;

	private $catalogos = array();
	private $ptr=0;

	function __construct($fpath_files) {

		$cat_requeridos = array(
			'Estadosciviles.txt',
			'Profesiones.txt',
			'Tiporeferencia.txt',
			'Tipotelefono.txt',
			'Provincias.txt'
		);

		foreach($cat_requeridos as $name){
			if (!in_array($name,array_keys($fpath_files))){
				throw new exception('Falta el catalogo: '.$name);
			}
			$aux[$name] = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$fpath_files[$name]);
		}
		
		foreach ($aux['Estadosciviles.txt'] as $v){
			$this->catalogos['Estadosciviles'][$v['CESTADOCIVIL']]=$v['DESCRIPCION'];
		}
		foreach ($aux['Profesiones.txt'] as $v){
			$this->catalogos['Profesiones'][$v['CPROFESION']]=$v['DESCRIPCION'];
		}
		foreach ($aux['Tiporeferencia.txt'] as $v){
			$this->catalogos['Tiporeferencia'][$v['ctiporeferencia']]=$v['DESCRIPCION'];
		}
		foreach ($aux['Tipotelefono.txt'] as $v){
			$this->catalogos['Tipotelefono'][$v['Tipo']]=$v['Descripcion'];
		}
		foreach ($aux['Provincias.txt'] as $v){
			$this->catalogos['Provincias'][$v['cprovincia']]=$v['Nombre'];
		}

		$dir = _TMP_UPLOAD_FOLDER;
		$files = array();
		// echo 'inicio leyendo archivos '.date('H:i:s');
		foreach ($fpath_files as $name => $file){
			if (in_array($name,$cat_requeridos)) continue;
			$archivo = new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$file);
			$aux = array();
			switch ($name){
				case 'Cliente.txt':
					foreach($archivo as $line => $value){
						$value['Provincia'] = $this->catalogos['Provincias'][$value['Provincia']];
						$value['Estado civil'] = $this->catalogos['Estadosciviles'][$value['Estado civil']];
						$value['profesion'] = $this->catalogos['Profesiones'][$value['profesion']];
						$aux[$value['Numero Operacion']] = $value;
					}
				break;
				case 'Coactivas.txt':
				case 'Cao.txt':
					foreach($archivo as $line => $value){
						$aux[$value['CCUENTA']] = $value;
					}
				break;
				case 'Credito.txt':
					foreach($archivo as $line => $value){
						$aux[$value['Numero Operacion']] = $value;
					}
				break;
				case 'Cuota.txt':
				case 'Pagos.txt':
					foreach($archivo as $line => $value){
						$aux[$value['Numero Operacion']][] = $value;
					}
				break;
				case 'Referencia.txt':
					foreach($archivo as $line => $value){
						$value['Tipo Referencia'] = $this->catalogos['Tiporeferencia'][$value['Tipo Referencia']];
						$aux[$value['Numero Identificacion']][] = $value;
					}
				break;
				case 'Telefono.txt':
					foreach($archivo as $line => $value){
						$value['Tipo telefono'] = $this->catalogos['Tipotelefono'][$value['Tipo telefono']];
						$aux[$value['Numero Identificacion']][] = $value;
					}
				break;
			}
			$files[$name]=$aux;
		}

		// print_arr(array_keys($files));
		// print_arr(count($files['Coactivas.txt']));
		// print_arr($files['Cao.txt']);
		// die();
		// print_arr(count($files['Cuota.txt']));
		// print_arr(count($files['Pagos.txt']));
		// print_arr(count($files['Referencia.txt']));
		// print_arr(count($files['Telefono.txt']));
		// echo 'fin leyendo archivos '.date('H:i:s').'<br><br>';
		
		// echo 'Inicio Armando Data '.date('H:i:s');

		$excluir = array('TIPODOCUMENTO','Numero Identificacion','Numero Operacion');
		$data = array();
		foreach ($files['Cliente.txt'] as $num_operacion => $value){

			if (!in_array($num_operacion,array_keys($data))){
				// cliente
				$data[$num_operacion]['cliente'] = $value;
				// referencias
				$data[$num_operacion]['cliente']['_referencias'] = $files['Referencia.txt'][$value['Numero Identificacion']];
				// telefono
				$data[$num_operacion]['cliente']['_telefonos'] = $files['Telefono.txt'][$value['Numero Identificacion']];
				// credito
				$data[$num_operacion]['credito'] = $files['Credito.txt'][$num_operacion];
				// cuotas
				$data[$num_operacion]['credito']['_cuotas'] = $files['Cuota.txt'][$num_operacion];
				// pagos
				$data[$num_operacion]['credito']['_pagos'] = $files['Pagos.txt'][$num_operacion];
				// coactivas
				$data[$num_operacion]['credito']['_coactivas'] = ($files['Coactivas.txt'][$num_operacion]['COACTIVAS']=='Si'?'Si':'No');
				// cao
				$cao = $files['Cao.txt'][$num_operacion];
				unset($cao['CCUENTA']);
				unset($cao['CEDULA']);
				unset($cao['NOMBRE SOCIO']);
				unset($cao['EXTERNA SOCIO']);
				$data[$num_operacion]['credito']['_cao'] = $cao;
			}
			
		}
		// echo 'Fin Armando Data '.date('H:i:s');
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

		if ($line['cliente']['Numero Operacion']=='') throw new Exception('N??mero de operacion vac??a en l??nea: '.$this->ptr);
		if ($line['cliente']['Numero Identificacion']=='') throw new Exception('N??mero de identificaci??n vac??a en l??nea: '.$this->ptr);
		if ($line['credito']['Numero Operacion']=='') throw new Exception('No existe la cuenta: '.$line['cliente']['Numero Operacion'].' en el archivo Creditos.txt');
		if ($line['credito']['Monto deuda']=='') throw new Exception('No existe la Monto deuda para la cuenta: '.$line['cliente']['Numero Operacion'].' en el archivo Creditos.txt');

		$ret = array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);

		$cuenta = new CargaModelo_Item_Cuenta();
		$cuenta->numero_cuenta = $line['credito']['Numero Operacion'];
		$cuenta->valor_actual = round(str_replace(',','',$line['credito']['Monto deuda']),2);
		$total_a_pagar = round(str_replace(',','',$line['credito']['Total a pagar']),2);
		
		// ajustamos para que vaya a monto a pagar
		$total_pagos=0.0;
		foreach ($line['credito']['_pagos'] as $p){
			$total_pagos+=round(str_replace(",","",$p['Total']),2);
		}
		
		if($cuenta->valor_actual > ($total_a_pagar+$total_pagos)) {
			$cuenta->add_actualizacion('AJUSTE-',-1*abs($cuenta->valor_actual-($total_a_pagar+$total_pagos)),date('Y-m-d'));
		} else if($cuenta->valor_actual < $total_a_pagar+$total_pagos) {
			$cuenta->add_actualizacion('AJUSTE+',abs($cuenta->valor_actual-($total_a_pagar+$total_pagos)),date('Y-m-d'));
		}
			
		// PAGOS
		foreach ($line['credito']['_pagos'] as $p){
			$pago = round(str_replace(",","",$p['Total']),2);
			$cuenta->add_actualizacion('PAGO',$pago*-1,$p['Fecha Pago']);
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
		$ret['otros_datos']['Plaza']=$line['cliente']['Plaza'];
		$ret['otros_datos']['Provincia']=$line['cliente']['Provincia'];
		$ret['otros_datos']['sexo']=$line['cliente']['sexo'];
		$ret['otros_datos']['Estado civil']=$line['cliente']['Estado civil'];
		$ret['otros_datos']['profesion']=$line['cliente']['profesion'];

		$ret['otros_datos']['Numero cuota mas vencido']=$line['credito']['Numero cuota mas vencido'];
		$ret['otros_datos']['Numero cuotas vencidas']=$line['credito']['Numero cuotas vencidas'];
		$ret['otros_datos']['Tasa credito']=$line['credito']['Tasa credito'];
		$ret['otros_datos']['Fecha creacion']=$line['credito']['Fecha creacion'];
		$ret['otros_datos']['Tipo credito']=$line['credito']['Tipo credito'];
		$ret['otros_datos']['Fecha Vcto.']=$line['credito']['Fecha Vcto.'];
		$ret['otros_datos']['Estado de la operacion']=$line['credito']['Estado de la operacion'];
		$ret['otros_datos']['Descripcion producto']=$line['credito']['Descripcion producto'];
		$ret['otros_datos']['Nombre agencia']=$line['credito']['Nombre agencia'];
		$ret['otros_datos']['Plazo']=$line['credito']['Plazo'];
		$ret['otros_datos']['Coactiva']=$line['credito']['_coactivas'];
		$ret['otros_datos']['Cao']=$line['credito']['_cao']['CAO'];
		// cuotas
		usort($line['credito']['_cuotas'],function ($a, $b) {
			return $a['Numero cuota'] > $b['Numero cuota'];
		});
		foreach ($line['credito']['_cuotas'] as $c){
			$ret['otros_datos']['cuota_numero_'.$c['Numero cuota']]=$c['Numero cuota'];
			$ret['otros_datos']['cuota_capital_'.$c['Numero cuota']]=$c['Capital cuota'];
			$ret['otros_datos']['cuota_fecha_vencimiento_'.$c['Numero cuota']]=$c['Fecha Vcto.'];
		}
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

class CarteraCooperativaJEP extends CargaModelo_Handler_Abstract {

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
				$uploader = new Cargador_CarteraCooperativaJEP($SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '2':
				$dir = _BASE_USER_PATH.'/CooperativaJEP/Cargas/cartera/';
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
					'Estadosciviles.txt'=> array('CESTADOCIVIL','DESCRIPCION'),
					'Profesiones.txt'=> array('CPROFESION','DESCRIPCION'),
					'Tiporeferencia.txt'=> array('ctiporeferencia','DESCRIPCION'),
					'Tipotelefono.txt'=> array('Tipo','Descripcion'),
					'Provincias.txt'=> array('cprovincia','Nombre'),
					'Coactivas.txt'=> array('CCUENTA','CEDULA','NOMBRE','EXTERNA','COACTIVAS'),
					'Cao.txt'=> array('CCUENTA','CEDULA','NOMBRE SOCIO','EXTERNA SOCIO','ESTATUS OPERACION','CCALIFICACIONCREDITO','ESTADO_OPERACION','DIAS MORA','CAO','AHORROS VISTA','FONDO','JEPITO','DPF','OTROSCREDITOS','COACTIVAS','TC','SALDOTC','DIASMORA','MAIL','ORIGENINGRESOS','VINCULADO','FULTIMOPAGO','MONTOULTIMOPAGO'),

					'Cliente.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero Operacion','Nombre Completo','Apellidos','Nombres','Agencia','Plaza','Provincia','sexo','Estado civil','profesion'),
					'Credito.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero Operacion','Monto deuda','Numero cuota mas vencido','Numero cuotas vencidas','Tasa credito','Fecha creacion','Tipo credito','Fecha Vcto.','Estado de la operacion','Total a pagar','Descripcion producto','Nombre agencia','Plazo'),
					'Cuota.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero Operacion','Numero cuota','Capital cuota','Interes financiero cuota','Comision 1','Comision 2','Fecha Vcto.','Tasa interes','Tasa interes de Mora','Calculo Mora','Valor_mora'),
					'Pagos.txt'=> array('Numero Identificacion','Numero Operacion','Numero cuota','Fecha Vencimiento','Fecha Pago','valor_1_pagos','valor_2_pagos','valor_3_pagos','valor_4_pagos','valor_5_pagos','valor_6_pagos','Forma Pago','Numero Convenio','Tipo de Movimiento','Marca de Condonacion','Porcentaje exoneracion','Total','Numero de Abono'),
					'Referencia.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero Identificacion Ref','Nombre Completo','Telefono','Tipo Referencia'),
					'Telefono.txt'=> array('TIPODOCUMENTO','Numero Identificacion','Numero de telefono','Tipo telefono')
				);
				$dir = _BASE_USER_PATH.'/CooperativaJEP/Cargas/cartera/';
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
					<label>Verificaci??n de archivos:</label><br>';
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
			'Cliente.txt'=>file_get_contents(dirname(__FILE__).'/modelo_cliente.txt'),
			'Credito.txt'=>file_get_contents(dirname(__FILE__).'/modelo_credito.txt'),
			'Cuota.txt'=>file_get_contents(dirname(__FILE__).'/modelo_cuota.txt'),
			'Pagos.txt'=>file_get_contents(dirname(__FILE__).'/modelo_pagos.txt'),
			'Referencia.txt'=>file_get_contents(dirname(__FILE__).'/modelo_referencia.txt'),
			'Telefono.txt'=>file_get_contents(dirname(__FILE__).'/modelo_telefono.txt'),
			'Coactivas.txt'=>file_get_contents(dirname(__FILE__).'/modelo_coactivas.txt'),
			'Cao.txt'=>file_get_contents(dirname(__FILE__).'/modelo_cao.txt'),

			'Estadosciviles.txt'=>file_get_contents(dirname(__FILE__).'/modelo_estadosciviles.txt'),
			'Profesiones.txt'=>file_get_contents(dirname(__FILE__).'/modelo_profesiones.txt'),
			'Tiporeferencia.txt'=>file_get_contents(dirname(__FILE__).'/modelo_tiporeferencia.txt'),
			'Tipotelefono'=>file_get_contents(dirname(__FILE__).'/modelo_tipotelefono.txt')
		);
		if($with_data) {
			$ret['bases_modelo']['Cliente.txt']=file_get_contents(dirname(__FILE__).'/modelo_cliente.txt');
			$ret['bases_modelo']['Credito.txt']=file_get_contents(dirname(__FILE__).'/modelo_credito.txt');
			$ret['bases_modelo']['Cuota.txt']=file_get_contents(dirname(__FILE__).'/modelo_cuota.txt');
			$ret['bases_modelo']['Pagos.txt']=file_get_contents(dirname(__FILE__).'/modelo_pagos.txt');
			$ret['bases_modelo']['Referencia.txt']=file_get_contents(dirname(__FILE__).'/modelo_referencia.txt');
			$ret['bases_modelo']['Telefono.txt']=file_get_contents(dirname(__FILE__).'/modelo_telefono.txt');
			$ret['bases_modelo']['Coactivas.txt']=file_get_contents(dirname(__FILE__).'/modelo_coactivas.txt');
			$ret['bases_modelo']['Cao.txt']=file_get_contents(dirname(__FILE__).'/modelo_cao.txt');

			$ret['bases_modelo']['Estadosciviles.txt']=file_get_contents(dirname(__FILE__).'/modelo_estadosciviles.txt');
			$ret['bases_modelo']['Profesiones.txt']=file_get_contents(dirname(__FILE__).'/modelo_profesiones.txt');
			$ret['bases_modelo']['Tiporeferencia.txt']=file_get_contents(dirname(__FILE__).'/modelo_tiporeferencia.txt');
			$ret['bases_modelo']['Tipotelefono.txt']=file_get_contents(dirname(__FILE__).'/modelo_tipotelefono.txt');
		}
		return $ret;
	}

}