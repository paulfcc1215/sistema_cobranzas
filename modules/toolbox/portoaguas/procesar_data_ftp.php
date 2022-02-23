<?php
require dirname(__FILE__).'/PortoaguasFTP.class.php';
$strClean = array(
	'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
	'à'=>'a','è'=>'e','ì'=>'i','ò'=>'o','ù'=>'u','À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
	'ñ'=>'n','Ñ'=>'N','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u','Ä'=>'A','Ë'=>'E','Ï'=>'I',
	'Ö'=>'O','Ü'=>'U','â'=>'a','ê'=>'e','î'=>'i','ô'=>'o','û'=>'u','Â'=>'A','Ê'=>'E','Î'=>'I',
	'Ô'=>'O','Û'=>'U','ã'=>'a','õ'=>'o','Ã'=>'A','Õ'=>'O','ç'=>'c','Ç'=>'C','ß'=>'ss'
);
DB::connect('pgsql',array(
	'host'=>'192.168.180.102',
	'user'=>'postgres',
	'password'=>'postgres',
	'dbname'=>'repositorio'
	
),'repo');
$repo = DB::getInstance('repo');
$repo->query('SET SEARCH_PATH TO repository_personas');

$PA = new PortoaguasFTP('1791967437','Recapp7437');

function sanitizeName($name) {
	GLOBAL $strClean;
	$name = strtr($name, $strClean);
	$name = preg_replace('#[^A-Za-z0-9 ]#','',$name);

	return $name;
}

function telType($tel) {
	if(preg_match('#^0?9\d{8}$#',$tel)) {
		return array(
			'tel'=>$tel,
			'type'=>'cel'
		);
	}else if(preg_match('#^0[2-8]\d{7}$#',$tel)) {
		return array(
			'tel'=>$tel,
			'type'=>'lcl'
		);
	} else {
		return array(
			'tel'=>$tel,
			'type'=>'unk'
		);		
	}

}

function filterPhone($tel) {
	$tel = str_replace('o','0',strtolower($tel));
	$tel = preg_replace('#[^\d]#','',$tel);
	
	
	if(preg_match('#(\d)\1{4,}#',$tel)) {
		return '';
	}else if(preg_match('#^\d{7}$#',$tel)) {
		return '05'.$tel;
	} else if(preg_match('#^0\d\d{7}$#',$tel)) {
		return $tel;
	} else if(preg_match('#^5\d{7}$#',$tel)) {
		return '0'.$tel;
	} else if(preg_match('#^9\d{8}$#',$tel)) {
		return '0'.$tel;
	} else if(preg_match('#^09\d{8}$#',$tel)) {
		return $tel;
	}
	return '';
}

function dow($date) {
	if(is_string($date))
		$date = new DateTime($date);
	
	return $date->format('N');
}


switch($_GET['step']) {
	case '2':
		try {
			$nowDateTime = new DateTime();
			$yesterdayDateTime = new DateTime();
			$yesterdayDateTime->sub(new DateInterval('P1D'));
			
			$time = microtime(true);
			$tmpUid = 't'.uniqid();
			
			//$PA->login();
			//$data = utf8_encode($PA->download(base64_decode($_GET['f'])));
			$data = utf8_encode(file_get_contents($_FILES['data']['tmp_name']));
			
			
			//file_put_contents('/tmp/fer.bin',utf8_encode($PA->download(base64_decode($_GET['f']))));
			//$data = (file_get_contents('/tmp/fer.bin'));


			// start processing
			
			// header is fixed
			$header=array(
				'IDENTIFICACION',
				'CLIENTE',
				'CUENTA',
				'CATASTRO',
				'CIUDADELA',
				'CALLE1',
				'CALLE2',
				'CORREO_CLIENTE',
				'TLF_CONV',
				'CELULAR',
				'TIPO_CONSUMO',
				'SERVICIO',
				'ESTADO_CONEXION',
				'ESTADO',
				'RECLAMO',
				'NUM_MEDIDOR',
				'FACTURAS_VENCIDAS',
				'OBLIGACIONES_CORRIENTES',
				'OBLIGACIONES_VENCIDAS',
				'DEUDA_PORTOAGUAS',
				'SALDO_CONVENIO',
				'VENCIMIENTO_FACTURA_VENCIDA',
				'VENCIMIENTO_FACTURA_CORRIENTE',
				'LATITUD',
				'LONGITUD',
				'fecha_emision_mes',
				'valor_condonacion'
			);
			$headerSize = count($header);
			
			// replace double tabs for one tab on the whole file
			do {
				$data = str_replace("\t\t","\t",$data);
			}while(strpos($data, "\t\t")!==false);
			
			// explode
			$data = explode("\r\n",$data);
			$aux = array_pop($data);
			if(trim($aux)!='') {
				array_push($data, $aux);
			}
			
			// check if every row has the same amount of cols
			$line = 0;
			$repoMaxCount = array(
				'wcel'=>0,
				'wlcl'=>0,
				'wunk'=>0,
				'gcel'=>0,
				'glcl'=>0,
				'gunk'=>0,
			);
			$repoQuery = array();
			foreach($data as &$d) {
				$line++;
				/*
				if($line>=1000)
					break;
				*/
				$aux = explode("\t",$d);
				if(count($aux)!=$headerSize)
					throw new Exception('La data original no tiene la cantidad correcta de columnas (Linea '.$line.')');
				$d = array();
				foreach($header as $kh=>$vh) {
					$d[$vh] = trim($aux[$kh]);
				}

				// perform some validations
				if($d['LATITUD']!='null' && !preg_match('#^\d+((\.|,)\d+)?$#',$d['LATITUD']))
					throw new Exception('La columna LATITUD no contiene la estructura correcta (posiblemente las columnas del archivo están descuadradas) Linea: '.$line.' {'.$d['LATITUD'].'}'.print_r($d,true));

				if($d['LONGITUD']!='null' && !preg_match('#^\d+((\.|,)\d+)?$#',$d['LONGITUD']))
					throw new Exception('La columna LONGITUD no contiene la estructura correcta (posiblemente las columnas del archivo están descuadradas) Linea: '.$line.' {'.$d['LONGITUD'].'}'.print_r($d,true));
				
				if(!preg_match('#^\d+-\d+-\d+ 0\.0\.0\.0$#',$d['fecha_emision_mes']))
					throw new Exception('La columna fecha_emision_mes no contiene la estructura correcta (posiblemente las columnas del archivo están descuadradas) Linea: '.$line.' {'.$d['fecha_emision_mes'].'}'.print_r($d,true));
				
				if($d['VENCIMIENTO_FACTURA_VENCIDA']!='null' && !preg_match('#^\d+-\d+-\d+ 0\.0\.0\.0$#',$d['VENCIMIENTO_FACTURA_VENCIDA']))
					throw new Exception('La columna VENCIMIENTO_FACTURA_VENCIDA no contiene la estructura correcta (posiblemente las columnas del archivo están descuadradas) Linea: '.$line.' {'.$d['VENCIMIENTO_FACTURA_VENCIDA'].'}'.print_r($d,true));
				
				$d['TLF_CONV'] = filterPhone($d['TLF_CONV']);
				$d['CELULAR'] = filterPhone($d['CELULAR']);

				$usedPhones = array($d['TLF_CONV'],$d['CELULAR']);
				
				
				$d['fecha_emision_mes'] = str_replace(' 0.0.0.0','',$d['fecha_emision_mes']);
				$d['VENCIMIENTO_FACTURA_VENCIDA'] = str_replace(' 0.0.0.0','',$d['VENCIMIENTO_FACTURA_VENCIDA']);
				
				$d['CLIENTE'] = sanitizeName($d['CLIENTE']);
				$repoQuery[]='(\''.$d['IDENTIFICACION'].'\')';
				
				if($d['VENCIMIENTO_FACTURA_CORRIENTE']!='null') {
					try {
						$aux = new DateTime(implode('-',array_reverse(explode('/',$d['VENCIMIENTO_FACTURA_CORRIENTE']))));
					}catch(Exception $e) {
						throw new Exception('Fecha invalida "'.(implode('-',array_reverse(explode('/',$d['VENCIMIENTO_FACTURA_CORRIENTE'])))).'" en linea '.$line.' del archivo');
					}
					$d['VENCIMIENTO_FACTURA_CORRIENTE'] = $aux->format('Y-m-d');
				}

				if($d['fecha_emision_mes']!='null') {
					try {
						$aux = new DateTime($d['fecha_emision_mes']);
					}catch(Exception $e) {
						throw new Exception('Fecha invalida "'.($d['fecha_emision_mes']).'" en linea '.$line.' del archivo');
					}
					
					$d['fecha_emision_mes'] = $aux->format('Y-m-d');
				}
				
				if($d['VENCIMIENTO_FACTURA_VENCIDA']!='null') {
					try {
					$aux = new DateTime($d['VENCIMIENTO_FACTURA_VENCIDA']);
					}catch(Exception $e) {
						throw new Exception('Fecha invalida "'.$d['VENCIMIENTO_FACTURA_VENCIDA'].'" en linea '.$line.' del archivo');
					}
					
					$d['VENCIMIENTO_FACTURA_VENCIDA'] = $aux->format('Y-m-d');
				}
				
				// calculate dias vencidos
				if($d['VENCIMIENTO_FACTURA_VENCIDA']!='null') {
					try {
						$aux = new DateTime($d['VENCIMIENTO_FACTURA_VENCIDA']);
					}catch(Exception $e) {
						throw new Exception('Fecha invalida "'.$d['VENCIMIENTO_FACTURA_VENCIDA'].'" en linea '.$line.' del archivo');
					}
					$diff = $aux->diff($nowDateTime);
					$d['_dias_vencidos'] = $diff->days;
				}else{
					try {
						$aux = new DateTime($d['VENCIMIENTO_FACTURA_CORRIENTE']);
					}catch(Exception $e) {
						print_arr($d);
						throw new Exception('Fecha invalida "'.$d['VENCIMIENTO_FACTURA_CORRIENTE'].'" en linea '.$line.' del archivo');
					}
					$diff = $aux->diff($nowDateTime);
					$d['_dias_vencidos'] = $diff->days;
				}
				
				// calculate plan
				/*
				si fecha_emision_mes es el dia anterior o la fecha cae en viernes sabado o domingo entonces => SMS1

				si "vencimiento_factura_vencida" == hoy+1
				  entonces SMS2
				fin

				case dias de vencimiento as x
				   1 <= x <= 60
					 SMS4
				   61 <= x <= 150
					 SMS5
				   151 <= x then
					  if FACTURAS_VENCIDAS > 5 AND DEUDA_PORTOAGUAS <= 80$
						 SMS6
					  else if FACTURAS_VENCIDAS > 5 AND DEUDA_PORTOAGUAS > 80$ 
						 SMS7
					  else
						 SMS5
					  end
				*/
				// plan 0 days
				$aux = new DateTime($d['fecha_emision_mes']);
				$dow = dow($aux);
				//$diff = $aux->diff($nowDateTime);
				
				
				
				if(/*$dow==5 || $dow == 6 || $dow == 7 || */$aux->format('Y-m-d') == $yesterdayDateTime->format('Y-m-d')) {
					$d['_plan_0'] = 'SMS1';
				}
				
				if($d['VENCIMIENTO_FACTURA_VENCIDA']!='null') {
					$aux = new DateTime($d['VENCIMIENTO_FACTURA_VENCIDA']);
					$diff = $aux->diff($nowDateTime);
					
					if(/*$diff->invert && */$diff->days == 1) {
						$d['_plan_1']='SMS2';
					}
				}
				

				if($d['_dias_vencidos']>=1 && $d['_dias_vencidos']<=60) {
					$d['_plan_2']='SMS4';
				}else if($d['_dias_vencidos']>=61 && $d['_dias_vencidos']<=150) {
					$d['_plan_2']='SMS5';
				}else if($d['_dias_vencidos']>=151) {
					if($d['FACTURAS_VENCIDAS']>5) {
						if($d['DEUDA_PORTOAGUAS']<=80) {
							$d['_plan_2']='SMS6';
						}else{
							$d['_plan_2']='SMS7';
						}
					} else {
						$d['_plan_2']='SMS5';
					}
				}
				
				unset($d);
			}
			
			
			
			$repo->query('CREATE TEMPORARY TABLE '.$tmpUid.'_data (cedula varchar(15))');
			$repo->query('INSERT INTO '.$tmpUid.'_data (cedula) VALUES '.implode(',',$repoQuery));
			
			$repo->query('CREATE TEMPORARY TABLE '.$tmpUid.'_cruce (cedula text, numero_telefono text, fuente text)');
			$repo->query('INSERT INTO '.$tmpUid.'_cruce SELECT pe_numero_identificacion, numero_telefono, \'w\' FROM '.$tmpUid.'_data d JOIN lista_blanca_telefonos lbt ON (lbt.pe_numero_identificacion = d.cedula)');
			$repo->query('INSERT INTO '.$tmpUid.'_cruce SELECT pe_numero_identificacion, numero_telefono, \'g\' FROM '.$tmpUid.'_data d JOIN telefono lbt ON (lbt.pe_numero_identificacion = d.cedula)');
			
			$repo->query('COPY (SELECT * FROM '.$tmpUid.'_cruce) TO \'/var/www/html/referidos/tmp/'.$tmpUid.'_cruce\' WITH CSV HEADER DELIMITER \';\'');
			file_put_contents('/tmp/'.$tmpUid.'_cruce',(file_get_contents('http://192.168.180.102/getTmp.php?n='.base64_encode($tmpUid.'_cruce').'&del=1')));
			
			$db->query('CREATE TEMPORARY TABLE '.$tmpUid.'_cruce (cedula TEXT,numero_telefono TEXT,fuente TEXT)');
			$db->query('COPY '.$tmpUid.'_cruce FROM \'/tmp/'.$tmpUid.'_cruce\' WITH CSV HEADER DELIMITER \';\'');
			$db->query('CREATE INDEX ON '.$tmpUid.'_cruce USING BTREE("cedula")');
			unlink('/tmp/'.$tmpUid.'_cruce');
			
			// now we do de thing with repo
			foreach($data as &$d) {
				$d['_repo']=array('w'=>array('lcl'=>array(),'cel'=>array(),'unk'=>array()),'g'=>array('lcl'=>array(),'cel'=>array(),'unk'=>array()));
				$usedPhones = array();
				foreach($db->query('SELECT numero_telefono,fuente FROM '.$tmpUid.'_cruce WHERE cedula=\''.$d['IDENTIFICACION'].'\'') as $lb) {
					if(in_array($lb['numero_telefono'],$usedPhones))
						continue;
					if(count($usedPhones)>=10)
						break;
					$aux = telType($lb['numero_telefono']);
					$d['_repo'][$lb['fuente']][$aux['type']][]=$aux['tel'];
					$usedPhones[] = $lb['numero_telefono'];
				}
				foreach($d['_repo'] as $fuente=>$repoData) {
					foreach($repoData as $type=>$tels) {
						$telC = count($tels);
						if($repoMaxCount[$fuente.$type]<$telC)
							$repoMaxCount[$fuente.$type] = $telC;
					}
				}
				unset($d);
			}

			// now generate report
			$report=array($header);
			$report[0][]='_dias_vencidos';
			$report[0][]='_plan_0';
			$report[0][]='_plan_1';
			$report[0][]='_plan_2';
			foreach(array('wcel'=>'blanca_cel','wlcl'=>'blanca_conv','wunk'=>'blanca_desc','gcel'=>'gris_cel','glcl'=>'gris_local','gunk'=>'gris_desc') as $type=>$typeTrans) {
				$j=0;
				for($i=1;$i<=$repoMaxCount[$type];$i++) {
					$j++;
					$type2 = $typeTrans.'_'.$j;
					$report[0][]=$type2;
				}
			}
			$reportHeaderSize = count($report[0]);
			$report[0] = implode("\t",$report[0]);
			
			foreach($data as $d) {
				$line=array();
				foreach($header as $h) {
					$line[]=$d[$h];
				}
				$line[]=$d['_dias_vencidos'];
				$line[]=$d['_plan_0'];
				$line[]=$d['_plan_1'];
				$line[]=$d['_plan_2'];
				foreach(array(
					'wcel'=>&$d['_repo']['w']['cel'],
					'wlcl'=>&$d['_repo']['w']['lcl'],
					'wunk'=>&$d['_repo']['w']['unk'],
					'gcel'=>&$d['_repo']['g']['cel'],
					'glcl'=>&$d['_repo']['g']['lcl'],
					'gunk'=>&$d['_repo']['g']['unk']
				) as $type=>$target) {
					$tCount=0;
					foreach($target as $t) {
						$tCount++;
						$line[]=$t;
					}
					while($tCount<$repoMaxCount[$type]) {
						$line[]='';
						$tCount++;
					}
				}
				
				$report[]=implode("\t",$line);
			}
			
			header('Content-Type: application/octect-stream');
			header('Content-Disposition: Attachment; filename='.basename(base64_decode($_GET['f'])));
			echo implode("\r\n",$report);
			
			
			die();
		}catch(Exception $e) {
			print_arr($e);
			die();
		}
		
	break;
	
	default:

		$_T['maincontent'].='
		<form method="POST" enctype="multipart/form-data" action="?mod='.$_GET['mod'].'&step=2">
		<h2>Procesamiento Plan de Trabajo - Portoaguas</h2>
		Indique el archivo a procesar:
		<br>
		<input type="file" name="data">
		<br>
		<button class="btn btn-primary">Procesar</button>
		</form>';
		
	break;
}