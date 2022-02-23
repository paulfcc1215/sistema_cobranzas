<?php
class CargasMensuales extends CargaModelo_Handler_Abstract {
	function getDescripcion() {
		return 'Cargas de Cuentas Interaguas';
	}
	
	function getTipoBase() {
		return 'Cargas de Cuentas Interaguas';
	}
	
	function execute($step,&$__data) {
		require 'uploadable/CuentasUploadable.class.php';
		switch($step) {
			case '3':
				$fname=decrypt($_POST['fname']);
				$ret=new CuentasUploadable($fname,';');
				$__data['hiddens']['fname']=encrypt($fname);
				$__data['step']='3';
				return $ret;
			break;
			
			case '2':
				$uid=uniqid();
				$fname=_TMP_UPLOAD_FOLDER.'/'.$uid;
				if(!move_uploaded_file($_FILES['archivo']['tmp_name'],$fname))
					throw new Exception('Error al leer archivo subido');
				$ret=new CuentasUploadable($fname,';');
				$__data['hiddens']['fname']=encrypt($fname);
				$__data['step']='3';
				return $ret;
			break;
			
			default:
				lbl_default:
				$__data['_T']['maintitle']='Carga Interaguas - Data Cuentas - Paso 1';
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