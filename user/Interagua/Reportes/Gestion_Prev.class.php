<?php
class Gestion_Prev implements Reporte_Interface {
	private $cache=array();
	
    public function getCamposRequeridos() {
		return array(
			'ciclo',
			'nro fact adeudadas',
		);
	}
	
    public function getTData($id_tipificacion) {
		if(!array_key_exists($id_tipificacion,$this->cache)) {
			$db=DB::getInstance();
			$this->cache[$id_tipificacion]=$db->query('SELECT * FROM "aux_tables"."interagua_tipif_extra_data" join gestiones.tipificacion t using(id_tipificacion) WHERE id_tipificacion='.$id_tipificacion)->current();
		}	
		return $this->cache[$id_tipificacion];
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db=DB::getInstance();
		$udn=getUdnByName('Interagua');
		$campanas=getCampanasByUdn($udn['id_udn']);
		foreach($campanas as &$c) {
			$c['procesos']=getProcesoByCampana($c['id_campana']);
			foreach($c['procesos'] as $p) {
				//if($p['status']!=1) continue;
				$procesos[$p['id_campana']][]=$p;
			}
			unset($c);
		}
		foreach ($procesos as $id_campana => $proceso) {
			foreach ($proceso as $p) {
				$cargas[$p['id_proceso']]=getCargasByProcesoId($p['id_proceso']);
			}
		}
		switch($_get['step']) {
			case '2':
				if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_post['fecha_desde'])) throw new Exception('Fecha desde inválida');
                if(!preg_match('#^\d{2}/\d{2}/\d{4}#',$_post['fecha_hasta'])) throw new Exception('Fecha hasta inválida');
				$proceso=getProceso($_post['id_proceso']);
				if(!$proceso) throw new Exception('Proceso no existe');
				$reporte = 'gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';

				$q='
					SELECT
						'.get_query_fields('gestion','g','g_','gestiones',true).',
						'.get_query_fields('cuenta','c','c_','cuentas',true).',
						'.get_query_fields('persona','p','p_','personas',true).',
						'.get_query_fields('proceso','pr','pr_','campanas',true).',
						'.get_query_fields('tipificacion','t','t_','gestiones',true).',
						'.get_query_fields('campana','camp','camp_','campanas',true).'
					FROM
						gestiones.gestion g
						JOIN cuentas.cuenta c USING (id_cuenta)
						JOIN campanas.proceso pr USING (id_proceso)
						--JOIN cargas.carga ca ON (ca.id_proceso=pr.id_proceso)
						JOIN campanas.campana camp USING (id_campana)
						JOIN personas.persona p ON (c.id_deudor=p.id_persona)
						JOIN gestiones.tipificacion t USING (id_tipificacion)
					WHERE
						g.id_cuenta IN (SELECT id_cuenta FROM cuentas.cuenta WHERE id_proceso='.$proceso['id_proceso'].')
						AND date(g.fecha_inicio) BETWEEN \''.Helpers::dmy2ymd($_post['fecha_desde']).'\' AND \''.Helpers::dmy2ymd($_post['fecha_hasta']).'\'
						--AND ca.id_carga in ('.implode(",",$_post['id_carga']).')
					ORDER BY g.fecha_inicio ASC
					';
				
				
				//escribir cabecera
				$lines[] = array(
							'Ciclo',
							'Identificación',
							'Contrato',
							'Nombre',
							'# Facturas',
							'Saldo Pendiente',
							'Gestión',
							'Cod Tipificacion',
							'Estado gestión',
							'Proveedor Asignado',
							'Fecha Gestión',
							'Teléfono',
							'Observación',
							'Login',
							'Fecha Compromiso',
							'Valor Compromiso',
							'Campaña',
							'Nombre Ciclo',
						);
				
				foreach($db->query($q) as $gestion) {
					$datos_nm=getCargaNoMapeada($gestion['g_id_cuenta']);
					$datos_nm_most_recent=array_keys($datos_nm)[0];
					$tdata=$this->getTData($gestion['t_id_tipificacion']);
					$line=array(
						// Ciclo
						$datos_nm[$datos_nm_most_recent]['ciclo'],
						// Identificación
						$gestion['p_identificacion'],
						// Contrato
						$gestion['c_cuenta'],
						// Nombre
						Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
						// # Facturas
						$datos_nm[$datos_nm_most_recent]['nro fact adeudadas'],
						// Saldo Pendiente
						$gestion['c_valor_actual'],
						// Gestión
						$gestion['t_descripcion'],
						// Codced
						$tdata['codced'],
						// Estado
						$tdata['estado'],
						// Proveedor Asignado
						'CANT',
						// Fecha Gestión
						date('d/m/Y H:i:s',strtotime($gestion['g_fecha_inicio'])),
						// Teléfono
						$gestion['g_tel_number'],
						// Observación
						$gestion['g_observacion'],
						// Login
						$gestion['g_user_name'],
						// Fecha Compromiso
						($gestion['g_fecha_compromiso']!='')?date('d/m/Y',strtotime($gestion['g_fecha_compromiso'])):'',
						// Valor Compromiso
						$gestion['g_monto_compromiso'],
						// Campaña
						$gestion['camp_campana'],
						// Nombre Ciclo
						$gestion['pr_descripcion'],
 					);
					foreach($line as &$l) {
						$l=str_replace("\t",' ',str_replace("\n",' ',str_replace("\r",'',$l)));
						unset($l);
					}
					$lines[]=$line;
					
					
					//Si tiene tipificacion "VISITA" generamos el pdf y se coloca en la ruta /tmp/
					if ($gestion['t_id_tipificacion']==163){
						$datos_pdf = array(
							'fecha'=>date('Y M d'),
							'nombre_cliente'=>Helpers::implodeNotEmpty(' ',array($gestion['p_primer_nombre'],$gestion['p_segundo_nombre'],$gestion['p_primer_apellido'],$gestion['p_segundo_apellido'])),
							'identificacion'=>$gestion['p_identificacion'],
							'deuda'=>$gestion['c_valor_actual'],
							'facturas_vencidas'=>$datos_nm[$datos_nm_most_recent]['nro fact adeudadas'],
							'contrato'=>$gestion['c_cuenta'],
							'empresa'=>'INTERAGUA',
						);
						$result[$datos_pdf['identificacion'].'.pdf']=$this->createPDF($datos_pdf);
					}
				}
				
				//crear el archivo reporte de gestion
				$reporte_gestion = new Helpers_CSV_Writer();
				$reporte_gestion->setLines($lines);
				$result[$reporte]=$reporte_gestion->getFilePath();
				
				return 'file_list';
				
			break;
			
			default:
				
				$_T['maintitle']='INTERAGUA - Reporte de Gestion Prev';
				$_T['maincontent']='
				<script>
					var campanas='.json_encode($campanas).';
					var procesos='.json_encode($procesos).';
					var cargas='.json_encode($cargas).';
					function updateProcesos(campana) {
						var proceso_combo=$("#id_id_proceso");
						proceso_combo.html("<option value=\'\'>Seleccione...</option>");
						if(campana=="") return;
						var html=[];
						for(var i in procesos[campana]) {
							var ptr=procesos[campana][i];
							html.push("<option value=\'"+ptr.id_proceso+"\'>"+ptr.id_proceso+" - "+ptr.descripcion+"</option>");
						}
						proceso_combo.html(proceso_combo.html()+html.join(""));
						
					}
					function updateCargas(proceso) {
						var carga_combo=$("#id_carga");
						carga_combo.html("<option value=\'\'>Seleccione...</option>");
						if(proceso=="") return;
						var html=[];
						for(var i in cargas[proceso]) {
							var ptr=cargas[proceso][i];
							html.push("<option value=\'"+ptr.id_carga+"\'>"+ptr.id_carga+" - "+ptr.descripcion+"</option>");
						}
						carga_combo.html(carga_combo.html()+html.join(""));
					}
				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Campaña:</b>
					<br>
					<select name="id_campana" id="id_id_campana" onchange="updateProcesos(this.value)">
						<option value="">Seleccione...</option>
						';
						foreach($campanas as $c) {
							$_T['maincontent'].='<option value="'.$c['id_campana'].'">'.$c['id_campana'].' - '.$c['campana'].'</option>';
						}
						$_T['maincontent'].='
					</select>
					<br><br>
					<b>Seleccione Proceso:</b>
					<br>
					<select name="id_proceso" id="id_id_proceso" onchange="updateCargas(this.value)">
						<option value="">Seleccione...</option>
					</select>
					<!--<br><br>
					<b>Seleccione Carga:</b>
					<br>
					<select name="id_carga[]" id="id_carga" multiple style="width:300px; height:200px;">
						<option value="">Seleccione...</option>
					</select>-->
					<br><br>
					<b>Indique Rango de Fecha</b>
					<br>
					<table border="0" class="t">
						<tr>
							<td>
								Desde:<input type="text" name="fecha_desde" class="fecha" value="'.date('d/m/Y').'">
							</td>
						</tr>
						<tr>
							<td>
								Hasta:<input type="text" name="fecha_hasta" class="fecha" value="'.date('d/m/Y').'">
							</td>
						</tr>
					</table>
					<br><br>
					<button class="btn btn-primary">Siguiente</button>
				</form>
				';
				return 'flow';
			break;
		}
		
	
	}
    
	public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {
	
	}
	
	private function createPDF($datos_pdf){

		// initiate FPDI
		$pdf = new FPDI();
		
		// get the page count
		$pageCount = $pdf->setSourceFile(dirname(__FILE__).'/notificacion_legal.pdf');

		// iterate through all pages
		for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
			// import a page
			$templateId = $pdf->importPage($pageNo);
			// get the size of the imported page
			$size = $pdf->getTemplateSize($templateId);
			// create a page (landscape or portrait depending on the imported page size)
			if ($size['w'] > $size['h']) {
				$pdf->AddPage('L', array($size['w'], $size['h']));
			} else {
				$pdf->AddPage('P', array($size['w'], $size['h']));
			}
			// use the imported page
			$pdf->useTemplate($templateId, -5, -3, 220);
			$pdf->setFont('Helvetica','B',10);
		}

		//FECHA
		$pdf->Text(144, 27,$datos_pdf['fecha']);
		//NOMBRE CLIENTE
		$pdf->Text(27, 50,utf8_decode($datos_pdf['nombre_cliente']));
		//IDENTIFICACION
		$pdf->Text(34, 60,$datos_pdf['identificacion']);
		//DEUDA
		$pdf->Text(45, 69,'$ '.$datos_pdf['deuda']);
		//FACTURAS VENCIDAS
		$pdf->Text(130, 69,$datos_pdf['facturas_vencidas']);
		//CONTRATO
		$pdf->Text(53, 78.5,$datos_pdf['contrato']);
		//EMPRESA
		$pdf->Text(56, 113.5,$datos_pdf['empresa']);
		//DEUDA EN TEXTO
		$pdf->Text(80, 123.5,'$ '.$datos_pdf['deuda']);
		// Output the new PDF
		$res='/tmp/'.$datos_pdf['identificacion'].'.pdf';
		$pdf->Output('F',$res);

		return $res;

	}
	
}