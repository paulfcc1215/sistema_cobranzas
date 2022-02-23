<?php
function smarty_function_box_telefonos_gestionados($params,$template) {
	$_telefonos=$params['data'];
	$html=array();
	$ptrs=array(
		'deudor'=>array(
			'pertenece_a'=>function($t) {
				return 'DEUDOR';
			}
		),
		'relaciones'=>array(
			'pertenece_a'=>function($t) {
				return $t['tipo_relacion'];
			}
		),
		'otras_personas'=>array(
			'pertenece_a'=>function($t) {
				return $t['tipo_deudor'];
			}
		),
	);
	if ($params['tipo']=='contacto'){
		foreach ($_telefonos['contacto'] as $tels){
			$html[]='<tr><td>'.$tels['fecha_gestion'].'<br>'.$tels['hora_gestion'].'</td>';
			$html[]='<td>DEUDOR</td>';
			$html[]='<td>'.$tels['tel_number'].'</td>';
			$html[]='<td>'.$tels['descripcion'].'</td>';
			$html[]='<td>'.$tels['fuente'].'</td>';
			$html[]='</tr>';
		}
	}else{
		foreach ($_telefonos['sin_contacto'] as $tels){
			$html[]='<tr><td>'.$tels['fecha_gestion'].'<br>'.$tels['hora_gestion'].'</td>';
			$html[]='<td>DEUDOR</td>';
			$html[]='<td>'.$tels['tel_number'].'</td>';
			$html[]='<td>'.$tels['descripcion'].'</td>';
			$html[]='</tr>';
		}
	}
	/*foreach($_telefonos as $ptr=>$tt) {
		foreach($tt as $t) {
			if(!$t['tiene_gestion']) {
				$html[]='<tr class="sin_gestion">';
			}elseif($t['tiene_promesa']){
				$html[]='<tr class="gestion_promesa">';
			}elseif($t['contacto_primera_persona']){
				$html[]='<tr class="gestion_primera_persona">';
			}elseif($t['contacto_tercera_persona']){
				$html[]='<tr class="gestion_tercera_persona">';
			}else{
				$html[]='<tr class="gestionado">';
			}
			
			$html[]='<td>'.$ptrs[$ptr]['pertenece_a']($t).'</td>';
			$html[]='<td>'.$t['telefono'].'<br>'.implodeNotEmpty(' ',array(
				$t['persona']['primer_nombre'],
				$t['persona']['segundo_nombre'],
				$t['persona']['primer_apellido'],
				$t['persona']['segundo_apellido']
				)).'</td>';
			if($t['tiene_gestion']) {
				$html[]='<td>'.$t['mejor_gestion']['tipificacion_descripcion'].'<br>('.date('d/m/Y H:i:s',strtotime($t['mejor_gestion']['fecha_inicio'])).')</td>';
			}else{
				$html[]='<td>N/A</td>';
			}
			$html[]='<td>'.$t['origen'].'</td>';
			$html[]='</tr>';
		}
	}*/
	return implode('',$html);
}