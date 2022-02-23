<?php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Cartera_Banco_Territorial extends CargaModelo_Uploadable_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	private $header;
	private $xlsx;

	private $ptr=0;

	function __construct($fpath) {
		$required_columns=array(
			'identificacion_deudor',
			'nombre_deudor',
			'numero_prestamo',
			'total_a_pagar',
			'comision_cobranza',
			'dias_mora',
			'fecha_ultimo_pago',
			'telefono',
			'agencia',
			'tipo_titular_garante_codeudor',
		);

        $this->xlsx = new Helpers_ExcelReader($fpath);
        $this->xlsx->setFechasCol('D');
        $this->header = $this->xlsx->getHead();
        $required = array(
            'Identificacion',
            'Nombre_Cliente',
            'cr_numeroOperacion',
            'FECHA DE VENCIMIENTO credito',
            'DIAS MORA ACTUAL',
            'SUBTOTAL',
            'SALDO ACTUAL',
            'Ciudad',
        );
        
        foreach($required as $r) {
            if(!in_array($r,$required))
                throw new Exception('La columna requerida '.$r.' no existe');
        }
		
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

class Cartera extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Banco Territorial - Cartera';
	}
	
	function getDescripcion() {
		return 'Banco Territorial - Cartera';
	}
	
	function execute($step, &$__data) {
		$SM=SessionManager::getInstance();
		switch($step) {
			case '3':
                try {
                    $uploader=new Cartera_Banco_Territorial(_TMP_UPLOAD_FOLDER.'/'.$SM->carga_process['source_file']);
                    return $uploader;
                }catch(Exception $e) {
                    $error=$e->getMessage();
                    goto lbl_1;
                }
				
			break;
			
			case '2':
				if($_FILES['data']['error']!='0')
					throw new Exception('Error '.$_FILES['data']['error'].' al cargar archivo');
				$aux=$SM->carga_process;
				$aux['source_file']=uniqid();
				$aux['original_filename']=$_FILES['data']['name'];
				$SM->carga_process=$aux;
				if(!move_uploaded_file($_FILES['data']['tmp_name'],_TMP_UPLOAD_FOLDER.'/'.$aux['source_file']))
					throw new Exception('Error al mover archivo subido');
				header('Location: ?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'3','token'=>$SM->carga_process['expected_token'])));
				die();
			break;
			
			case '1':
                lbl_1:
				$__data['_T']['maincontent'].='<h1>Carga de Carteras - Banco Territorial</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2','token'=>$SM->carga_process['expected_token'])).'" enctype="multipart/form-data">
				';
                if($error!='') {
                    $__data['_T']['maincontent'].='<h3 style="color: maroon;">'.$error.'</h3>';
                }
                $__data['_T']['maincontent'].='
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
			'ModeloCartera.xlsx'=>''
		);
		if($with_data) {
			$ret['ModeloCartera.xlsx']=file_get_contents(dirname(__FILE__).'/modelo_cartera.xlsx');
		}
		return $ret;
		
	}	
}
