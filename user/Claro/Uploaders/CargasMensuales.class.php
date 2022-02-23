<?php
class CargasMensuales extends CargaModelo_Handler_Abstract {
	function getDescripcion() {
		return 'Cargas mensuales enviadas por Claro';
	}
	
	function getTipoBase() {
		return 'Base General Mensual';
	}
	
	function execute($step,&$__data) {
		switch($step) {
			case '3':
				try {
					$carga_handler=decrypt($_POST['carga_handler']);
					$file=decrypt($_POST['file']);
					if($carga_handler===false)
						throw new Exception('Carga Handler Invalido');
					if($file===false)
						throw new Exception('File inválido');
					if(!is_readable($file))
						throw new Exception('File no es leible');
					
					require dirname(__FILE__).'/includes/CargasMensualesUploadable.class.php';
					$sep=$_POST['separador'];
					if($sep=='t') $sep="\t";
					$clazz=new CargasMensualesUploadable($file,$sep);
					$clazz->pushFile($_POST['original_fname'],$file);
					return $clazz;
					
				}catch(Exception $e) {
					$error=$e->getMessage();
					goto lbl_default;
				}
			break;
			
			case '2':
				try {
					if($_FILES['archivo']['error']!=0) throw new Exception(getFileUploadErrorString($_FILES['archivo']['error']));
					$target=_TMP_UPLOAD_FOLDER.'/'.basename($_FILES['archivo']['tmp_name']);
					move_uploaded_file($_FILES['archivo']['tmp_name'],$target);
					$__data['_T']['maintitle']='Carga Claro - Data Mensual - Paso 2';
					$data=trim(file_get_contents($target));
					$num_rec=count(explode("\r\n",$data))-1;
					$__data['_T']['maincontent'].='
					<b>Total de registros: '.$num_rec.'</b>
					<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3')).'" enctype="multipart/form-data">
					'.UI_Helper::array_to_hidden($_POST).'
					<input type="hidden" name="file" value="'.encrypt($target).'">
					<input type="hidden" name="original_fname" value="'.$_FILES['archivo']['name'].'">
					<b>Seleccione Separador: </b>
					<select name="separador">
					<option value=";">; (punto y coma)</option>
					<option value="t">\\t (tabulador)</option>
					</select>
					<br><br>
					<button class="btn btn-primary">Siguiente</button>
					</form>
					';
					
				
				}catch(Exception $e) {
					$error=$e->getMessage();
					goto lbl_default;
				}
			
			break;
			
			default:
				lbl_default:
				$__data['_T']['maintitle']='Carga Claro - Data Mensual - Paso 1';
				if($error!='') {
					$__data['_T']['maincontent'].='<div style="color: maroon; font-weight: bold; font-size: 20px;">'.$error.'</div>';
				}
				$__data['_T']['maincontent'].='
				<b>Indique el archivo</b>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array('__upload'),array('step2'=>'2')).'" enctype="multipart/form-data">
				<input type="file" name="archivo">
				<br>
				<button class="btn btn-primary">Procesar</button>
				
				</form>
				';
				
			break;
		}
		
	}
}