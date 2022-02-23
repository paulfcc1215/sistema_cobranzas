<?php
class InDragon implements Reporte_Interface {
    public function getCamposRequeridos() {
		return array(
		);
	}
	
	public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {
	
	}
	
	public function genDragonTelData($tel,$i,$dragon) {
		switch($dragon){
			case '10.0.210.14':
				switch(substr(ltrim($tel,0),0,1)) {
					case '2':
						$loct_id='1';
						$city_id='512';
						$tel=substr($tel,2);
					break;
					case '4':
						$loct_id='1';
						$city_id='400';
						$tel=substr($tel,2);
					break;
					case '9':
						$loct_id='3';
						$city_id='998';
					break;
					default:
						$loct_id='3';
						$city_id='998';
					break;
				}
				$ret=array(
					'RAW_TEL'.$i=>$tel,
					'RAW_LOCT_ID'.$i=>$loct_id,
					'RAW_CITY_CODE'.$i=>$city_id,
					'RAW_EXT_TEL'.$i=>'',
					'RAW_TEL'.$i.'_STATE'=>'ACTIVE',
				);
			break;
			
			default:
				$ret=array(
					'RAW_TEL'.$i=>$tel,
					'RAW_LOCT_ID'.$i=>'3',
					'RAW_CITY_CODE'.$i=>'100',
					'RAW_EXT_TEL'.$i=>'',
					'RAW_TEL'.$i.'_STATE'=>'ACTIVE',
				);
			
			break;
		}
		return $ret;
	}
	
	public function append_dragon_record(&$output,$rec,$send_name,$campaign_name,$num_rec,$subj_type,&$max_tels) {
		$drec=array(
			'RAW_FLAG'=>'0',
			'RAW_SEND_NAME'=>$send_name,
			'RAW_CAMPAIGN_NAME'=>$campaign_name,
			'RAW_PRODUCT'=>$num_rec,
			'RAW_ORDER'=>$num_rec,
			'RAW_SUBJ_TYPE'=>$subj_type,
			'RAW_SUBI_ID'=>$rec['identificacion'],
			'RAW_NAME'=>substr(preg_replace('#[^A-Za-z 0-9\.]#','',$rec['nombre']),0,100),
			'RAW_SURNAME'=>'',
			'RAW_STR_FIELD1'=>$rec['id_cuenta'],
			'RAW_STR_FIELD2'=>substr(preg_replace('#[^A-Za-z 0-9\.]#','',$rec['nombre']),0,100),
			'RAW_STR_FIELD3'=>$rec['valor_actual'],
		);
		if($max_tels<count($rec['telefonos'])) {
			$max_tels=count($rec['telefonos']);
		}
		$i=0;
		foreach($rec['telefonos'] as $t) {
			$i++;
			foreach($this->genDragonTelData($t,$i,'10.0.210.14') as $k=>$v) {
				$drec[$k]=$v;
			}
		}
		$output[]=$drec;
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
				$q='SELECT
					'.get_query_fields('udn','udn','u_','estructura',true).',
					'.get_query_fields('campana','c','c_','campanas',true).',
					'.get_query_fields('proceso','p','p_','campanas',true).'
				FROM estructura.udn udn JOIN campanas.campana c USING (id_udn) JOIN campanas.proceso p USING (id_campana)';
				foreach($db->query($q) as $p) {
					$procesos[]=$p;
				}
				
				$_T['maintitle']='DRAGON - Generador Carga Dragon';
				$_T['maincontent']='
				<script>
					var procesos='.json_encode($procesos).';
				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
				<b>Seleccione Proceso:</b>
				<br>
				<select name="id_proceso" id="id_id_campana">
				<option value="">Seleccione...</option>
				';
				foreach($procesos as $p) {
					$_T['maincontent'].='<option value="'.$p['p_id_proceso'].'">'.$p['p_id_proceso'].' - UDN: '.$p['u_udn'].' | Camp: '.$p['c_campana'].' | Prc: '.$p['p_descripcion'].'</option>';
				}
				$_T['maincontent'].='
				</select>
				<br><br>
				<b>Indique UDN Dragon:</b>
				<br>
				<input type="text" name="send_name">

				<br><br>
				<b>Indique Campaña Dragon:</b>
				<br>
				<input type="text" name="campaign_name">
				<br><br>
				<button class="btn btn-primary">Siguiente</button>
				</form>
				';
				return 'flow';				
			break;
		}
	}
}