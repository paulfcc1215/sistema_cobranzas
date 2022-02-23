<?php

	class InvestigacionTelefonica implements Reporte_Interface{
		public function getCamposRequeridos() {
			return array(
			);
		}
		
		public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {
		
		}

		public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
			$db=DB::getInstance();
			switch($_get['step']) {
				case '3':
					if(empty($_post['bases']))
						throw new Exception('Debe indicar al menos una base');
					$output=array(
						array('DD_RAW(TRUNCATE)(;)'),
						array(),
						array('CAMPAIGN_SUBJECT(TRUNCATE)')
					);
					$tel_cond=array();
					foreach($_post['bases'] as $b) {
						$aux=array();
						$b=explode('|',$b);
						foreach($b as &$bb){
							if($bb=='NULL') {
								$bb=' IS NULL';
							}else{
								$bb='=\''.$bb.'\'';
							}
							unset($bb);
						}
						$aux[]='id_carga'.$b[0];
						$aux[]='origen'.$b[1];
						$tel_cond[]=implode(' AND ',$aux);
					}

					$db->prepare('get_tel','SELECT * FROM medios_contacto.telefono WHERE id_persona=$1 AND ('.implode(' OR ',$tel_cond).')');
					$db->prepare('get_gestiones','SELECT * FROM gestiones.gestion g JOIN gestiones.tipificacion t USING (id_tipificacion) LEFT JOIN gestiones.tipificacion_metadata tm USING (id_tipificacion) WHERE g.id_cuenta=$1');
					$db->prepare('get_gestiones_on_tel','SELECT * FROM gestiones.gestion g JOIN gestiones.tipificacion t USING (id_tipificacion) JOIN gestiones.tipificacion_metadata tm USING (id_tipificacion) WHERE g.id_cuenta=$1 AND g.tel_number=$2');

					$query='SELECT * FROM cuentas.cuenta c JOIN personas.persona p ON (p.id_persona=c.id_deudor AND p.id_proceso=c.id_proceso) WHERE c.id_proceso='.$_post['id_proceso'].' -- AND c.id_cuenta=1595';
					$num_rec=0;
					$subj_type='CC-'.date('YmdHis').'-'.rand(10000,99999);
					$max_tels=0;
					foreach($db->query($query) as $cuenta) {
						$tg=$db->execute('get_gestiones',array($cuenta['id_cuenta']));
						$exclude=false;
						if($_post['opciones']['excluye_gestionadas']=='1' && $tg->numRows()>0) continue;
						if($_post['opciones']['excluye_promesas']=='1' || $_post['opciones']['excluye_negativas']=='1') {
							foreach($tg as $tgg) {
								if($_post['opciones']['excluye_promesas']=='1' && $tgg['es_promesa']=='1') {
									$exclude=true;
									break;
								}else if($_post['opciones']['excluye_negativas']=='1' && $tgg['es_negativa']=='1') {
									$exclude=true;
									break;
								}
							}
						}
						
						if($exclude) continue;
						//echo 'SELECT * FROM medios_contacto.telefono WHERE id_persona=$1 AND ('.implode(' OR ',$tel_cond).')';
						//die();
						$rec=array(
							'id_persona'=>$cuenta['id_deudor'],
							'id_cuenta'=>$cuenta['id_cuenta'],
							'identificacion'=>$cuenta['identificacion'],
							'nombre'=>Helpers::implodeNotEmpty(' ',array($cuenta['primer_nombre'],$cuenta['segundo_nombre'],$cuenta['primer_apellido'],$cuenta['segundo_apellido'])),
							'valor_actual'=>$cuenta['valor_actual'],
							'telefonos'=>array()
						);
						$tq=$db->execute('get_tel',array($cuenta['id_deudor']));
						foreach($tq as $t) {
							//print_arr($t);
							//die();
							$exclude=false;
							foreach($tg as $g) {
								if(ltrim($g['tel_number'],'0')==ltrim($t['telefono'],'0')) continue;
								if($_post['opciones']['excluye_fallecidos']=='1' && $g['es_fallecido']=='1') {
									$exclude=true;
									continue;
								}
								if($_post['opciones']['excluye_equivocados']=='1' && $g['es_equivocado']=='1') {
									$exclude=true;
									continue;
								}
							}
							if($exclude) continue;
							$rec['telefonos'][]=$t['telefono'];
						}
						
						if(empty($rec['telefonos'])) continue;
						$num_rec++;
						$this->append_dragon_record($output,$rec,$_post['send_name'],$_post['campaign_name'],$num_rec,$subj_type,$max_tels);
					}
					
					$output[1]=array(
						'RAW_FLAG',
						'RAW_SEND_NAME',
						'RAW_CAMPAIGN_NAME',
						'RAW_PRODUCT',
						'RAW_ORDER',
						'RAW_SUBJ_TYPE',
						'RAW_SUBI_ID',
						'RAW_NAME',
						'RAW_SURNAME',
						'RAW_STR_FIELD1',
						'RAW_STR_FIELD2',
						'RAW_STR_FIELD3',
					);
					$aux=array();
					for($i=1;$i<=$max_tels;$i++) {
						$output[1][]='RAW_TEL'.$i;
						$output[1][]='RAW_LOCT_ID'.$i;
						$output[1][]='RAW_CITY_CODE'.$i;
						$output[1][]='RAW_EXT_TEL'.$i;
						$output[1][]='RAW_TEL'.$i.'_STATE';
						
					}
					//print_arr($output);
					//die();
					for($j=3;$j<count($output);$j++) {
						while(count($output[1])<>count($output[$j])) {
							$output[$j][]='';
						}
					}
					
					foreach($output as &$o) {
						$o=implode(';',$o);
						unset($o);
					}

					$result['dragon.in']=implode("\r\n",$output);
					return 'file';
				
				break;
				case '2':
					$query='SELECT DISTINCT t.id_carga,t.origen,(SELECT descripcion FROM cargas.carga WHERE id_carga=t.id_carga) FROM medios_contacto.telefono t JOIN personas.persona p USING (id_persona) JOIN cuentas.cuenta c ON (c.id_deudor=p.id_persona) ORDER BY 1';
					foreach($db->query($query) as $p) {
						$base_origen[]=$p;
					}
					$_T['maintitle']='DRAGON - Generador Carga Dragon';
					
					$_T['maincontent'].='Seleccione las bases:
					<br>
					<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'3')).'">
					'.UI_Helper::array_to_hidden($_post,false).'
					<select name="bases[]" size="20" multiple="true" style="padding: 10px;">
					';
					foreach($base_origen as $bo) {
						foreach($bo as &$b) {
							if(is_null($b)) {
								$b='NULL';
							}
							unset($b);
						}
						$_T['maincontent'].='<option value="'.$bo['id_carga'].'|'.$bo['origen'].'">Id Carga: '.($bo['id_carga'].' | Origen: '.$bo['origen'].' | Descripción: '.$bo['descripcion']).'</option>';
					}
					$_T['maincontent'].='</select>
					<br>
					<table>
					<tr><td><input type="checkbox" name="opciones[excluye_promesas]" checked="1" value="1"></td><td>Excluir Promesas de Pago</td></tr>
					<tr><td><input type="checkbox" name="opciones[excluye_negativas]" checked="1" value="1"></td><td>Excluir Negativas de Pago</td></tr>
					<tr><td><input type="checkbox" name="opciones[excluye_equivocados]" checked="1" value="1"></td><td>Excluir Equivocados</td></tr>
					<tr><td><input type="checkbox" name="opciones[excluye_gestionadas]" checked="1" value="1"></td><td>Excluir con Gestion</td></tr>
					
					</table>
					<br>
					<button class="btn btn-primary">Generar</button>
					';
					return 'flow';
				break;
				
				default:
					// print_arr(_getTelefonosRepositorio('1723587778'));
					// die();
					$q='SELECT * FROM estructura.udn WHERE status=\'1\'';
					$udns=array();
					foreach($db->query($q) as $u) {
						$udns[$u['id_udn']] = strtoupper($u['udn']);
					}
					
					$_T['maintitle']='Generico - Investigación Telefonía';
					$_T['maincontent']='
					<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
						<div class="row">
							<div class="col-md-4">
								<label>Seleccione UDN:</label>
								<select onchange="udn_change($(this).val())" class="form-control">
									<option value="">Seleccione...</option>';
									foreach($udns as $id_u => $u) {
										$_T['maincontent'].='<option value="'.$id_u.'">'.$id_u.' - '.$u.'</option>';
									}
									$_T['maincontent'].='
								</select>
							</div>
							<div class="col-md-4">
								<label>Seleccione Campaña:</label>
								<select id="id_id_campana" onchange="campana_change($(this).val())" class="form-control" size="5">
									<option value="">Seleccione...</option>
								</select>
							</div>
							<div class="col-md-4">
								<label>Seleccione Proceso:</label>
								<select id="id_id_proceso" class="form-control" size="5">
									<option value="">Seleccione...</option>
								</select>
							</div>
						</div>
						<br><br>
						
						<br><br>
						<b>Indique Campaña Dragon:</b>
						<br>
						<input type="text" name="campaign_name">
						<br><br>
						<button class="btn btn-primary">Siguiente</button>
					</form>
					<script>
						function udn_change(me){
							$("#id_id_campana").empty();
							$("#id_id_campana").append("<option value=\"\">Seleccione...</option>");
							if (me=="") return false;
							$.ajax({
								method: "post",
								url:"user/Genericos/Reportes/transacciones_ajax.php?action=get_campanas",
								data: {
									"id_udn":me
								},
								success: function(result) {
									result = JSON.parse(result);
									var options = "";
									$.each(result.data,function(i,o){
										options+="<option value="+i+">"+i+" - "+o+"</option>";
									});
									$("#id_id_campana").append(options);
								}
							});
						}

						function campana_change(me){
							$("#id_id_proceso").empty();
							$("#id_id_proceso").append("<option value=\"\">Seleccione...</option>");
							if (me=="") return false;
							$.ajax({
								method: "post",
								url:"user/Genericos/Reportes/transacciones_ajax.php?action=get_procesos",
								data: {
									"id_campana":me
								},
								success: function(result) {
									result = JSON.parse(result);
									var options = "";
									$.each(result.data,function(i,o){
										options+="<option value="+i+">"+o+"</option>";
									});
									$("#id_id_proceso").append(options);
								}
							});
						}

					</script>
					';
					return 'flow';				
				break;
			}
		}
	}