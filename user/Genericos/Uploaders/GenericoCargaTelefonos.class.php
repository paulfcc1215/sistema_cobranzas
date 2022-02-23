<?php
class GenericoCargaTelefonos extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Generico - Carga de Telefonos';
	}
	
	function getDescripcion() {
		return 'Generico - Carga de Telefonos';
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
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$db=DB::getInstance();
				$db->startTransaction();
				$_AM['cargas']=AutoModel::getInstance('cargas','carga',$db);
				$carga=$_AM['cargas']->insert(
					array(
						'id_proceso'=>$__data['id_proceso'],
						'descripcion'=>$_POST['descripcion_carga'],
						'fecha_carga'=>'NOW()',
						'usuario'=>$SM->user['usr_logname'],
						'tipo_carga'=>'TELEFONOS'
						)
				);
				
				$db->prepare('get_persona_in_process','SELECT * FROM personas.persona WHERE identificacion=$1 AND id_persona IN (SELECT id_deudor FROM cuentas.cuenta WHERE id_proceso='.$__data['id_proceso'].')');
				$db->prepare('carga_add_telefono','SELECT cargas.carga_add_telefono('.$carga->id_carga.',$1,$2,$3,$4)');
				$db->prepare('carga_new_persona','SELECT cargas.carga_new_persona('.$carga->id_carga.', '.$__data['id_proceso'].', $1, $2, $3, $4, $5, $6) AS id_persona');
				$csv=new Helpers_CSV(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				$csv->setWithHeader(false);
				$header=$csv->getHeader();
				$headerSize=count($header);
				foreach ($csv as $c) {
					$fuente=$c[0];
					$identificacion=$c[1];
					$primer_nombre=$c[2];
					$segundo_nombre=$c[3];
					$primer_apellido=$c[4];
					$segundo_apellido=$c[5];
					$telefonos=array();
					for ($i=6;$i<$headerSize;$i++) {
						if($c[$i]=='') continue;
						foreach($this->parseTelefonos($c[$i]) as $t) $telefonos[]=$t;
					}

					$telefonos=array_unique($telefonos);
					$persona=$db->execute('get_persona_in_process',array($identificacion));
					if ($persona->numRows()==0) {
						// hay que crear a la persona
						if (strlen($identificacion)==9 && Helpers::luhn_validate('0'.$identificacion)) {
							$tipo_identificacion='CEDULA';
							$identificacion='0'.$identificacion;
						}elseif (strlen($identificacion)==10 && Helpers::luhn_validate($identificacion)) {
							$tipo_identificacion='CEDULA';
						}elseif (strlen($identificacion)==12 && Helpers::luhn_validate(substr('0'.$identificacion,0,10))) {
							$tipo_identificacion='RUC';
							$identificacion='0'.$identificacion;
						}elseif (strlen($identificacion)==13 && Helpers::luhn_validate(substr($identificacion,0,10))) {
							$tipo_identificacion='RUC';
						}else{
							$tipo_identificacion='OTRO';
						}
						
						$persona=$db->execute('carga_new_persona',array(
							// tipo_identificacion
							$tipo_identificacion,
							// identificacion
							$identificacion,
							// primer_nombre
							$primer_nombre,
							// segundo_nombre
							$segundo_nombre,
							// primer_apellido
							$primer_apellido,
							// segundo_apellido
							$segundo_apellido,
						));
						$count['personas_creadas']++;
					}
					
					foreach($telefonos as $t) {
						if(substr($t,0,2)=='09') {
							$tipo_telefono='CELULAR';
						}else{
							$tipo_telefono='CONVENCIONAL';
						}
						$db->execute('carga_add_telefono',array(
							// id_persona
							$persona->current()['id_persona'],
							// tipo_telefono
							$tipo_telefono,
							// telefono
							$t,
							// origen
							$fuente
						));
						$count['telefonos_agregados']++;
					}					
					$count['registros_procesados']++;
				}
				insertCargaData($carga->id_carga,_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file'],$SM->carga_process['original_filename'],'DETALLES');
				
				$__data['_T']['maincontent']='<h1>Datos cargados satisfactoriamente</h1>';
				$__data['_T']['maincontent'].='<table border="1">';
				foreach($count as $k=>$v) {
					$__data['_T']['maincontent'].='<tr><th>'.$k.'</th><td>'.$v.'</td></tr>';
				}
				$__data['_T']['maincontent'].='</table>';
				
				
				$db->commit();
				
				//$uploader=new CarteraExcel(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				
				//return $uploader;
				
				
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
				$__data['_T']['maincontent']='<h1>Carga de Telefonos</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				Seleccione el archivo:
				<input type="file" name="data">
				<br>
				<button class="btn btn-primary">Cargar</button>
				</form>
				
				';
			break;
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'ModeloCargaTelefono.txt'=>''
		);
		if($with_data) {
			$ret['ModeloCargaTelefono.txt']=file_get_contents(dirname(__FILE__).'/ModeloCargaTelefono.txt');
		}
		return $ret;
		
	}	
}
