<?php
$db=DBv2::getInstance();
$_T['css_files'][]='template/assets/custom_toggle/custom_toggle.css';


switch($_GET['step']) {
	case 'updateCCStatus':
		$q=$db->query('SELECT * FROM aux_tables.com_crd_dd_control_status_bases WHERE id_subida='.$_POST['id_subida']);
		if($q->numRows()==0) {
			$db->query('INSERT INTO aux_tables.com_crd_dd_control_status_bases (id_subida,status,fecha_cambio,usuario_cambio) VALUES (
				\''.$_POST['id_subida'].'\',
				\''.($_POST['new_status']=='true'?'1':'0').'\',
				\'NOW()\',
				\''.$SM->user['usr_logname'].'\'
			)');
		}else{
			$rec=$q->current();
			$db->query('UPDATE aux_tables.com_crd_dd_control_status_bases SET
				status=\''.($_POST['new_status']=='true'?'1':'0').'\',
				fecha_cambio=\'NOW()\',
				usuario_cambio=\''.$SM->user['usr_logname'].'\'
				WHERE id='.$rec['id']
			);
		}
		Log::addLog('DESEMBOLSO_DIRECTO_ACTIVACION_DESACTIVACION_BASE',__FILE__,$_POST);
		echo '1';
		die();
	break;
	
	default:
		$_T['bottom_jscript'].='
		function updateStatus(cbox) {
			$.ajax({
				"url":"?'.Helpers::arr_to_url($_GET,array('step'),array('step'=>'updateCCStatus')).'",
				"method":"POST",
				"data":{
					"id_subida":$(cbox).val(),
					"new_status":$(cbox).prop("checked")
				},
				"async":false,
				"success":function(d) {
					if(d!="1") {
						alert(d.substr(2));
						window.location=window.location;
					}
				},
				"failure":function() {
					alert("Ocurrio una falla al comunicarse con server");
					window.location=window.location;
				}
			});
		}
		';
		$query='SELECT * FROM subidas WHERE id_instrumento_tipo=74 ORDER BY id_subida DESC';
		$_T['maintitle']='Activación/Desactivación Bases Desembolso Directo';
		$_T['maincontent'].='<table class="table table-striped">';
		$_T['maincontent'].='<tr>';
		$_T['maincontent'].='<th>Status</th>';
		$_T['maincontent'].='<th>Id Subida</th>';
		$_T['maincontent'].='<th>Fecha</th>';
		$_T['maincontent'].='<th>Nombre</th>';
		$_T['maincontent'].='</tr>';
		foreach($db->query($query) as $q) {
			$q1=$db->query('SELECT * FROM aux_tables.com_crd_dd_control_status_bases WHERE id_subida='.$q['id_subida']);
			$status=false;
			if($q1->numRows()==1) {
				$status=$q1->current()['status']=='1'?true:false;
			}
			
			$_T['maincontent'].='<tr>';
			$_T['maincontent'].='<td>
			<div>
				<label class="switch">
					<input type="checkbox" '.($status?' checked="1"':'').' value="'.$q['id_subida'].'" onchange="updateStatus(this)">
					<span class="slider"></span>
				</label>
			</div>
			</td>';
			$_T['maincontent'].='<td>'.$q['id_subida'].'</td>';
			$_T['maincontent'].='<td>'.$q['fecha_subida'].'</td>';
			$_T['maincontent'].='<td>'.$q['descripcion'].'</td>';
			$_T['maincontent'].='</tr>';
		}
		$_T['maincontent'].='</table>';
		
	break;
}
