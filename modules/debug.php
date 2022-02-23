<?php
switch($_GET['step']) {
	case 'dw':
		$qa0=$db->query('SELECT * FROM cargas.carga_data WHERE id_carga_data='.$_GET['id'])->current();
		
		$qa0['raw_data']=$db->unescape_bytea($qa0['raw_data']);
		
		$qa0['raw_data']=gzuncompress($qa0['raw_data']);
		header('Content-Disposition: attachment; filename="'.$qa0['nombre_archivo'].'"');
		
		echo ($qa0['raw_data']);
		die();
	break;
	default:
		
		$_T['maincontent']='<table border="1">';
		foreach($db->query('SELECT * FROM cargas.carga_data') as $r) {
			$_T['maincontent'].='<tr>';
			$_T['maincontent'].='<td>'.$r['id_carga'].'</td>';
			$_T['maincontent'].='<td><a href="?mod='.$_GET['mod'].'&step=dw&id='.$r['id_carga_data'].'">'.$r['nombre_archivo'].'</a></td>';
			$_T['maincontent'].='</tr>';
		}
		$_T['maincontent'].='</table>';
	break;
}