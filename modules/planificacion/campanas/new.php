<?php
Auth::enforcePrivileges('AUTH_PLANIFICACION*');
$_AM['udns']=AutoModel::getInstance('estructura','udn',DB::getInstance());
$_AM['empresas']=AutoModel::getInstance('estructura','empresa',DB::getInstance());
$_AM['campanas']=AutoModel::getInstance('campanas','campana',DB::getInstance());
$_AM['metadata_usable']=AutoModel::getInstance('metadata','metadata_usable',DB::getInstance());
$_AM['metadata']=AutoModel::getInstance('metadata','metadata',DB::getInstance());
$_AM['cat_tipificaciones']=AutoModel::getInstance('gestiones','catalogo_tipificacion',DB::getInstance());

switch($_GET['step']) {
    case 'ajax':
        $ret=array(
            'success'=>true,
            'data'=>array()
        );
        try {
            switch($_GET['a']) {
				case 'getUDNs':
					$data=$_AM['udns']->getByAndCond(array(
						'id_empresa'=>$_POST['id_empresa']
					));
					
					foreach($data as $d) {
						$ret['data'][]=$d->toArray();
					}
				break;
				
                case 'getInstrumentoClasses':
                    if(!preg_match('#^[a-zA-Z0-9_]+$#',$_POST['path'])) throw new Exception('Ruta inválida');
                    $dhdl=opendir(_BASE_INSTRUMENTOS_PATH.'/'.$_POST['path']);
                    while($ptr=readdir($dhdl)) {
                        if($ptr=='.' || $ptr=='..') continue;
                        if(preg_match('#^(Instrumento_.*?)\.class\.php$#',$ptr,$matches)) {
                            $ret['data'][]=$matches[1];
                        }
                    }
                break;
                
            }
        }catch(Exception $e) {
            $ret=array(
                'success'=>false,
                'data'=>$e->getMessage
            );
        }
        echo json_encode($ret);
        die();
    break;
    
	case '3':
		try {
			$empresa=$_AM['empresas']->getById($_POST['empresa']);
			if(!$empresa) throw new Exception('Empresa inválida');
			if(!Auth::hasEmpresa($empresa->id_empresa)) throw new Exception('No tiene privilegios para esta empresa');
			if(!is_dir(_BASE_USER_PATH.'/'.$_POST['carpeta_control'])) throw new Exception('Carpeta controladora invalida');
			$udn=$_AM['udns']->getByAndCond(array(
				'id_empresa'=>$empresa->id_empresa,
				'id_udn'=>$_POST['id_udn']
			));
			if(!$udn)
				throw new Exception('UDN inválida');
			$_POST['nombre_campana']=trim($_POST['nombre_campana']);
			if($_POST['nombre_campana']=='')
				throw new Exception('El nombre de campaña no puede estar vacío');

			$db->startTransaction();
			$campana=$_AM['campanas']->insert(array(
				'id_udn'=>$_POST['id_udn'],
				'campana'=>$_POST['nombre_campana'],
				'carpeta_control'=>$_POST['carpeta_control'],
				'id_catalogo_tipificaciones'=>$_POST['id_cat_tipificacion']
			));
			metadata_save($_POST['configuraciones'],'campana',$campana->id_campana);
			$db->commit();
			
			
			$_T['maintitle']='Planificación de Operación - campanas - Nueva Campaña';
			$_T['maincontent']='<h2 style="color: green;">Se ha creado la campaña satisfactoriamente</h2>
			<a href="?mod=planificacion/campanas/index">Regresar</a>
			';
			

        }catch(Exception $e) {
            $db->rollback();
            $error=$e->getMessage();
            goto lbl_2;
        }	
	break;
	
	case '2':
		lbl_2:
		try {
			$empresa=$_AM['empresas']->getById($_POST['empresa']);
			if(!$empresa) throw new Exception('Empresa inválida');
			if(!Auth::hasEmpresa($empresa->id_empresa)) throw new Exception('No tiene privilegios para esta empresa');
			
			$udn=$_AM['udns']->getByAndCond(array(
				'id_empresa'=>$empresa->id_empresa,
				'id_udn'=>$_POST['id_udn']
			));
			if(!$udn)
				throw new Exception('UDN inválida');
			$_POST['nombre_campana']=trim($_POST['nombre_campana']);
			if($_POST['nombre_campana']=='')
				throw new Exception('El nombre de campaña no puede estar vacío');
			
			$aux=db_get_sps(DB::getInstance());
			$expected_prefix='sp_'.preg_replace('#[^a-z]#','',strtolower($empresa->nombre)).'_'.preg_replace('#[^a-z]#','',strtolower($udn[0]->udn)).'_';
			foreach($aux['cargas'] as $a) {
				//if($a['args']!='' || $a['ret_type']!='boolean') continue;
				if(preg_match('#^'.$expected_prefix.'#',$a['name'])) $sps[]=$a;
			}
			foreach($sps as $sp) {
				$sps_options[]='<option value="'.$sp['name'].'">'.$sp['name'].'</option>';
			}
			
			$_T['css'].='
			.tbl {
				border: solid 1px #ccc;
				border-collapse: collapse;
			}
			.tbl td,.tbl th{
				padding: 10px;
				border: solid 1px #ccc;
			}
			
			
			';
			$_T['maintitle']='Planificación de Operación - Campanas - Nueva Campaña';
			$_T['top_jscript'].='
			var sps='.json_encode($sps).';
			
			function updateDesc(sp) {
				$("#sp_desc").html("");
				for(var i in sps) {
					var ptr=sps[i];
					console.log(sp);
					if(ptr.name==sp) {
						$("#sp_desc").html(ptr.comment);
						break;
					}
				}
			}
			
			function add() {
				var subida=$("#id_nombre_subida");
				var sp=$("#id_sp");
				var tbl=$("#sp_tbl");
				var html=new Array();
				
				if(subida.val().trim()=="") {
					alert("Debe ingresar un nombre para la subida");
					return;
				}
				if(sp.val().trim()=="") {
					alert("Debe seleccionar un SP");
					return;
				}
				html.push("<tr>");
				html.push("<td>"+subida.val()+"</td>");
				html.push("<td>"+sp.val()+"</td>");
				html.push("<td>");
				html.push("<button class=\'btn btn-danger\' type=\'button\' onclick=\'del(this)\'>Eliminar</button>");
				html.push("<input type=\'hidden\' name=\'subidas[]\' value=\'"+btoa(sp.val()+";"+subida.val())+"\'");
				html.push("</td>");
				html.push("</tr>");
				tbl.append(html.join(""));
				
			}
			
			function del(td) {
				$(td).parent().parent().remove();
				
			}
			';
			if($error!='') {
				$_T['maincontent'].='<div style="color: maroon; font-weight: bold;">'.$error.'</div>';
			}
			$_T['maincontent'].='<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'3')).'" id="theForm">';
			foreach($_POST as $k=>$v) {
				if($k=='subidas') continue;
				$_T['maincontent'].='<input type="hidden" name="'.$k.'" value="'.$v.'">';
			}
			$_T['maincontent'].='
			<table border="1">
			<tr><th style="padding: 10px;">Empresa: </th><td style="padding: 10px;">'.$empresa->nombre.'</td></tr>
			<tr><th style="padding: 10px;">UDN: </th><td style="padding: 10px;">'.$udn[0]->udn.'</td></tr>
			<tr><th style="padding: 10px;">Nombre Campaña: </th><td style="padding: 10px;">'.$_POST['nombre_campana'].'</td></tr>
			</table>
			
			<br>
			
			<b>Por favor indique los Stored Procedures a utilizar para los procesos de subida</b><br><br>
			
			<table class="tbl" width="90%">
			<tr style="background-color: #f9f9f9;"><th>Nombre Subida</th><th>Stored Procedure</th><th>&nbsp;</th></tr>
			<tr>
			<td><input type="text" id="id_nombre_subida" placeholder="Indique nombre de subida..." style="width: 80%;"></td>
			<td>
				<select id="id_sp" onchange="updateDesc(this.value)"><option value="">Seleccione...</option>'.implode('',$sps_options).'</select>
			</td>
			<td>
			<button class="btn btn-success" type="button" onclick="add()">Agregar</button>
			</td>
			</tr>
			</table>
			
			<b>Descripción del Stored Procedure</b>:
			<br>
			<span id="sp_desc" style="margin-left: 20px;"></span>
			
			<hr>
			<button class="btn btn-primary">Guardar</button>
			
			<table class="table table-striped" style="margin-top: 20px;" id="sp_tbl">
			<tr><th>Nombre Subida</th><th>Stored Procedure</th><th>&nbsp;</th></tr>
			';
			if(!empty($_POST['subidas'])) {
				foreach($_POST['subidas'] as $s) {
					$ss=base64_decode($s);
					$ss=explode(';',$ss);
					$_T['maincontent'].='<tr>';
					$_T['maincontent'].='<td>'.$ss[1].'</td>';
					$_T['maincontent'].='<td>'.$ss[0].'</td>';
					$_T['maincontent'].='<td>';
					$_T['maincontent'].='<button class="btn btn-danger" type="button" onclick="del(this)">Eliminar</button>';
					$_T['maincontent'].='<input type="hidden" name="subidas[]" value="'.$s.'">';
					$_T['maincontent'].='</td>';
					$_T['maincontent'].='</tr>';

				}
			}
			$_T['maincontent'].='
			</table>
			
			
			';
			

        }catch(Exception $e) {
            $error=$e->getMessage();
            goto lbl_default;
        }	
	break;
    
    default:
        lbl_default:
		$empresas_options=array(
			'<option value="">Seleccione...</option>'
		);
		foreach(Auth::getEmpresas() as $e) {
			$empresas_options[]='<option value="'.$e['id_empresa'].'">'.$e['id_empresa'].' - '.$e['nombre_empresa'].'</option>';
		}
		
		
        $_T['maintitle']='Planificación de Operación - campanas - Nueva Campaña';
        if($error!='') {
            $_T['maincontent'].='<span style="color: maroon; font-weight: bold;">'.$error.'</span>';
        }
        $_T['top_jscript']='
			function updateUDNS(id_empresa) {
				$("#id_udn").html("<option value=\'\'>Seleccione...</option>");
				$.ajax({
					"url":"?'.Helpers::arr_to_url($_GET,array(),array('step'=>'ajax','a'=>'getUDNs')).'",
					"method":"POST",
					"data":{
						"id_empresa":id_empresa
					},
					"success":function(d) {
						try {
							d=$.parseJSON(d);
                            if(!d) throw "Error en la respuesta del servidor";
                            if(!d.success) throw d.data;
                            d=d.data;
                            var options=new Array();
                            for(var i in d) {
                                options.push("<option value=\'"+d[i].id_udn+"\'>"+d[i].id_udn+" - "+d[i].udn+"</option>");
                            }
                            $("#id_udn").html(options.join(""));
                            if(cb != null) {
                                console.log("Calling callback");
                                cb(path);
                            }


						}catch(err) {
						}
					}
				});
			}
			
        ';
        $_T['maincontent'].='
        <form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step'=>'3')).'" id="theForm">
        <input type="hidden" name="configuraciones" id="hidden_target">
        <div class="form-group">
            <label>Empresa</label>
            <select name="empresa" class="form-control" onchange="updateUDNS(this.value)">
            '.implode("\r\n",$empresas_options).'
            </select>
            
        </div>
        
		<div class="form-group">
            <label>Nombre de la UDN</label>
            <select class="form-control" name="id_udn" id="id_udn">
            <option value="">Seleccione...</option>
            </select>
        </div>
        <div class="form-group">
            <label>Catálogo Tipificaciones</label>
            <select class="form-control" name="id_cat_tipificacion" id="id_cat_tipificacion">
			<option value="">Seleccione...</option>
		';
		foreach($_AM['cat_tipificaciones']->getAll() as $cat) {
			$_T['maincontent'].='<option value="'.$cat->id_cat_tipificacion.'">'.$cat->id_cat_tipificacion.' - '.$cat->nombre_catalogo.'</option>';
		}
		$_T['maincontent'].='
			</select>
        </div>
        <div class="form-group">
            <label>Nombre de la Campaña</label>
            <input type="text" class="form-control" name="nombre_campana" placeholder="Nombre de la campaña" value="'.$_POST['nombre_campana'].'">
        </div>

        <div class="form-group">
            <label>Carpeta Controladores</label>
            <select class="form-control" name="carpeta_control">
			<option value="">Seleccione...</option>
		';
		foreach(getCarpetasControles() as $c) {
			$_T['maincontent'].='<option value="'.$c.'">'.$c.'</option>';
		}
		$_T['maincontent'].='
			</select>
        </div>
		
        <div class="form-group">
            <label>Metadata</label>
        ';
        
        $select=new UIComponents_ConfigSelector();
        $select->source_data=metadata_load_usable('campana');
        $select->form_id='theForm';
        $select->hidden_target='hidden_target';
        $select->value=$_POST['configuraciones'];
        $_T['maincontent'].=$select->draw();
        $_T['maincontent'].='
        </div>
  
  <button type="submit" class="btn btn-primary">Siguiente</button>
  <button class="btn btn-danger" type="button" onclick="window.location=\'?mod=planificacion/campanas/index\'">Cancelar</button> 
        </form>
        <script>
        ';
        if($_POST['instrumento_class']!='') {
            $_T['maincontent'].='
            $(document).ready(function() {
                updateInstrumentoClass($("[name=\'instrumento_path\']").val(),function() {
                    $("[name=\'instrumento_class\']").find("option").each(function(k,o) {
                        if(o.value=="'.$_POST['instrumento_class'].'") {
                            o.selected="1";
                            return false;
                        }
                    });
                });
            });
            ';
        }
        $_T['maincontent'].='
        </script>
        ';
    break;
}