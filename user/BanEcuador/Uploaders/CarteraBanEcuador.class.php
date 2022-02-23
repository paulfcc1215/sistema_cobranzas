<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Cargador_Cartera_Ban_Ecuador extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $header;
	private $xlsx;
	
	private $ptr=0;

	function __construct($fpath) {
		$required=array(
			'zonal',
			'sucursal',
			'oficina',
			'operacion',
			'cedula_ruc',
			'nombre',
			'clase_cartera',
			'telefonos',
			'nombre_garante',
			'telefono_garante',
			'dias_mora',
			'saldo_vencido',
			'cartera_imp_actual',
			'saldo_inicial'
		);

		$this->xlsx = new Helpers_ExcelReader($fpath);
		$this->header = $this->xlsx->getHead();
		
        foreach($this->header as $h) {
            if(!in_array(strtolower($h),$required))
                throw new Exception('La columna requerida '.$h.' no existe');
        }
		$this->setTipoCarga('cartera');

	}
	
	function parseTelefonos($telefonos) {
		$ret=array();
		$aux=array();
		if(preg_match_all('#[^\d]#',$telefonos,$matches)) {
			$matches=array_unique($matches[0]);
			foreach($matches as $m) {
				foreach(explode($m,$telefonos) as $t) {
					$t=trim($t);
					if(preg_match('#^\d+$#',$t)) $aux[]=$t;
				}
			}
			$aux=array_unique($aux);
		}else{
			$aux[]=$telefonos;
		}
		foreach($aux as $a) {
			$a=ltrim($a,'0');
			if(strlen($a)==7) $a='4'.$a;
			if(!preg_match('#^9[1-9]\d{7}$#',$a) && !preg_match('#^[2-7]\d{7}$#',$a)) {
				continue;
			}
			if(preg_match('#(.)\1{5}#',$a)) continue;
			$ret[]='0'.$a;
		}
		return $ret;
	}
	
	function processRecord(&$line) {

		$ret=array(
			'cuenta'=>null,
			'otros_datos'=>array()
		);
		foreach($line as $l) {
            $ll[$l['head']]=$l;
        }
        $line = $ll;
        unset($ll);

		if (trim($line['OPERACION']['value'])=='') throw new Exception('Número de operacion vacía en línea '.$this->ptr);
		if (trim($line['CEDULA_RUC']['value'])=='') throw new Exception('Cedula vacía en línea '.$this->ptr);
		
		$cuenta = new CargaModelo_Item_Cuenta();

		$cuenta->numero_cuenta = $line['OPERACION']['value'];
		$cuenta->valor_actual = round(str_replace(",","",$line['SALDO_INICIAL']['value']),2);
		if (round(str_replace(",","",$line['SALDO_VENCIDO']['value']),2) > round(str_replace(",","",$line['SALDO_INICIAL']['value']),2)){
			$pago = (round(str_replace(",","",$line['SALDO_INICIAL']['value']),2) - round(str_replace(",","",$line['SALDO_VENCIDO']['value']),2))*-1;
			$cuenta->add_actualizacion('AJUSTE+',$pago,date('Y-m-d'));
		}else{
			$pago = (round(str_replace(",","",$line['SALDO_INICIAL']['value']),2) - round(str_replace(",","",$line['SALDO_VENCIDO']['value']),2))*-1;
			$cuenta->add_actualizacion('PAGO',($pago>0?$pago*-1:$pago),date('Y-m-d'));
		}
		

		//persona responsable
		$cuenta->persona_responsable = new CargaModelo_Item_Persona();
		$cuenta->persona_responsable->tipo_identificacion='CEDULA';
		$cuenta->persona_responsable->identificacion=$line['CEDULA_RUC']['value'];
		$cuenta->persona_responsable->primer_nombre=$line['NOMBRE']['value'];
		//contactos
		foreach($this->parseTelefonos($line['TELEFONOS']['value']) as $t){
			$cuenta->persona_responsable->add_medio_contacto('TELEFONO',$t);
		}
		$ret['cuenta']=$cuenta;

		$ret['otros_datos']['zonal']=$line['ZONAL']['value'];
		$ret['otros_datos']['sucursal']=$line['SUCURSAL']['value'];
		$ret['otros_datos']['oficina']=$line['OFICINA']['value'];
		$ret['otros_datos']['clase_cartera']=$line['CLASE_CARTERA']['value'];
		$ret['otros_datos']['garante']=$line['NOMBRE_GARANTE']['value'];
		$ret['otros_datos']['telefono_garante']=$line['TELEFONO_GARANTE']['value'];
		$ret['otros_datos']['dias_mora']=$line['DIAS_MORA']['value'];
		$ret['otros_datos']['saldo_vencido']=$line['SALDO_VENCIDO']['value'];
		$ret['otros_datos']['cartera_imp_actual']=$line['CARTERA_IMP_ACTUAL']['value'];

		return $ret;
	}

	// Iterator
	function next() {
		$this->xlsx->next();
	}
	
	function current() {
        return $this->processRecord($this->xlsx->current());
	}
	
	function rewind() {
		$this->xlsx->rewind();
	}
	
	function key() {
        return $this->xlsx->key();
	}
	
	function valid() {
		return $this->xlsx->valid();
	}
	
}

class CarteraBanEcuador extends CargaModelo_Handler_Abstract {

	function getTipoBase() {
		return 'Cartera';
	}
	
	function getDescripcion() {
		return 'Cartera';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
				$uploader=new Cargador_Cartera_Ban_Ecuador(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
				return $uploader;
			break;
			
			case '2':
				if($_FILES['data']['error']!='0') throw new Exception('Error '.$_FILES['data']['error'].' al cargar archivo');
				$aux=$SM->carga_process;
				$aux['source_file']=uniqid();
				$aux['original_filename']=$_FILES['data']['name'];
				$SM->carga_process=$aux;
				if(!move_uploaded_file($_FILES['data']['tmp_name'],_TMP_UPLOAD_FOLDER.'/'.$aux['source_file'])) throw new Exception('Error al mover archivo subido');
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
				die();
			break;
			
			case '1':
				$__data['_T']['maincontent']='<h1>Carga de Carteras - BanEcuador</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
					Seleccione el archivo:
					<input type="file" name="data">
					<br>
					<button class="btn btn-primary">Cargar</button>
				</form>';
			break;
		}

	}
	
	function getArchivoModelo($with_data=false) {
		$ret=array(
			'formato_carga_cartera.xlsx'=>''
		);
		if($with_data) {
			$ret['formato_carga_cartera.xlsx']=file_get_contents(dirname(__FILE__).'/formato_carga_cartera.xlsx');
		}
		return $ret;
	}

}



































	
	/*function processRecord(&$line) {
		$ret=array(
			'cuenta'=>null,
            'otros_datos'=>array()
		);
        foreach($line as $l) {
            $ll[$l['head']]=$l;
        }
        $line = $ll;
        unset($ll);

		$cuenta = new CargaModelo_Item_Cuenta();
        $cuenta->numero_cuenta=$line['cr_numeroOperacion']['value'];
        $cuenta->valor_actual=$line['SALDO ACTUAL']['value'];
        $cuenta->persona_responsable=new CargaModelo_Item_Persona();
        $cuenta->persona_responsable->tipo_identificacion='CEDULA';
        $cuenta->persona_responsable->identificacion=$line['Identificacion']['value'];
        $cuenta->persona_responsable->primer_nombre=$line['Nombre_Cliente']['value'];
		$ret['cuenta']=$cuenta;
        foreach(array('FECHA DE VENCIMIENTO credito','SUBTOTAL','SALDO ACTUAL','Ciudad') as $a) {
            $ret['otros_datos'][str_replace(' ','_',strtolower($a))]=$line[$a]['value'];
        }
        $ret['otros_datos']['fecha_de_vencimiento_credito']=explode(' ',$ret['otros_datos']['fecha_de_vencimiento_credito'])[0];
        
		return $ret;
	}*/
