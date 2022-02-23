<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class GenericoGestionesMasivas implements CargaModelo_Gestiones_Interface, Iterator{
	
	private $tipif_map=array();

	function __construct($fpath,$id_proceso) {
		
		foreach (Tipificaciones::getTipificacionesByProceso($id_proceso) as $id_t => $t){
			$this->tipif_map[$id_t] = array_shift($t['_metadata']);
			$this->tipif_map[$id_t]['descripcion'] = $t['descripcion'];
		}
		
		if (empty($this->tipif_map)) throw new Exception('No existe catálogo de tipificaciones para el proceso seleccionado');

		// tipificaciones que no requieren tener id llamada
		$sin_id_llamada = array(
			'RESPUESTA MASIVA WHATSAPP',
			'EMAIL-N',
			'WHATSAPP-N',
			'SMS-N',
			'EMAIL',
			'WHATSAPP',
			'WHATSAPP WEB',
			'SMS',
			'SUSPENDER GESTION',
			'VT - NO UBICADO',
			'Dirección Inválida',
			'Cambio de domicilio',
			'VT - NOTIFICADO',
			'Entrega a Titular',
			'Debajo de la Puerta',
			'VT-BUZON',
			'Entrega a Terceros',
			'VT-NO UBICADO',
			'VT - BUZON',
			'No lo conocen',
			'VT-NOTIFICADO TERCERO',
			'VT-NOTIFICADO TITULAR'
		);
		
		$this->db=DB::getInstance();
		$required_columns=array(
			'cuenta',
			'cedula',
			'fecha_hora_gestion',
			'fecha_fin_gestion',
			'tiempo_llamada',
			'id_llamada',
			'usuario',
			'telefono_email',
			'tipificacion',
			'observacion',
			'tipo_gestion',
			'fecha_compromiso',
			'monto_compromiso',
			'latitud',
			'longitud',
			'direccion'
		);

		if (!mb_check_encoding(file_get_contents($fpath),'UTF-8'))
			throw new Exception('El archivo debe estar codificado en UTF-8');
		$fhdl=fopen($fpath,'rb');
		$header=fgetcsv($fhdl,null,_UPLOADS_SEPARATOR,_UPLOADS_TEXT_QUALIFIER);
		if (count(array_unique($header))!=count($header))
			throw new Exception('El archivo subido contiene una o más columnas repetidas ('.print_r($header,true).')');

		$faltan=array();
		foreach ($required_columns as $rc) {
			if(!in_array($rc,$header)) {
				$faltan[]=$rc;
			}
		}

		if (!empty($faltan)) throw new Exception('Faltan las siguientes columnas requeridas: {'.implode(', ',$faltan).'}');

		fclose($fhdl);
		$csv = new Helpers_CSV($fpath);
		
		foreach ($csv as $num_linea=>$linea) {
			$linea['direccion']=str_replace(',','',$linea['direccion']);
			//validaciones de data
			if ($linea['cuenta']=='') throw new Exception('No existe numero de cuenta en linea: '.$num_linea);
			
			if ($linea['fecha_hora_gestion']=='') throw new Exception('No existe fecha de gestión, en linea: '.$num_linea);
			if (!preg_match('#^\d{1,2}/\d{1,2}/\d{4}#',$linea['fecha_hora_gestion'])) throw new Exception('fecha_hora_gestion inválida, formato válido "dd/mm/yyyy H:i:s", en linea: '.$num_linea);

			$fecha_separada = explode(' ',$linea['fecha_hora_gestion']);
			$linea['fecha_hora_gestion'] = trim(Helpers::dmy2ymd($fecha_separada[0]).' '.$fecha_separada[1]);
			
			if ($linea['fecha_fin_gestion']!=''){
				if (!preg_match('#^\d{1,2}/\d{1,2}/\d{4}#',$linea['fecha_fin_gestion'])) throw new Exception('fecha_fin_gestion inválida, formato válido "dd/mm/yyyy", en linea '.$num_linea);
				$fecha_separada = explode(' ',$linea['fecha_fin_gestion']);
				$linea['fecha_fin_gestion'] = Helpers::dmy2ymd($fecha_separada[0]).' '.$fecha_separada[1];
			}
			
			if (trim($linea['tipo_gestion'])=='') throw new Exception('No existe tipo_gestion en linea: '.$num_linea);
			//if($linea['usuario']=='') throw new Exception('No existe usuario en linea: '.$num_linea);
			if ($linea['tipificacion']=='') throw new Exception('No existe tipificacion en linea: '.$num_linea);
			$existe_tip=false;
			foreach($this->tipif_map as $t){
				if (trim($linea['tipificacion'])==$t['descripcion']){
					$tipificacion=$t;
					$existe_tip=true;
					break;
				}
			}
			if (!$existe_tip) throw new Exception('No existe tipificacion en linea: '.$num_linea);

			if ($linea['tipo_gestion']=='IVR') {
				// SOLO PARA CAMPAÑAS DE PORTOAGUAS=>17, CNEL=>18, GAD=>19
				if (in_array($_POST['id_campana'],array(17, 18, 19))){
					$linea['id_llamada']='w_'.microtime(true);
				}
				if ($linea['id_llamada']=='') throw new Exception('No existe id de llamada en linea: '.$num_linea);
			}

			if ($linea['tipo_gestion']=='EMAIL') {
				// valido formato de correo
				// if (!Helpers::is_valid_email($linea['telefono_email'])) throw new Exception('Email incorrecto '.$linea['telefono_email'].', en linea: '.$num_linea);
			}
			if ($linea['tipo_gestion']=='SMS') {
				// valido formato de correo
				// if (!Helpers::is_valid_email($linea['telefono_email'])) throw new Exception('Email incorrecto '.$linea['telefono_email'].', en linea: '.$num_linea);
			}
			if ($linea['tipo_gestion']=='GESTION_COBRANZA') {
				// SOLO PARA CAMPAÑAS DE PORTOAGUAS, CNEL
				if (in_array($_POST['id_campana'],array(17,18)) && $linea['tipificacion']=='IVR'){
					$linea['id_llamada']='w_'.microtime(true);
				}else{
					// id de llamada
					if (!in_array($linea['tipificacion'],$sin_id_llamada)){
						if ($linea['id_llamada']=='') throw new Exception('No existe id de llamada en linea: '.$num_linea);
					}
					// if (!$tipificacion['es_fallecido']){
					// 	if ($linea['id_llamada']=='') throw new Exception('No existe id de llamada en linea: '.$num_linea);
					// }
				}
				// si compromiso pago debe tener fecha y monto de compromiso
				if ($tipificacion['es_promesa']){
					if ($linea['fecha_compromiso']=='') throw new Exception('No existe fecha_compromiso en linea: '.$num_linea);
					if ($linea['monto_compromiso']=='') throw new Exception('No existe monto compromiso en linea: '.$num_linea);
				}
			}
			if ($linea['tipo_gestion']=='VISITA_TERRENO') {
				if ($linea['latitud']=='') throw new Exception('No existe dato en columna "latitud" en linea: '.$num_linea);
				if ($linea['longitud']=='') throw new Exception('No existe dato en columna "longitud" en linea: '.$num_linea);
				if ($linea['direccion']=='') throw new Exception('No existe dato en columna "direccion" en linea: '.$num_linea);
			}
			// $gestiones[$linea['cuenta']][]=$linea;
			$gestiones[]=$linea;
		}
		
		$this->header = $header;
		$this->data = $gestiones;
		$this->numRows = count($this->data);
		$this->keys = array_keys($this->data);
		$this->id_proceso=$id_proceso;
		$this->db->prepare('_get_id_cuenta_by_cuenta_proceso','SELECT get_id_cuenta_by_cuenta_proceso AS id_cuenta FROM public.get_id_cuenta_by_cuenta_proceso('.$id_proceso.',$1)');
	}
	
	function processRecord(&$line) {

		$q1=$this->db->execute('_get_id_cuenta_by_cuenta_proceso',array($line['cuenta']));
		//if(is_null($q1->current()['id_cuenta'])) return null; //throw new Exception('No existe cuenta '.$line['cuenta'].' para el proceso '.$this->id_proceso);
		if (is_null($q1->current()['id_cuenta'])) throw new Exception('No existe cuenta '.$line['cuenta'].' para el proceso '.$this->id_proceso);

		$gestion = new CargaModelo_Item_Gestion();
		$gestion->id_cuenta = $q1->current()['id_cuenta'];
		$gestion->cuenta = $line['cuenta'];

		$gestion->fecha_inicio = $line['fecha_hora_gestion'];
		$gestion->fecha_fin = $line['fecha_fin_gestion'];
		$gestion->user_name = $line['usuario'];
		$gestion->telh_id = $line['id_llamada'];
		if ($line['tipo_gestion']=='VISITA_TERRENO'){
			$gestion->latitud = $line['latitud'];
			$gestion->longitud = $line['longitud'];
			$gestion->direccion = $line['direccion'];
			$gestion->tel_number = '';
		}elseif($line['tipo_gestion']=='EMAIL'){
			$gestion->email = $line['telefono_email'];
			$gestion->tel_number = '';
		}else{
			if ($line['telefono_mail']!==''){
				// $telefonos = Helpers::parseTelefonos($line['telefono_email']);
				// if (empty($telefonos)) throw new Exception('No existen teléfono válido para la cuenta: '.$line['cuenta']);
				// $gestion->tel_number=$telefonos[0];
				$gestion->tel_number = $line['telefono_email'];
			}
			
		}
		$gestion->observacion = $line['observacion'];
		foreach ($this->tipif_map as $id_t => $t){
			if ($t['descripcion']==trim($line['tipificacion'])){
				$gestion->id_tipificacion=$id_t;
				$tip = $t;
			}
		}
		if ($tip['es_promesa']) {
			if (!empty($line['fecha_compromiso'])) {
				$line['fecha_compromiso'] = explode(' ',$line['fecha_compromiso']);
				$gestion->fecha_compromiso = Helpers::dmy2ymd($line['fecha_compromiso'][0]);
				if (!preg_match('#^\d+(\.\d+)?$#',$line['monto_compromiso'])){
					$aux_valor = explode('.', $line['monto_compromiso']);
					if (count($aux_valor)>2){
						$line['monto_compromiso']='0.00';
					}elseif($aux_valor[0]==''){
						$line['monto_compromiso'] = '0.'.$aux_valor[1];
					}
				}
				$gestion->monto_compromiso = $line['monto_compromiso'];
			}
		}

		//por petición de Operación (Eduardo Martinez y Marco Pala 2021-12-14) se agrega funcionalidad para duplicar gestión solo cuando es gestiones IVR para CNEL
		// se retira proceso de replica por petición de Jairo Guevara Ticket R-010891 2022-01-04
		// if ($_POST['id_udn']==15 && $line['tipificacion']=='IVR'){
		// 	crear_gestion_ivr_CNEL($line['cedula'],$gestion->getData());
		// }
		return $gestion;
	}

	function pushFile($filename,$filepath) {

	}

	function getFiles() {
		return array();
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
		return $this->keys[$this->ptr];
	}
	
	function valid() {
		return $this->ptr<$this->numRows;
	}
	
}

class GenericoCargaGestionesMasivas extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Generico - Gestiones Masivas';
	}
	
	function getDescripcion() {
		return 'Generico - Gestiones Masivas';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader=new GenericoGestionesMasivas(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file'],$__data['id_proceso']);
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
				$html_tip='';
				$tip_compromiso=array();
				foreach(Tipificaciones::getTipificacionesByProceso($__data['id_proceso']) as $id_t => $t){
					if($t['status']!='1') continue;
					$style='';
					if(array_shift($t['_metadata'])['es_promesa']=='1'){
						$style='style="background:orange;"';
						$tip_compromiso[]=$t['descripcion'];
					}
					$html_tip.='<small '.$style.'>'.$t['descripcion'].'</small>|<br>';
				}
				$descripcion_archivo=array(
					'cuenta'=>'Cuenta del cliente',
					'cedula'=>'Identificación del cliente',
					'fecha_hora_gestion'=>'Formato válido: (dd/mm/yyyy H:i:s)',
					'fecha_fin_gestion'=>'Formato válido: (dd/mm/yyyy H:i:s)',
					'tiempo_llamada'=>'',
					'id_llamada'=>'id de llamada de gestión',
					'usuario'=>'Usuario quién realiza la gestión',
					'telefono_email'=>'Teléfono/Email donde se realizó la gestión',
					'tipificacion'=>'Tipificaciones:<br>'.$html_tip,
					'observacion'=>'',
					'tipo_gestion'=>'Tipos de Gestión:<br>IVR<br>SMS<br>EMAIL<br>GESTION_COBRANZA<br><p style="background:#74ADF4;">VISITA_TERRENO</p>',
					'fecha_compromiso'=>'Solo si la tipificación es <small style="background:orange;">"'.implode("|",$tip_compromiso).'"</small> y tipo_gestion es "GESTION_COBRANZA"',
					'monto_compromiso'=>'Solo si la tipificación es <small style="background:orange;">"'.implode("|",$tip_compromiso).'"</small> y tipo_gestion es "GESTION_COBRANZA"',
					'latitud'=>'Solo si tipo_gestion es <small style="background:#74ADF4;">"VISITA_TERRENO"</small><br>',
					'longitud'=>'Solo si tipo_gestion es <small style="background:#74ADF4;">"VISITA_TERRENO"</small><br>',
					'direccion'=>'Solo si tipo_gestion es <small style="background:#74ADF4;">"VISITA_TERRENO"</small><br>',
				);
				$__data['_T']['maincontent']='<h1>Cargador Generico de Gestiones Masivas</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
					<br><h4>Descripción de cabecera de archivo</h4>
					<table border="1">
						<tr>';
						foreach (array_keys($descripcion_archivo) as $columna){
							$__data['_T']['maincontent'].='<th style="padding:3px;background:#C1C6CC;">'.$columna.'</th>';
						}
						$__data['_T']['maincontent'].='
						</tr>
						<tr>';
						foreach ($descripcion_archivo as $descripcion){
							$__data['_T']['maincontent'].='<td style="padding:3px;" valign="top">'.$descripcion.'</td>';
						}
						$__data['_T']['maincontent'].='
						</tr>
					</table>
					<br><br>
					Seleccione el archivo:<input type="file" name="data">
					<br>
					<button class="btn btn-primary">Cargar</button>
				</form>';
			break;
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'GenericoCargasMasivas.txt'=>''
		);
		if($with_data) {
			$ret['GenericoCargasMasivas.txt']=file_get_contents(dirname(__FILE__).'/GenericoCargasMasivas.txt');
		}
		return $ret;
		
	}	
}
