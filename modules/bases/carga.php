<?php
//$odbc=new ODBC('Gecko','sgecoaccess','Sg2019');
//var_dump($odbc);

$_AM['udns']=AutoModel::getInstance('estructura','udn',DB::getInstance());
$_AM['campanas']=AutoModel::getInstance('campanas','campana',DB::getInstance());
$_AM['proceso']=AutoModel::getInstance('campanas','proceso',DB::getInstance());
$_AM['empresa']=AutoModel::getInstance('estructura','empresa',DB::getInstance());
$_AM['metadata_usable']=AutoModel::getInstance('metadata','metadata_usable',DB::getInstance());
$_AM['carga']=AutoModel::getInstance('cargas','carga',DB::getInstance());
$_AM['cuenta']=AutoModel::getInstance('cuentas','cuenta',DB::getInstance());

$_T['maintitle']='Cargas de trabajo';
$BM=new Benchmark();
function compara_persona($persona_a,$persona_b) {
	$cambios_en_persona=array();
	foreach(array(
		'primer_nombre','segundo_nombre','primer_apellido','segundo_apellido',
	) as $f) {
		if($persona_a[$f]!=$persona_b[$f]) {
			$cambios_en_persona[$f]=array(
				$persona_a[$f],
				$persona_b[$f]
			);
		}
	}
	return $cambios_en_persona;
}

function get_cuentas_en_db_no_en_archivo($en_archivo,$id_proceso) {
	GLOBAL $db;
	$temp=tempnam(_TMP_UPLOAD_FOLDER,'uploads');
	$tname=basename($temp);
	file_put_contents($temp,implode("\r\n",$en_archivo));
	chmod($temp,0666);
	$db->query('CREATE TEMPORARY TABLE "'.$tname.'_en_archivo" (numero_cuenta TEXT)');
	$db->query('COPY "'.$tname.'_en_archivo" FROM \''.$temp.'\'');
	unlink($temp);
	$db->query('CREATE INDEX idx_'.uniqid().' ON "'.$tname.'_en_archivo" USING BTREE(numero_cuenta)');
	

	
	$db->query('
	CREATE TEMPORARY TABLE "'.$tname.'_en_db_no_en_archivo" AS
	SELECT
	cuenta::text AS numero_cuenta,
	valor_original::float AS valor_original,
	valor_actual::float AS valor_actual,
	(
		SELECT CONCAT(tipo_identificacion,\' - \',identificacion) FROM personas.persona p WHERE p.id_persona=(
			SELECT id_persona FROM cuentas.cuenta_responsable WHERE tipo_responsable=\'DEUDOR\' AND id_cuenta=c.id_cuenta
		)
	) AS deudor_db
	FROM
	cuentas.cuenta c JOIN cargas.carga u ON (c.id_carga=u.id_carga)
	WHERE
	u.id_proceso='.$id_proceso.'
	AND NOT EXISTS (
		SELECT numero_cuenta FROM "'.$tname.'_en_archivo" WHERE "'.$tname.'_en_archivo".numero_cuenta=c.cuenta
	)
	');
	$ret=array();
	foreach($db->query('SELECT * FROM "'.$tname.'_en_db_no_en_archivo"') as $r) {
		$ret[]=$r;
	}
	return $ret;
}

switch($_GET['step']) {
    case 'ajax':
        $ret=array(
            'success'=>true,
            'data'=>null
        );
        try {
            switch($_GET['a']) {
                case 'getCampanas':
                    if(!preg_match('#^\d+$#',$_POST['id_udn'])) throw new Exception('Id UDN Inválida');
                    $campanas=$_AM['campanas']->getByAndCond(array('id_udn'=>$_POST['id_udn']));
                    $ret['data']=array();
                    foreach($campanas as $c) {
                        $ret['data'][]=array(
                            'id_campana'=>$c->id_campana,
                            'campana'=>$c->campana
                        );
                    }
                break;
                case 'getProcesos':
                    if(!preg_match('#^\d+$#',$_POST['id_camp'])) throw new Exception('Id Campaña Inválida');
					$ret['data']=getProcesosByCampId($_POST['id_camp'],false);
                break;
                
                
                case 'getCargasHandler':
					$ret['data']=getCargasHandler($_POST['id_camp']);
					foreach($ret['data'] as &$d) {
						$d['fname']=encrypt($d['fname']);
						unset($d);
					}
                break;

				case 'getCargasModeloArchivo':
					$tipo_carga=decrypt($_POST['tipo_carga']);
					require $tipo_carga;
					$base_name=str_replace('.class.php','',basename($tipo_carga));
					$carga=new $base_name();
					$modelos=$carga->getArchivoModelo();
					if(is_null($modelos)) {
						$ret['data']=array();
					}else{
						$ret['data']=array();
						foreach($modelos as $k=>$v) {
							$ret['data'][]=$k;
						}
					}
					
				break;				
            }
        }catch(Exception $e) {
            $ret['success']=false;
            $ret['data']=$e->getMessage();
            echo json_encode($ret);
			die();
        }
        echo json_encode($ret);
        die();
    break;
	
	case 'download_details':
		$uid=decrypt($_GET['uid']);
		$dhdl=opendir(_TMP_UPLOAD_FOLDER);
		if(!$dhdl) die('Error al abrir tmp folder');
		while($ptr=readdir($dhdl)) {
			if($ptr=='.' || $ptr=='..') continue;
			$aux=explode('_',$ptr);
			if($aux[0]==$uid) $list[]=$ptr;
		}
		$tempfile=tempnam('/tmp','zip');
		$zip=new ZipArchive();
		$zip->open($tempfile,ZipArchive::CREATE);
		foreach($list as $l) {
			$aux=explode('_',$l);
			unset($aux[0]);
			
			$zip->addFile(_TMP_UPLOAD_FOLDER.'/'.$l,implode('_',$aux));
		}
		$zip->close();
		header('Content-Disposition: Attachment; filename="detalles.zip"');
		header('Content-Type: application/octect-stream');
		header('Content-Length: '.strlen(file_get_contents($tempfile)));
		
		
		readfile($tempfile);
		die();
	break;
	
	case 'downloadModel';
		$tipo_carga=decrypt($_GET['c']);
		require $tipo_carga;
		$base_name=str_replace('.class.php','',basename($tipo_carga));
		$carga=new $base_name();
		$modelos=$carga->getArchivoModelo(true);
		header('Content-Disposition: Attachment; filename="'.$_GET['m'].'"');
		echo ($modelos[$_GET['m']]);
		die();
	break;
	
	case '2':
		if(false && $_GET['token']!=$SM->carga_process['expected_token'])
			throw new Exception('Token invalido');
		$start_timer=microtime(true);
		$_T['css']='
		.resumen_tbl th {
			background-color: #ccc;
			text-align: right;
			padding: 5px;
		}
		.resumen_tbl td {
			padding: 5px;
			border:solid 1px #ccc;
		}
		
		.resume {
			font-size: 16px;
			margin-left: 18px;
		}
		';
		try {
			if($_GET['step2']!='1' && $_GET['step2']!='') {
				foreach($SM->carga_process as $k=>$v) {
					$_POST[$k]=$v;
				}
			}
			if(!preg_match('#^\d+$#',$_POST['id_udn'])) throw new Exception('La UDN indicada es inválida');
			if(!preg_match('#^\d+$#',$_POST['id_campana'])) throw new Exception('La Campana indicada es inválida');
			if(!preg_match('#^\d+$#',$_POST['id_proceso'])) throw new Exception('El Proceso indicado es inválido');
			$carga_handler=decrypt($_POST['carga_handler']);
			if($carga_handler===false) throw new Exception('Carga handler invalido');

			$campana=$_AM['campanas']->getByAndCond(array('id_campana'=>$_POST['id_campana']));
			$campana=$campana[0];
			$udn=$_AM['udns']->getByAndCond(array('id_udn'=>$campana->id_udn));
			$udn=$udn[0];
			
			if(!Auth::hasEmpresa($udn->id_empresa))
				throw new Exception('No tiene permiso para subir datos en esta empresa');
			
			$proceso=$_AM['proceso']->getByAndCond(array('id_proceso'=>$_POST['id_proceso']));
			$proceso=$proceso[0];
			
			if($_GET['step2']=='1' || $_GET['step2']=='') {
				$SM->carga_process=$_POST;
			}
			
			$aux=$SM->carga_process;
			$aux['expected_token']=uniqid();
			$SM->carga_process=$aux;
			
			$empresa=$_AM['empresa']->getById($udn->id_empresa);
			
			require $carga_handler;
			$class=str_replace('.class.php','',basename($carga_handler));
			
			$clazz=new $class();
			$data=array(
				'_T'=>&$_T,
				'id_udn'=>$_POST['id_udn'],
				'id_campana'=>$_POST['id_campana'],
				'id_proceso'=>$_POST['id_proceso'],
				'udn'=>$udn,
				'campana'=>$campana,
				'proceso'=>$proceso,
			);
			$ret=$clazz->execute(($_GET['step2']==''?'1':$_GET['step2']),$data);
			if (!is_null($ret)) {
				$implements=class_implements($ret);
				if (in_array('CargaModelo_Uploadable_Interface',$implements)) {
					if (!in_array('Iterator',$implements))
						throw new Exception('El handler de subidas debe devolver una clase que implemente "CargaModelo_Uploadable_Interface" y "Iterator" (No está implementando Iterator)');
					foreach($ret->getFiles() as $f) {
						if (!is_readable($f['filepath']))
							throw new Exception('El archivo "'.$f['filepath'].'" no se puede leer');
					}
					
					if ($_GET['__upload']!='1') {
						require dirname(__FILE__).'/analyze_phase.php';
					}else{
						require dirname(__FILE__).'/upload_phase.php';
					}
				}elseif(in_array('CargaModelo_Gestiones_Interface',$implements)) {
					require dirname(__FILE__).'/gestiones_upload_phase.php';
				}elseif(in_array('CargaModelo_Actualizaciones_Interface',$implements)) {
					require dirname(__FILE__).'/actualizaciones_upload_phase.php';
				}
			}
			
			
		}catch(Exception $e) {
			$_T['maincontent']='<h2 style="color: maroon; font-weight: bold;">ERROR<br>'.$e->getMessage().'</h2>';
		}
		
		
	break;
	


    
    default:
        lbl_default:
		$SM->carga_process=array(
			'expected_token'=>uniqid()
		);
        $_T['maincontent'].='
        <script>
        function updateGUICampanas() {
            var ctl_udn=$("#id_udn");
            var ctl_camp=$("#id_campana");
            
            ctl_camp.html("<option value=\'\'>Seleccione...</option>");
            
            if(ctl_udn.val()=="") return;
            
            $.ajax({
                "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getCampanas')).'",
                "method":"POST",
                "data": { 
                    "id_udn":ctl_udn.val()
                },
                "success":function(d) {
                    try {
                        d=$.parseJSON(d);
                        if(!d.success) throw d.data;
                        var html=new Array("<option value=\'\'>Seleccione...</option>");
                        for(var i in d.data) {
                            var ptr=d.data[i];
                            html.push("<option value=\'"+ptr.id_campana+"\'>"+ptr.id_campana+" - "+ptr.campana+"</option>");
                        }
                        ctl_camp.html(html.join(""));
                    }catch(err) {
                        alert(err);
                    }
                }
            });
        }
        
        function updateGUIprocesos() {
            var ctl_udn=$("#id_udn");
            var ctl_camp=$("#id_campana");
            var ctl_tipocarga=$("#id_tipocarga");
            var ctl_proceso=$("#id_proceso");
            
            ctl_tipocarga.html("<option value=\'\'>Seleccione...</option>");
            ctl_proceso.html("<option value=\'\'>Seleccione...</option>");
            
            if(ctl_udn.val()=="") return;
            if(ctl_camp.val()=="") return;            
            $.ajax({
                "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getProcesos')).'",
                "method":"POST",
                "data": { 
                    "id_udn":ctl_udn.val(),
                    "id_camp":ctl_camp.val()
                },
                "success":function(d) {
                    try {
						try {
							dd=$.parseJSON(d);
						}catch(err){
							alert("JSON Invalido. "+d); 
						}
						d=dd;
                        if(!d.success) throw d.data;
                        var html=new Array("<option value=\'\'>Seleccione...</option>");
                        for(var i in d.data) {
                            var ptr=d.data[i];
                            html.push("<option value=\'"+ptr.id_proceso+"\'>"+ptr.id_proceso+" - "+ptr.descripcion+"</option>");
                        }
                        ctl_proceso.html(html.join(""));
						updateGUIcargas();
                    }catch(err) {
                        alert(err);
                    }
                }
            });
        }

		function updateGUIGetArchivoModelo() {
            var ctl_udn=$("#id_udn");
            var ctl_camp=$("#id_campana");
            var ctl_tipocarga=$("#id_tipocarga");
			if(ctl_tipocarga.val()=="") return;
			var html_modelos=$("#html_modelos");
			html_modelos.html("");
			$.ajax({
                "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getCargasModeloArchivo')).'",
                "method":"POST",
                "data": { 
                    "id_udn":ctl_udn.val(),
                    "id_camp":ctl_camp.val(),
					"tipo_carga":ctl_tipocarga.val()
                },
                "success":function(d) {
                    try {
                        try{
							dd=$.parseJSON(d);
						}catch(err){
							alert("JSON Invalido. "+d);
						}
						d=dd;
                        if(!d.success) throw d.data;
                        if(d.data.length==0) {
							html_modelos.html("(Cargador no tiene hay archivos modelos)");
						}else{
							var html=new Array();
							for(var i in d.data) {
								var ptr=d.data[i];
								html.push("<a href=\'?'.Helpers::arr_to_url($_GET,array(),array('step'=>'downloadModel')).'&c="+ctl_tipocarga.val()+"&m="+d.data[i]+"\'>"+d.data[i]+"</a>");
							}
							html_modelos.html("Archivos Modelo:<br><div style=\'margin-left: 20px;\'>"+html.join(" | ")+"</div>");
						}
                    }catch(err) {
                        alert(err);
                    }
                }
			});
		}
		
        function updateGUIcargas() {
            var ctl_udn=$("#id_udn");
            var ctl_camp=$("#id_campana");
            var ctl_tipocarga=$("#id_tipocarga");
            
            ctl_tipocarga.html("<option value=\'\'>Seleccione...</option>");
            
            if(ctl_udn.val()=="") return;
            if(ctl_camp.val()=="") return;            
            $.ajax({
                "url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getCargasHandler')).'",
                "method":"POST",
                "data": { 
                    "id_udn":ctl_udn.val(),
                    "id_camp":ctl_camp.val()
                },
                "success":function(d) {
                    try {
                        try{
							dd=$.parseJSON(d);
						}catch(err){
							alert("JSON Invalido. "+d);
						}
						d=dd;
                        if(!d.success) throw d.data;
                        var html=new Array("<option value=\'\'>Seleccione...</option>");
                        for(var i in d.data) {
                            var ptr=d.data[i];
                            html.push("<option value=\'"+ptr.fname+"\'>"+ptr.tipo+"</option>");
                        }
                        ctl_tipocarga.html(html.join(""));
                    }catch(err) {
                        alert(err);
                    }
                }
            });
        }
            
        </script>
        
        <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'2','token'=>$SM->carga_process['expected_token'])).'" id="theForm" enctype="multipart/form-data">
		<input type="hidden" name="metadata" id="config_hidden_target">
        <b>Seleccione UDN:</b>
        <select class="form-control" id="id_udn" name="id_udn" onchange="updateGUICampanas()">
        <option value="">Seleccione...</option>
        ';
		
        $aux=array();
        foreach(
            $db->query('SELECT u.id_udn,u.udn,e.nombre AS nombre_empresa,id_empresa FROM estructura.udn u JOIN estructura.empresa e USING (id_empresa) ORDER BY e.nombre ASC,u.udn ASC') as $udn
        ) {
			if(!Auth::hasEmpresa($udn['id_empresa'])) continue;
            $aux[$udn['nombre_empresa']][]=$udn;
        }
        foreach($aux as $k=>$v) {
            $_T['maincontent'].='<optgroup label="'.$k.'">'; 
            foreach($v as $vv) {
                $_T['maincontent'].='<option value="'.$vv['id_udn'].'">'.$vv['id_udn'].' - '.$vv['udn'].'</option>'; 
            }
            $_T['maincontent'].='</optgroup>'; 
        }
		
        
        $_T['maincontent'].='
        </select>
        
        <br>
        <b>Seleccione Campana:</b>
        <select class="form-control" id="id_campana" name="id_campana" onchange="updateGUIprocesos()">
        <option value="">Seleccione...</option>
        </select>
        <br>
        <b>Seleccione Proceso:</b>
        <select class="form-control" id="id_proceso" name="id_proceso">
        <option value="">Seleccione...</option>
        </select>
        <br>
        <b>Seleccione Tipo Carga:</b>
        <select class="form-control" id="id_tipocarga" name="carga_handler" onchange="updateGUIGetArchivoModelo()">
        <option value="">Seleccione...</option>
        </select>
		<div id="html_modelos"></div>

        <br>
        <b>Descripción para la Carga:</b>
        <input type="text" name="descripcion_carga" class="form-control" id="id_descripcion_carga" name="descripcion_carga">

    	<br>
		<b>Metadata:</b>
		';
		
		$select=new UIComponents_ConfigSelector();
        $aux=array();
        foreach($_AM['metadata_usable']->getByAndCond(array('aplicable_a'=>'subida')) as $r) {
            $aux[]=$r->toArray();
        }
        $select->source_data=$aux;
        $select->form_id='theForm';
        $select->hidden_target='config_hidden_target';
        $select->value=$_POST['metadata'];
        
        $_T['maincontent'].=$select->draw();
		
		$_T['maincontent'].='
        <button class="btn btn-primary">Siguiente</button>
        </form>
        ';
        
        
    break;
}