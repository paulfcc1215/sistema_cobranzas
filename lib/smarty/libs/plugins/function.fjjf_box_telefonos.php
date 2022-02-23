<?php
function smarty_function_fjjf_box_telefonos($params,$template) {
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
				return $t['tipo_responsable'];
			}
		),
	);
	foreach($_telefonos as $ptr=>$tt) {
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
			$html[]='<td>'.$t['persona']['identificacion'].'-'.implodeNotEmpty(' ',array(
				$t['persona']['primer_nombre'],
				$t['persona']['segundo_nombre'],
				$t['persona']['primer_apellido'],
				$t['persona']['segundo_apellido']
				)).'</td>';
			$html[]='<td>'.$t['telefono'].'</td>';
			$html[]='<td>'.($t['fuente']==''?'BASE':$t['fuente']).'</td>';
			/*if($t['tiene_gestion']) {
				$html[]='<td>'.$t['mejor_gestion']['tipificacion_descripcion'].'<br>('.date('d/m/Y H:i:s',strtotime($t['mejor_gestion']['fecha_inicio'])).')</td>';
			}else{
				$html[]='<td>N/A</td>';
			}
			$html[]='<td>'.$t['origen'].'</td>';*/
			$html[]='</tr>';
		}
	}
	return implode('',$html);
}