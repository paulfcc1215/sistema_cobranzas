<?php
$_MESES=array(1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');

function print_arr($arr,$return=false) {
    $ret='<pre>';
    $ret.=print_r($arr,true);
    $ret.='</pre>';
    if($return) return $ret;
    echo $ret;
}

if (!function_exists('password_hash')) {
    function password_hash($strPassword, $numAlgo = 1, $arrOptions = array()) {
        $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
        $salbluet = base64_encode($salt);
        $salt = str_replace('+', '.', $salt);
        $hash = crypt($strPassword, '$2y$10$' . $salt . '$');
        return $hash;
    }
}

if(!function_exists('password_verify')) {
    function password_verify($strPassword, $strHash) {
        $strHash2 = crypt($strPassword, $strHash);
        $boolReturn = $strHash == $strHash2;
        return $boolReturn;
    }
}

function encrypt($source) {
	
    $cipher=_ENCRYPTION_CIPHER_ALGORITHM;
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
	
	$md5=md5($source);
	$checksum=openssl_encrypt($md5,$cipher,_ENCRYPTION_KEY,0,$iv);
	$ciphertext=openssl_encrypt($source,$cipher,_ENCRYPTION_KEY,0,$iv);
	
    
	$crypt=base64_encode((implode('$!$',array(base64_encode($iv),$checksum,$ciphertext))));
    return $crypt;
    
}

function decrypt($source) {
    $cipher=_ENCRYPTION_CIPHER_ALGORITHM;
    $source=explode('$!$',base64_decode($source));
    if(count($source)!=3) return false;
    $iv=base64_decode($source[0]);
    $checksum=openssl_decrypt($source[1],$cipher,_ENCRYPTION_KEY,0,$iv);
	$plaintext=openssl_decrypt($source[2],$cipher,_ENCRYPTION_KEY,0,$iv);
	
	if(md5($plaintext)!=$checksum) return false;
	return $plaintext;
}


function debug_get_callstack() {
    $bt=debug_backtrace();
    $ret=array();
    foreach($bt as $b) {
        $ret[]=$b['class'].'::'.$b['function'].'::'.$b['line'];
    }
    return $ret;
}

function udnGetStandardizedName($id_udn) {
	$_AM['udns']=AutoModel::getInstance('estructura','udn',DB::getInstance());
	$udn=$_AM['udns']->getById($id_udn);
	$udn2=preg_replace('#[^a-z0-9_ \-]#','',strtolower($udn->udn));
	$udn2=str_replace(' ','_',$udn2);
	$udn2=str_replace('-','_',$udn2);
	return $udn->id_udn.'-'.$udn2;
}

function getStandardizedName($str) {
	$str=preg_replace('#[^a-z0-9_ \-]#','',strtolower($str));
	$str=str_replace(' ','_',$str);
	$str=str_replace('-','_',$str);
	return $str;
}


// ----
function getCargasHandler($id_camp) {
	$_AM['udns']=AutoModel::getInstance('estructura','udn',DB::getInstance());
	$_AM['campanas']=AutoModel::getInstance('campanas','campana',DB::getInstance());
	
	$camp=$_AM['campanas']->getById($id_camp);
	if(!$camp)
		throw new Exception('Campaña no existe');
	$udn=$_AM['udns']->getById($camp->id_udn);
	if(!Auth::hasEmpresa($udn->id_empresa))
		throw new Exception('No tiene privilegios para esta empresa');
	
	if(!is_readable(_BASE_USER_PATH.'/'.$camp->carpeta_control.'/Uploaders'))
		throw new Exception('La carpeta "Uploaders" en control no puede ser leida');
	$target_folders=array(
		'Genericos',
		$camp->carpeta_control
	);
	foreach($target_folders as $tf) {
		$dhdl=opendir(_BASE_USER_PATH.'/'.$tf.'/Uploaders');
		while($ptr=readdir($dhdl)) {
			if($ptr=='..' || $ptr=='.') continue;
			if(substr($ptr,-10)!='.class.php') continue;
			if(is_dir(_BASE_USER_PATH.'/'.$tf.'/Uploaders/'.$ptr)) continue;
			require_once _BASE_USER_PATH.'/'.$tf.'/Uploaders/'.$ptr;
			$clazz=str_replace('.class.php','',$ptr);
			$class=new $clazz();
			
			$parents=class_parents($class);
			$interfaces=class_implements($class);
			if(
				!in_array('CargaModelo_Handler_Abstract',$parents)
			) continue;
			
			$ret[]=array(
				'fname'=>(_BASE_USER_PATH.'/'.$tf.'/Uploaders/'.$ptr),
				'tipo'=>$class->getTipoBase(),
				'desc'=>$class->getDescripcion(),
			);

		}
		closedir($dhdl);
	}
	return $ret;
}

function getCarpetasControles() {
	$dhdl=opendir(_BASE_USER_PATH);
	$folders=array();
	while(($ptr=readdir($dhdl))!=false) {
		if($ptr=='.' || $ptr=='..') continue;
		if(!is_dir(_BASE_USER_PATH.'/'.$ptr)) continue;
		$folders[]=$ptr;
	}
	sort($folders);
	return $folders;
	
}

function getProcesosByCampId($camp_id, $include_disabled=true) {
	$db=DB::getInstance();
	//$_AM['udns']=AutoModel::getInstance('estructura','udn',DB::getInstance());
	$_AM['campanas']=AutoModel::getInstance('campanas','campana',DB::getInstance());
	$_AM['proceso']=AutoModel::getInstance('campanas','proceso',DB::getInstance());
	if(!preg_match('#^\d+$#',$camp_id)) throw new Exception('Id Campaña Inválida');
	$campana=$_AM['campanas']->getById($camp_id);
	if(!$campana) throw new Exception('Campana invalida');
	$procesos=$_AM['proceso']->getByAndCond(array('id_campana'=>$camp_id));

	$ret=array();
	foreach($procesos as $p) {
		if($include_disabled){
			$ret[]=array(
				'id_proceso'=>$p->id_proceso,
				'descripcion'=>$p->descripcion
			);
		}else{
			if ($p->status){
				$ret[]=array(
					'id_proceso'=>$p->id_proceso,
					'descripcion'=>$p->descripcion
				);
			}
		}
	}
	rsort($ret);
	return $ret;
}

function getCargasByProcesoId($id_proceso, $order_by='fecha_carga DESC'){
	$ret=array();
	$db=DB::getInstance();
	foreach ($db->query('SELECT * FROM cargas.carga WHERE id_proceso='.$id_proceso.' ORDER BY '.$order_by) as $carga) {
		$ret[$carga['id_carga']]=$carga;
	}
	return $ret;
}

function getCargasByCuenta($id_cuenta){
	$ret = array();
	$db=DB::getInstance();
	foreach ($db->query('SELECT DISTINCT(sc.id_carga),date(ca.fecha_carga) as fecha_carga FROM cuentas.cuenta c JOIN cargas.carga_seg_cuentas sc on (c.cuenta=sc.numero_cuenta) JOIN cargas.carga ca on (ca.id_carga=sc.id_carga) WHERE c.id_cuenta='.$id_cuenta)->fetchAll() as $value) {
		$ret[$value['id_carga']] = $value['fecha_carga'];
	}
	return $ret;
}

function metadata_save($config_string,$fk_tabla,$fk_valor) {
	$_AM['metadata']=AutoModel::getInstance('metadata','metadata',DB::getInstance());
	
	$config=metadata_parse_string($config_string);
	
	foreach($_AM['metadata']->getByAndCond(array(
		'fk_tabla'=>$fk_tabla,
		'fk_valor'=>$fk_valor,
		'status'=>'1'
	)) as $m) $m->delete();

	foreach($config as $k=>$v) {
		if(is_array($v)) {
			$is_array='1';
			$v=implode(',',$v);
		}else{
			$is_array='0';
		}
		
		$_AM['metadata']->insert(
			array(
				'fk_tabla'=>$fk_tabla,
				'fk_valor'=>$fk_valor,
				'key'=>$k,
				'value'=>$v,
				'is_array'=>$is_array,
				'status'=>'1'
			)
		);
	}
	
}

function metadata_load_usable($aplicable_a) {
	$_AM['metadata_usable']=AutoModel::getInstance('metadata','metadata_usable',DB::getInstance());
	$aux=array();
	foreach($_AM['metadata_usable']->getByAndCond(array('aplicable_a'=>$aplicable_a)) as $r) {
		$aux[]=$r->toArray();
	}
	return $aux;
	
}

function metadata_load_config_string($fk_tabla,$fk_valor) {
	$_AM['metadata']=AutoModel::getInstance('metadata','metadata',DB::getInstance());
    $md=$_AM['metadata']->getByAndCond(array(
        'fk_tabla'=>$fk_tabla,
        'fk_valor'=>$fk_valor,
        'status'=>'1'
    ));
    $configs=array();
    foreach($md as $m) {
        if($m->is_array=='1') {
            $configs[$m->key]=explode(',',$m->value);
        }else{
            $configs[$m->key]=$m->value;
        }
    }
    $configs=base64_encode(json_encode($configs));
    return $configs;
}

function metadata_parse_string($string) {
	return json_decode(base64_decode($string),true);
}

function db_get_sps($db) {
	$q=$db->query('SELECT pro.oid,ns.nspname,pro.proname,pro.proargtypes,pro.prorettype,format_type(pro.prorettype,null) as ret_type FROM pg_proc pro JOIN pg_namespace ns ON (ns.oid=pro.pronamespace) WHERE nspname NOT IN (\'pg_catalog\',\'information_schema\')');
	$ret=array();
	foreach($q as $p) {
		$add=array(
			'name'=>$p['proname'],
			'args'=>$p['proargtypes'],
			'ret'=>$p['prorettype'],
			'ret_type'=>$p['ret_type'],
			'comment'=>''
		);
		$query='SELECT * FROM pg_description d WHERE d.objoid='.$p['oid'];
		$q2=$db->query($query)->current();
		if($q2!==false) {
			$add['comment']=$q2['description'];
		}
		$ret[$p['nspname']][]=$add;
	}
	return $ret;
	
}

function bulkMkdir($path,$dirs) {
	foreach($dirs as $f) {
		if(!mkdir($path.'/'.$f))
			throw new Exception('Error al crear sub carpeta "'.$path.'/'.$f.'"');
	}
	return true;
}

function bulkRmdir($path,$dirs) {
	foreach($dirs as $f) {
		if(!mkdir($path.'/'.$f))
			throw new Exception('Error al crear sub carpeta "'.$path.'/'.$f.'"');
	}
	return true;
}

function getFileUploadErrorString($error) {
	switch($error) {
		case '0':
			return 'There is no error, the file uploaded with success.';
		break;
		case '1':
			return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
		break;
		case '2':
			return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
		break;
		case '3':
			return 'The uploaded file was only partially uploaded.';
		break;
		case '4':
			return 'No file was uploaded.';
		break;
		case '6':
			return 'Missing a temporary folder. Introduced in PHP 5.0.3.';
		break;
		case '7':
			return 'Failed to write file to disk. Introduced in PHP 5.1.0.';
		break;
		case '8':
			return 'A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help. Introduced in PHP 5.2.0.';
		break;
	}
	return 'Unknown Error';
}

// -------------------------
function _getPersona($id_persona,&$referencias,$get_referencias=true,$get_telefonos=true,$get_medios_contacto=true,$get_direcciones=true) {
	$db=DB::getInstance();
	$id_persona=$db->escape($id_persona);
	$q = 'SELECT * FROM personas.persona WHERE persona.id_persona='.$id_persona;
	$persona=$db->query($q)->current();
	if(!$persona) return false;
	if($get_telefonos) {
		//get telefonos gestionados
		$persona['telefonos_gestionados']=array();
		$q = 'SELECT 
			to_char(g.fecha_inicio, \'YYYY-MM-DD\') AS fecha_gestion,
			to_char(g.fecha_inicio, \'HH24:MI:SS\') AS hora_gestion,
			g.id_gestion,g.id_cuenta,g.tel_number,g.fecha_inicio,g.user_name,
			t.descripcion,
			c.id_proceso,
			(CASE WHEN tm.es_contacto_primera_persona=\'1\' OR tm.es_contacto_tercero=\'1\' THEN \'1\' ELSE \'0\' END) AS contacto,
			\'1\' AS tiene_gestion,
			(SELECT campana FROM campanas.campana WHERE id_campana=(SELECT id_campana FROM campanas.proceso WHERE id_proceso=c.id_proceso)) as fuente
		FROM gestiones.gestion g
			JOIN cuentas.cuenta c ON(c.id_cuenta=g.id_cuenta)
			JOIN gestiones.tipificacion t ON(t.id_tipificacion=g.id_tipificacion)
			JOIN gestiones.tipificacion_metadata tm ON(tm.id_tipificacion=t.id_tipificacion)
		WHERE 
			c.id_deudor='.$id_persona.'
		ORDER BY contacto DESC,g.fecha_inicio DESC';

		$tmp_telefonos = array();
		foreach($db->query($q) as $tq) {
			if (!in_array($tq['tel_number'],$tmp_telefonos)){
				$tmp_telefonos[]=$tq['tel_number'];
				if ($tq['contacto']){
					$persona['telefonos_gestionados']['contacto'][]=$tq;
				}else{
					$persona['telefonos_gestionados']['sin_contacto'][]=$tq;
				}
			}
		}
		//get telefonos
		$persona['telefonos']=array();
		foreach($db->query('SELECT * FROM medios_contacto.telefono WHERE id_persona='.$id_persona) as $tq) {
			if (!in_array($tq['telefono'],$tmp_telefonos)){
				$tmp_telefonos[]=$tq['telefono'];
				$persona['telefonos'][]=$tq;
			}
		}
		//get telefonos repositorio
		foreach(_getTelefonosRepositorio($persona['identificacion']) as &$tq) {
			if (!in_array($tq['numero_telefono'],$tmp_telefonos)){
				$tmp_telefonos[]=$tq['numero_telefono'];
				if ($tq['clasificacion_telefono']=='lista_blanca'){
					$tq['id_persona'] = $persona['id_persona'];
					$tq['tel_number'] = $tq['numero_telefono'];
					$tq['fecha_gestion'] = $tq['fecha_insercion'];
					$tq['descripcion'] = $tq['clasificacion_telefono'];
					unset($tq['numero_telefono']);
					unset($tq['fecha_insercion']);
					$persona['telefonos_gestionados']['contacto'][]=$tq;
				}else{
					$tq['id_persona'] = $persona['id_persona'];
					$tq['telefono'] = $tq['numero_telefono'];
					$tq['fecha_inicio'] = $tq['fecha_insercion'];
					unset($tq['numero_telefono']);
					unset($tq['fecha_insercion']);
					$persona['telefonos'][]=$tq;
				}
			}
		}
		
	}
	if($get_medios_contacto) {
		$persona['medios_contacto']=array();
		foreach($db->query('SELECT * FROM medios_contacto.medio_contacto WHERE id_persona='.$id_persona) as $tq) {
			$persona['medios_contacto'][]=$tq;
		}
	}
	if($get_referencias) {
		$persona['relaciones']=array();
		foreach($db->query('SELECT * FROM personas.persona_relacion WHERE id_persona_1='.$id_persona.' OR id_persona_2='.$id_persona) as $pr) {
			$aux2=$pr['id_persona_1'];
			if($aux2==$id_persona) $aux2=$pr['id_persona_2'];
			if(in_array($aux2,$referencias)) continue;
			$referencias[]=$aux2;
			$aux=array(
				'tipo_relacion'=>$pr['tipo_relacion'],
				'persona'=>_getPersona($aux2,$referencias)
			);
			$persona['relaciones'][]=$aux;
		}
	}
	if($get_direcciones){
		$persona['direcciones']=array();
		foreach($db->query('SELECT * FROM medios_contacto.direccion WHERE id_persona='.$id_persona.' AND status=\'1\'') as $d) {
			$persona['direcciones'][]=$d;
		}
	}

	return $persona;
}

function getPersona($id_persona,$get_referencias=true,$get_telefonos=true,$get_medios_contacto=true) {
	$referencias=array($id_persona);
	return _getPersona($id_persona,$referencias,$get_referencias,$get_telefonos,$get_medios_contacto);
}

function _getTelefonosRepositorio($identificacion){
	$identificacion = substr($identificacion,0,10);
	
	try{
		$db_repo = DB::getInstance('repo');
		if (is_null($db_repo)) throw new exception('No existe conexion a repositorio');
		// var_dump($db_repo);
		// die();
		$q = 'SELECT
			p.primer_nombre,p.segundo_nombre,p.primer_apellido,p.segundo_apellido,
			t.pe_numero_identificacion,t.tipo_telefono,t.numero_telefono,t.fecha_insercion,
			(CASE WHEN t.prioridad_telefono IS NOT NULL THEN \'lista_blanca\' ELSE \'lista_gris\' END) AS clasificacion_telefono,
			\'Repositorio\' AS fuente
		FROM repository_personas.telefono t
		JOIN repository_personas.persona p ON(p.pe_numero_identificacion=t.pe_numero_identificacion)
		WHERE t.pe_numero_identificacion = \''.$db_repo->escape($identificacion).'\'
		ORDER BY t.fecha_insercion DESC';
		$rows = array();
		$result = $db_repo->query($q);
	}catch(exception $ex){
		Log::addLog('MINERIA_REPO',__FILE__,array('error'=>$ex->getMessage()));
		return array();
	};
	foreach ($result as $row){
		$rows[]=$row;
	}
	return $rows;
}

function getProceso($id_proceso) {
	GLOBAL $_CACHE;
	if(!array_key_exists($id_proceso,$_CACHE['procesos'])) {
		$db=DB::getInstance();
		$aux=$db->query('SELECT * FROM campanas.proceso WHERE id_proceso='.$db->escape($id_proceso));
		if($aux->numRows()!=1) return false;
		$_CACHE['procesos'][$id_proceso]=$aux->current();
	}
	return $_CACHE['procesos'][$id_proceso];
}

function getProcesoByCampana($id_campana,$order_by=' ORDER BY id_proceso DESC') {
	$db=DB::getInstance();
	$ret=array();
	$q='SELECT * FROM campanas.proceso WHERE id_campana=\''.$db->escape($id_campana).'\'';
	$q.=$order_by;
	foreach($db->query($q) as $p) {
		$ret[]=getProceso($p['id_proceso']);
	}
	return $ret;
}

function getCampana($id_campana) {
	GLOBAL $_CACHE;
	if(!array_key_exists($id_campana,$_CACHE['campanas'])) {
		$db=DB::getInstance();
		$aux=$db->query('SELECT * FROM campanas.campana WHERE id_campana='.$db->escape($id_campana));
		if($aux->numRows()!=1) return false;
		$_CACHE['campanas'][$id_campana]=$aux->current();
        $_CACHE['campanas'][$id_campana]['hooks']=array();
        foreach($db->query('SELECT * FROM hooks.hooks WHERE id_campana=\''.$db->escape($id_campana).'\' ORDER BY "order" ASC') as $hook) {
            $_CACHE['campanas'][$id_campana]['hooks'][$hook['hook_type']][]=$hook;
        }
	}
	return $_CACHE['campanas'][$id_campana];
}

function getCampanasByUdn($id_udn) {
	$db=DB::getInstance();
	$ret=array();
	foreach($db->query('SELECT * FROM campanas.campana WHERE id_udn=\''.$db->escape($id_udn).'\'') as $c) {
		$ret[]=getCampana($c['id_campana']);
	}
	return $ret;
}

function getEmpresa($id_empresa) {
	GLOBAL $_CACHE;
	if(!array_key_exists($id_empresa,$_CACHE['empresas'])) {
		$db=DB::getInstance();
		$aux=$db->query('SELECT * FROM estructura.empresa WHERE id_empresa='.$db->escape($id_empresa));
		if($aux->numRows()!=1) return false;
		$_CACHE['empresas'][$id_empresa]=$aux->current();
	}
	return $_CACHE['empresas'][$id_empresa];
}

function getUdn($id_udn) {
	GLOBAL $_CACHE;
	if(!array_key_exists($id_udn,$_CACHE['udns'])) {
		$db=DB::getInstance();
		$aux=$db->query('SELECT * FROM estructura.udn WHERE id_udn='.$db->escape($id_udn));
		if($aux->numRows()!=1) return false;
		$_CACHE['udns'][$id_udn]=$aux->current();
	}
	return $_CACHE['udns'][$id_udn];
}

function getUdnByName($name) {
	$db=DB::getInstance();
	$query='SELECT id_udn FROM estructura.udn WHERE LOWER(udn)=LOWER(\''.$db->escape($name).'\')';
	$q0=$db->query($query);
	if($q0->numRows()==0) return false;
	
	return getUdn($q0->current()['id_udn']);
}

function getCarga($id_carga) {
	GLOBAL $_CACHE;
	if(!array_key_exists($id_carga,$_CACHE['cargas'])) {
		$db=DB::getInstance();
		$aux=$db->query('SELECT * FROM cargas.carga WHERE id_carga='.$db->escape($id_carga));
		if($aux->numRows()!=1) return false;
		$_CACHE['cargas'][$id_carga]=$aux->current();
	}
	return $_CACHE['cargas'][$id_carga];
}

function getCuenta($id_cuenta) {
	GLOBAL $_CACHE;
	if(!array_key_exists($id_cuenta,$_CACHE['cuentas'])) {
		$db=DB::getInstance();
		$aux=$db->query('SELECT * FROM cuentas.cuenta WHERE id_cuenta='.$db->escape($id_cuenta));
		if($aux->numRows()!=1) return false;
		$_CACHE['cuentas'][$id_cuenta]=$aux->current();
	}
	return $_CACHE['cuentas'][$id_cuenta];
}

function getCuentaByCuentaAndProcess($cuenta,$id_proceso) {
	$db=DB::getInstance();
	$aux=$db->query('SELECT * FROM cuentas.cuenta WHERE cuenta=\''.$db->escape($cuenta).'\' AND id_proceso='.$db->escape($id_proceso));
	if($aux->numRows()!=1) return false;
	return $aux->current();
}

function getActualizaciones($id_cuenta, $order_by='ORDER BY id_cuenta_actualizacion ASC') {
	$db=DB::getInstance();
	$ret=array();
	foreach($db->query('SELECT * FROM cuentas.cuenta_actualizacion WHERE id_cuenta='.$id_cuenta.' '.$order_by) as $a) {
		$ret[]=$a;
	}
	return $ret;
}

function getTipificacion($id_tipificacion) {
	GLOBAL $_CACHE;
	if(!array_key_exists($id_tipificacion,$_CACHE['tipificaciones'])) {
		$db=DB::getInstance();
		$aux=$db->query('SELECT * FROM gestiones.tipificacion WHERE id_tipificacion='.$db->escape($id_tipificacion));
		if($aux->numRows()!=1) return false;
		$_CACHE['tipificaciones'][$id_tipificacion]=$aux->current();
		$_CACHE['tipificaciones'][$id_tipificacion]['_metadata']=array();
		
		$aux=$db->query('SELECT * FROM gestiones.tipificacion_metadata WHERE id_tipificacion='.$db->escape($id_tipificacion));
		$md=array();
		foreach($aux as $a) {
			foreach($a as $k=>$v) {
				if(in_array($k,array('id_tipificacion_metadata','id_tipificacion'))) continue;
				$md[$k]=$v;
				//if($v=='1' || $v=='0') {
				//	$md[$k]=($v=='1');
				//}else{
				//	$md[$k]=$v;
				//}
			}
			$_CACHE['tipificaciones'][$id_tipificacion]['_metadata']=$md;
		}
	}
	return $_CACHE['tipificaciones'][$id_tipificacion];

}

function getTipificacionesByCampana($id_campana,$include_disabled=false,$include_no_mostrar_agente=false) {
	GLOBAL $_CACHE;
	$db=DB::getInstance();
	if(!array_key_exists($id_campana,$_CACHE['tipificaciones_by_campana'])) {
		$_CACHE['tipificaciones_by_campana'][$id_campana]=array();
		$query='SELECT
			id_tipificacion
		FROM campanas.tipificacion_cat_campana catcamp
			JOIN gestiones.catalogo_tipificacion cat USING (id_cat_tipificacion)
			JOIN gestiones.tipificacion T USING (id_cat_tipificacion)		
		WHERE catcamp.id_campana=\''.$db->escape($id_campana).'\'';
		if(!$include_disabled) {
			$query.=' AND t.status=\'1\' ';
		}
		if(!$include_no_mostrar_agente) {
			$query.=' AND t.mostrar_agente=\'1\' ';
		}
		$query.='ORDER BY t.peso DESC';
		foreach($db->query($query) as $t) {
			$t=getTipificacion($t['id_tipificacion']);
			$_CACHE['tipificaciones_by_campana'][$id_campana][]=$t;
		}
	}
	return $_CACHE['tipificaciones_by_campana'][$id_campana];
}

function getGestiones($id_cuenta) {
	$db=DB::getInstance();
	$ret=array();
	foreach($db->query('SELECT * FROM gestiones.gestion WHERE id_cuenta='.$id_cuenta.' ORDER BY fecha_inicio DESC') as $a) {
		$a['_tipificacion']=getTipificacion($a['id_tipificacion']);
		$ret[]=$a;
	}
	return $ret;
}


function getGestionPanelData($id_cuenta,$id_cuenta_adicionales=array()) {
	$BM=new Benchmark();
	$db=DB::getInstance();

	$BM->mark('seleccion_cuenta_puntual');
	$aux_cuentas=array();
	// seleccionamos las cuentas puntuales
    $id_cuenta = $db->escape($id_cuenta);
	$aux_cuentas[]=$db->query('SELECT
		c.*,
		p.tipo_identificacion,
		p.identificacion
	FROM
		cuentas.cuenta c
	JOIN personas.persona p ON (c.id_deudor=p.id_persona)
	WHERE
		c.id_cuenta=\''.$id_cuenta.'\'')->current();

	$BM->mark('seleccion_cuenta_puntual');
	// agregamos las otras cuentas que pertenecen a la misma cedula y esten en el mismo proceso
	$BM->mark('buscar_cuentas_de_cedula');
    $query='SELECT
		c.*,
		p.tipo_identificacion,
		p.identificacion
	FROM
		cuentas.cuenta c
	JOIN personas.persona p ON (c.id_deudor=p.id_persona)
	JOIN campanas.proceso pr ON (c.id_proceso=pr.id_proceso)
	WHERE
        c.id_cuenta<>'.$aux_cuentas[0]['id_cuenta'].'
		AND p.identificacion=\''.$aux_cuentas[0]['identificacion'].'\'
		AND c.id_proceso='.$aux_cuentas[0]['id_proceso'];
    foreach($id_cuenta_adicionales as &$ic) {
        $ic = $db->escape($ic);
        unset($ic);
    }
    if(!empty($id_cuenta_adicionales)) {
        $query.=' OR c.id_cuenta IN (\''.implode('\',\'',$id_cuenta_adicionales).'\')';
    }

	foreach($db->query($query) as $q) {
		$aux_cuentas[]=$q;
	}
	
	$BM->mark('buscar_cuentas_de_cedula');
	$data=array();
	foreach($aux_cuentas as $aux_cuenta) {
		$id_cuenta=$aux_cuenta['id_cuenta'];
		
		// proceso
		$BM->mark('proceso');
		$data['cuentas'][$id_cuenta]['proceso']=getProceso($aux_cuenta['id_proceso']);
		$BM->mark('proceso');
		// campana
		$BM->mark('campana');
		$data['cuentas'][$id_cuenta]['campana']=getCampana($data['cuentas'][$id_cuenta]['proceso']['id_campana']);
		$BM->mark('campana');
		// udn
		$BM->mark('udn');
		$data['cuentas'][$id_cuenta]['udn']=getUdn($data['cuentas'][$id_cuenta]['campana']['id_udn']);
		$BM->mark('udn');
		// empresa
		$BM->mark('empresa');
		$data['cuentas'][$id_cuenta]['empresa']=getEmpresa($data['cuentas'][$id_cuenta]['udn']['id_empresa']);
		$BM->mark('empresa');
		// carga
		$BM->mark('carga');
		$data['cuentas'][$id_cuenta]['carga']=getCarga($aux_cuenta['id_carga']);
		$BM->mark('carga');
		// cuenta
		$BM->mark('cuenta');
		$data['cuentas'][$id_cuenta]['cuenta']=getCuenta($id_cuenta);
		$BM->mark('cuenta');
		// persona
		$BM->mark('persona');
		$data['cuentas'][$id_cuenta]['deudor']=getPersona($data['cuentas'][$id_cuenta]['cuenta']['id_deudor']);
		$BM->mark('persona');
		
		// personas asociadas
		$BM->mark('personas_asociadas');
		foreach($db->query('SELECT
				cr.tipo_responsable,
				persona.id_persona
			FROM
				cuentas.cuenta_responsable cr 
				JOIN personas.persona USING (id_persona)
			WHERE
				cr.id_cuenta=\''.$id_cuenta.'\'') as $p) 
		{
			$aux=getPersona($p['id_persona']);
			$aux['tipo_responsable']=$p['tipo_responsable'];
			$data['cuentas'][$id_cuenta]['otras_personas'][]=$aux;
		}
		$BM->mark('personas_asociadas');
		
		// actualizaciones
		$BM->mark('actualizaciones');
		$data['cuentas'][$id_cuenta]['actualizaciones']=array();
		foreach($db->query('SELECT * FROM cuentas.cuenta_actualizacion WHERE id_cuenta='.$id_cuenta.' ORDER BY fecha_actualizacion DESC') as $a) {
			$data['cuentas'][$id_cuenta]['actualizaciones'][]=$a;
		}
		$BM->mark('actualizaciones');
		
		// gestiones
		$BM->mark('gestiones');
		$data['cuentas'][$id_cuenta]['gestiones']=array();
		foreach($db->query('SELECT * FROM gestiones.gestion WHERE id_cuenta='.$id_cuenta.' ORDER BY fecha_inicio DESC') as $a) {
			$a['tipificacion']=getTipificacion($a['id_tipificacion']);
			$data['cuentas'][$id_cuenta]['gestiones'][]=$a;
		}
		$BM->mark('gestiones');
		
		$BM->mark('tipificaciones');
		$data['cuentas'][$id_cuenta]['tipificaciones']=getTipificacionesByCampana($data['cuentas'][$id_cuenta]['campana']['id_campana']);
		$BM->mark('tipificaciones');
		
		// data no mapeada
		$BM->mark('data_no_mapeada');
		$data['cuentas'][$id_cuenta]['data_no_mapeada']['data']=array();
		$q0=$db->query('SELECT MAX(id_carga) FROM cargas.carga_no_mapeada WHERE id_cuenta='.$id_cuenta)->current();
		if(!is_null($q0['max'])) {

			$data['cuentas'][$id_cuenta]['data_no_mapeada']['carga']=getCarga($q0['max']);
			foreach($db->query('SELECT * FROM cargas.carga_no_mapeada WHERE id_cuenta='.$id_cuenta.' AND id_carga='.$q0['max'].' ORDER BY "order" ASC') as $c) {
				$data['cuentas'][$id_cuenta]['data_no_mapeada']['data'][$c['campo']]=$c['valor'];
			}

		}
		$BM->mark('data_no_mapeada');
		
		$BM->mark('telefonos');
		$data['cuentas'][$id_cuenta]['telefonos']['deudor']=$data['cuentas'][$id_cuenta]['deudor']['telefonos'];
		foreach($data['cuentas'][$id_cuenta]['telefonos']['deudor'] as &$t) {
			$t['persona'] = getPersona($t['id_persona'],false,false,false);
			unset($t);
		}
		$BM->mark('telefonos');
		
		$BM->mark('telefonos_relaciones');
		$data['cuentas'][$id_cuenta]['telefonos']['relaciones']=array();
		foreach($data['cuentas'][$id_cuenta]['deudor']['relaciones'] as $r) {
			if(empty($r['persona']['telefonos'])) continue;
			foreach($r['persona']['telefonos'] as $t) {
				$t['tipo_relacion']=$r['tipo_relacion'];
				$t['persona'] = getPersona($r['persona']['id_persona'],false,false,false);
				$data['cuentas'][$id_cuenta]['telefonos']['relaciones'][]=$t;
			}
		}
		$BM->mark('telefonos_relaciones');
		
		$BM->mark('otras_personas');
		$data['cuentas'][$id_cuenta]['telefonos']['otras_personas']=array();
		foreach($data['cuentas'][$id_cuenta]['otras_personas'] as $r) {
			if(empty($r['telefonos'])) continue;
			foreach($r['telefonos'] as $t) {
				$t['tipo_responsable']=$r['tipo_responsable'];
				$t['persona'] = getPersona($r['id_persona'],false,false,false);
				$data['cuentas'][$id_cuenta]['telefonos']['otras_personas'][]=$t;
			}
		}
		$BM->mark('otras_personas');
		

		// actualizamos data de telefonos
		$BM->mark('actualizacion_telefonos');
		foreach($data['cuentas'][$id_cuenta]['telefonos'] as &$t) {
			foreach($t as &$tt) {
				$tt['tiene_gestion']=false;
				$tt['tiene_promesa']=false;
				$tt['contacto_primera_persona']=false;
				$tt['contacto_tercera_persona']=false;
				$tt['mejor_gestion']=array();
				$query='SELECT
						G .tel_number,
						C .cuenta,
						P .primer_nombre,
						P .segundo_nombre,
						P .primer_apellido,
						P .segundo_apellido,
						G .fecha_inicio,
						T .id_tipificacion AS tipificacion_id,
						T .tag AS tipificacion_tag,
						T .descripcion AS tipificacion_descripcion
					FROM
						"gestiones"."gestion" G
					JOIN cuentas.cuenta C USING (id_cuenta)
					JOIN personas.persona P ON (C .id_deudor = P .id_persona)
					JOIN gestiones.tipificacion t USING (id_tipificacion)
					WHERE
					identificacion = \''.$data['cuentas'][$id_cuenta]['deudor']['identificacion'].'\'
					AND tel_number = \''.$tt['telefono'].'\'
					ORDER BY t.peso DESC';
				$q0=$db->query($query);
				if($q0->numRows()>0) {
					$g=$q0->current();
					$g['_tipificacion']=getTipificacion($g['tipificacion_id']);
					if($g['_tipificacion']['_metadata']['es_promesa']) $tt['tiene_promesa']=true;
					$tt['tiene_gestion']=true;
					$tt['mejor_gestion']=$g;
					foreach($q0 as $qq) {
						$BM->mark('getTipificacionActualizacionTelefono');
						$qq['t']=getTipificacion($qq['tipificacion_id']);
						$BM->mark('getTipificacionActualizacionTelefono');
						if($qq['t']['_metadata']['es_contacto_tercero'] && !$tt['contacto_primera_persona']) {
							$tt['contacto_tercera_persona']=true;
						}else if($qq['t']['_metadata']['es_contacto_primera_persona']) {
							$tt['contacto_tercera_persona']=false;
							$tt['contacto_primera_persona']=true;
						}
					}
				}
				unset($tt);
			}
			unset($t);
		}
		$BM->mark('actualizacion_telefonos');
	}
    
	//$data['tipificaciones']=
	return $data;
}

/**
* Aplica implode al arreglo $array utilizando el separador $separator
* solo con aquellos elementos no vacíos de $array
* 
* @param mixed $separator
* @param mixed $array
* @return string
*/

function implodeNotEmpty($separator,$array) {
	$aux=array();
	foreach($array as $a) {
		$a=trim($a);
		if($a!='') $aux[]=$a;
	}
	return implode($separator,$aux);
}

function get_cuenta_data($id_cuenta) {
	GLOBAL $smarty;
	$ret=getGestionPanelData($id_cuenta);
	$ret['cuenta']=$ret['cuentas'][$id_cuenta];
	unset($ret['cuentas']);
	$smarty->assign('only_content',true);
	$smarty->assign('with_cuenta',$ret['cuenta']);
	$ret['html']['carga_no_mapeada']=$smarty->fetch('main_gestionar/detalle_cuenta_box.tpl');
	$ret['html']['gestiones']=$smarty->fetch('main_gestionar/gestiones_box.tpl');
	$ret['html']['telefonos']=$smarty->fetch('main_gestionar/telefono_table.tpl');
	return $ret;
}

function get_query_fields($table,$table_alias='',$prefix='',$schema='',$return_imploded=false) {
    $db=DB::getInstance();
    if($schema=='') $schema='public';
    $query='SELECT * FROM information_schema.columns WHERE table_name=\''.$table.'\' AND table_schema=\''.$schema.'\'';
    $q0=$db->query($query);
    $cols=array();
    foreach($q0 as $qa0) {
        $aux='';
        if($table_alias!='') {
            $aux.=$table_alias.'.';
        }
        $aux.=$qa0['column_name'];
        if($prefix!='') $aux.=' AS '.$prefix.$qa0['column_name'];
        $cols[]=$aux;
    }
    if($return_imploded) return implode(',',$cols);
    return $cols;
}

function getCargaNoMapeada($id_cuenta) {
	$db=DB::getInstance();
	$ret=array();
	foreach($db->query('SELECT * FROM cargas.carga_no_mapeada WHERE id_cuenta=\''.$db->escape($id_cuenta).'\' ORDER BY id_carga DESC') as $nm) {
		$ret[$nm['id_carga']][$nm['campo']]=$nm['valor'];
	}
	return $ret;
}

function insertCargaData($id_carga,$filepath,$nombre_archivo,$tipo) {
		$db=DB::getInstance();
		$data=file_get_contents($filepath);
		if($data===false)
			throw new Exception('Error al leer "'.$file['filepath'].'"');
		$row=array(
			'id_carga'=>$id_carga,
			'nombre_archivo'=>'\''.$db->escape($nombre_archivo).'\'',
			'md5'=>'\''.md5($data).'\'',
			'raw_data'=>null,
			'original_size'=>strlen($data),
			'compressed_size'=>null,
			'md5_compressed'=>null,
			'tipo'=>'\''.$tipo.'\''
		);
		$data=gzcompress($data,9);
		$row['raw_data']='\''.$db->escape_bytea($data).'\'';
		$row['compressed_size']=strlen($data);
		$row['md5_compressed']='\''.md5($data).'\'';
		
		
		$query='INSERT INTO cargas.carga_data ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')';
		$db->query($query);	
}

function get_navigation($__file__,$include_last_node=false){
	$aux = explode('/',$__file__);
	$aux2 = array();
	foreach ($aux as $idx => $t){
		if (in_array($idx,array(0,1,2,3,4))) continue;
		if ($t === end($aux)) {
			if ($include_last_node)
				$aux2[]=substr($t,0,-4);
		}else{
			$aux2[]=$t;
		}
	}
	$nav = array();
	$r.='';
	foreach($aux2 as $m){
		$r.=$m.'/';
		$nav[]='<a href="?mod='.$r.'index">'.$m.'</a>';
	}
	return '/<a href="?">Inicio</a>/'.implode('/',$nav);
}


function multiexplode($delimiters,$string) {
	$ready = str_replace($delimiters, $delimiters[0], $string);
	$launch = explode($delimiters[0], $ready);
	return  $launch;
}


function crear_gestion_ivr_CNEL($identificacion,$gestion){
	$db=DB::getInstance();
	// get gestiones ivr
	$q = 'SELECT * FROM gestiones.gestion g
	JOIN cuentas.cuenta c USING(id_cuenta)
	JOIN personas.persona p ON(p.id_persona=c.id_deudor)
	where p.identificacion=\''.$identificacion.'\' AND g.id_tipificacion='.$gestion['id_tipificacion'].' ORDER BY fecha_inicio DESC';
	$q0 = $db->query($q);
	if ($db->numRows($q0)==0){
		// get telefono blanco de repositorio
		$db_repo = DB::getInstance('repo');
		$qr = 'SELECT numero_telefono FROM repository_personas.lista_blanca_telefonos WHERE pe_numero_identificacion=\''.$identificacion.'\' ORDER BY fecha_insercion DESC';
		$qr0 = $db_repo->query($qr);
		$tels = array();
		while ($qar0 = $db_repo->fetchOne($qr0)){
			if ($gestion['tel_number']!==$qar0['numero_telefono'])
				$tels[] = $qar0['numero_telefono'];
		}
		if (empty($tels)) return false;
	}else{
		while($qa0 = $db->fetchOne($q0)){
			if ($gestion['tel_number']!==$qa0['tel_number'])
				$tels[]=$qa0['tel_number'];
		}
		if (empty($tels)) return false;
	}
	$max = 1;
	foreach ($tels as $t){
		$_request['id_cuenta'] = $gestion['id_cuenta'];
		$_request['fecha_inicio'] = date('Y-m-d ').rand(10, 18).':'.rand(10,59).':00';
		$_request['user_name'] = $gestion['user_name'];
		// $_request['user_name'] = 'test_ivr';
		$_request['tel_number'] = $t;
		$_request['id_tipificacion'] = $gestion['id_tipificacion'];
		$_request['observacion'] = 'ENVIO IVR AUTOMATICO - '.$t;

		// INSERTAR EN GESTION
		$q = 'INSERT INTO gestiones.gestion('.implode(',',array_keys($_request)).')VALUES(\''.implode('\',\'',$_request).'\')';
		$db->query($q);
		$max++;
		if ($max>=2) break;
	}

	return true;
}


function getDataDragonByTelNumber($ip_dragon,$telefono){
	global $servidores_dragon;
	if (!in_array($ip_dragon,array_keys($servidores_dragon))) return array();
	if ($telefono=='') return array();
	$server = $servidores_dragon[$ip_dragon]['db'];
	DB::connect('pgsql',$server,'dragon');
	$db = DB::getInstance('dragon');
	$q = 'SELECT ct.*
	FROM dd_call_time ct
		JOIN dd_telephone t USING(tel_id)
	WHERE t.tel_number=\''.$telefono.'\'
	ORDER BY ct.cal_date DESC';
	$q0 = $db->query($q);
	if ($db->numRows($q0)==0) return array();
	return $db->fetchOne($q0);
}