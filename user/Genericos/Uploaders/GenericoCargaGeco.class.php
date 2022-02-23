<?php

class migrarRegistroCargaGeco extends CargaModelo_Actualizaciones_Abstract implements CargaModelo_Uploadable_Interface, Iterator{
	
	function __construct($cedente,$carga) {
		$this->db=DB::getInstance();
		$this->odbc=new ODBC('Gecko','sgecoaccess','Sg2019');
		$query='
			SELECT
				DISTINCT cr.numero_operacion,cr.saldo
			FROM dbo.Deudores d 
				JOIN Creditos cr on(cr.id_deudor=d.id_deudor) 
			WHERE cr.cedente=\''.$cedente.'\' AND d.fecha_carga in (\''.implode("','",$carga).'\')
			--AND cr.numero_operacion=\'432043\'
		';
		$this->cedente=$cedente;
		$this->q=$this->odbc->query($query);
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
		$db=DB::getInstance();
		$odbc=new ODBC('Gecko','sgecoaccess','Sg2019');
		$query='
			SELECT
				d.identificacion_deudor as identificacion,d.tipo_identificacion, CASE d.nombres_completos	WHEN \'\' THEN d.nombres +\' \'+ d.apellido_p	ELSE d.nombres_completos END AS nombre,
				cr.saldo,
				p.fecha_pago,p.valor,p.tipo_transaccion,
				t.telefono_depurado
			FROM dbo.Deudores d 
				LEFT JOIN Telefonos t on (t.id_deudor=d.id_deudor)
				JOIN Creditos cr on (cr.id_deudor=d.id_deudor) 
				LEFT JOIN Pagos p on (p.id_deudor=d.id_deudor)
			WHERE cr.cedente=\''.$this->cedente.'\' AND cr.numero_operacion=\''.$line['numero_operacion'].'\'
		';
		$telefonos=array();
		foreach ($odbc->query($query) as $value) {
			foreach ($value as $key2 => $value2) {
				if($key2=='telefono_depurado' and $value2!=''){
					$telefonos[]=$value2;
				}else{
					$line[$key2]=$value2;
				}
			}
		}
		$cuenta=new CargaModelo_Item_Cuenta();
		if (!preg_match('#^\d+\.\d+$#',$line['numero_operacion']) and strpos($this->cedente, 'CLARO')!==false) {
			throw new Exception('Contrato inválido en línea '.$this->ptr.' ('.$line['numero_operacion'][1].')');
		}
		$cuenta->numero_cuenta=$line['numero_operacion'];
		$cuenta->valor_actual=$line['saldo'];
		$cuenta->persona_responsable = new CargaModelo_Item_Persona();
		switch($line['tipo_identificacion']) {
			case 'RUC':
				$cuenta->persona_responsable->tipo_identificacion='RUC';
			break;
			case 'PASAPORTE':
				$cuenta->persona_responsable->tipo_identificacion='PASAPORTE';
			break;
			case 'CONSUMIDOR FINAL':
				$cuenta->persona_responsable->tipo_identificacion='OTRO';
			break;
			case 'CÉDULA DE CIUDADANIA':
				$cuenta->persona_responsable->tipo_identificacion='CEDULA';
			break;
			default:
				if($line['tipo_identificacion']!=''){
					//throw new Exception('No se conoce el tipo de identificacion "'.$line['tipo_identificacion'].'" para la cédula '.$line['identificacion'].' (Solo se permiten {CÉDULA DE CIUDADANIA, CONSUMIDOR FINAL, PASAPORTE, RUC}');
				}
				if(preg_match('#^0?\d{12}$#',$line['identificacion'])) {
					$cuenta->persona_responsable->tipo_identificacion='RUC';
				}else if(preg_match('#^\d{10}$#',$line['identificacion']) && Helpers::luhn_validate($line['identificacion'])) {
					$cuenta->persona_responsable->tipo_identificacion='CEDULA';
				}else if(preg_match('#^\d{9}$#',$line['identificacion']) && Helpers::luhn_validate('0'.$line['identificacion'])) {
					$cuenta->persona_responsable->tipo_identificacion='CEDULA';
				}else if(preg_match('#[A-Za-z]#',$line['identificacion'])) {
					$cuenta->persona_responsable->tipo_identificacion='PASAPORTE';
				}else{
					$cuenta->persona_responsable->tipo_identificacion='OTRO';
				}
			break;
		}
		if(preg_match('#^[1-9]\d{8}$#',$line['identificacion']) && Helpers::luhn_validate('0'.$line['identificacion'])) {
			$line['identificacion']='0'.$line['identificacion'];
		}
		$cuenta->persona_responsable->identificacion=$line['identificacion'];
		$cuenta->persona_responsable->primer_nombre=$line['nombre'];

		foreach($telefonos as $t) {
			foreach ($this->parseTelefonos($t) as $tel) {
				$cuenta->persona_responsable->add_tel($tel);
			}
		}
		$act = new CargaModelo_Item_CuentaActualizacion();
		if($line['valor']!=''){
			$fecha=date_create($line['fecha_pago']);
			if(!preg_match('#^(\-|\+)?\d+(\.\d+)?$#',$line['valor'])){
				$aux_valor=explode('.', $line['valor']);
				if (count($aux_valor)>2){
					$line['valor']='0.00';
				}elseif ($aux_valor[0]==''){
					$line['valor']='0.'.$aux_valor[1];
				}
			}
			
			if($line['valor']>0)
				$valor=$line['valor']*(-1);
			else
				$valor=$line['valor'];

			$act->set('PAGO',$valor,date_format($fecha,"Y-m-d"));
			$cuenta->pushActualizacion($act);
		}
		
		$ret=array(
			'cuenta'=>&$cuenta,
			'otros_datos'=>array()
		);
		return $ret;
	}

	function next() {
		$this->q->next();
	}
	
	function current() {
		return $this->processRecord($this->q->current());
	}
	
	function rewind() {
		$this->q->rewind();
	}
	
	function key() {
		return $this->q->key();
	}
	
	function valid() {
		return $this->q->valid();
	}

}

class GenericoCargaGeco extends CargaModelo_Handler_Abstract {
	function getTipoBase() {
		return 'Generico - Cargar desde SGECO';
	}
	
	function getDescripcion() {
		return 'Generico - Migra Carga desde SGECO';
	}
	
	
	function execute($step, &$__data) {
		switch($step) {
			case '2':
				if($_POST['cedente']=='') throw new Exception('No ha seleccionado cedente');
				if($_POST['carga']=='') throw new Exception('No ha seleccionado carga');
				return new migrarRegistroCargaGeco($_POST['cedente'],$_POST['carga']);
			break;
			
			case '1':
				$odbc_geco = new ODBC('Gecko','sgecoaccess','Sg2019');
				$__data['_T']['maincontent']='<h1>Seleccione parámetros de migración</h1>
				<form method="POST" action="?'.Helpers::arr_to_url($_GET,array(),array('step2'=>'2')).'" enctype="multipart/form-data">
				<table style="width:100%;">
					<tr>
						<td width="30%">
							Seleccione Cedente:
							<select class="form-control" name="cedente" id="cedente" onchange="change_cedente()">
								<option value="">Seleccione Cedente...</option>';
								$q='SELECT DISTINCT(cr.cedente) FROM dbo.Deudores d JOIN dbo.Creditos cr on (d.id_deudor=cr.id_deudor)';
								foreach ($odbc_geco->query($q) as $value) {
									$__data['_T']['maincontent'].='<option value="'.$value['cedente'].'">'.$value['cedente'].'</option>';
								}
								$__data['_T']['maincontent'].='
							</select>
						</td>
						<td width="5%"></td>
						<td width="30%">
							<div id="cargando" style="display:none;"><img src="../../cobranzas/template/assets/tenor.gif" alt="loading" width="200" height="150"/></div>
							Seleccione Carga:
							<select class="form-control" name="carga[]" id="carga" onchange="change_carga()" multiple size="20" style="height: 100%;">
								<option value="">Seleccione...</option>
							</select>
						</td>
						<td width="5%"></td>
						<td width="30%">
							Registros Existentes:
							<input type="text" readonly class="form-control" id="registros" value="0"/>
						</td>
					</tr>
				</table>
				<br>
				<button class="btn btn-primary">Cargar</button>
				</form>';
				$__data['_T']['bottom_jscript'].='
					var cargas_numRegistros;
					function change_cedente(){
						$("#carga").empty();
						$("#carga").append("<option value=\"\">Seleccione...</option>");
						$("#registros").val("0");
						if($("#cedente").val()!=""){
							$("#cargando").show();
							$.ajax({
								method: "POST",
								url: "../../cobranzas/others/getCargasSGECO.php",
								data: { 
									accion:"getCargas",
									cedente:$("#cedente").val(),
								},
								success: function(json_cargas){
									var cargas = JSON.parse(json_cargas);
									cargas_numRegistros = cargas;
									$.each(cargas, function(i,o){
										$("#carga").append("<option value=\""+i+"\">"+i+" [Num. Registros: "+o+"]"+"</option>");
									});
									$("#cargando").hide();
								}
							})
						}
					}
					function change_carga(){
						$("#registros").val("0");
						if($("#cedente").val()=="") return false;
						if($("#carga").val()=="") return false;
						$("#registros").val(cargas_numRegistros[$("#carga").val()]);
					}
				';
			break;
		}
	}
}