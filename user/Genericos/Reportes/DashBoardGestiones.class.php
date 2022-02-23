<?php
class DashBoardGestiones implements Reporte_Interface{
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

    public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		$db = DB::getInstance();
		//get UDNS
		$udns = array();
		foreach(AutoModel::getInstance('estructura','udn',$db)->getAll() as $record){
			$udns[$record->id_udn]=$record->udn;
		}
		//get CAMPANAS
		foreach($udns as $id_udn => $udn){
			foreach(getCampanasByUdn($id_udn) as $c){
				$campanas[$id_udn][$c['id_campana']] = $c['campana'];
			}
		}
		
		//get PROCESOS
		foreach($campanas as $c){
			foreach ($c as $id_c => $nc){
				foreach (getProcesoByCampana($id_c) as $p)
					$procesos[$id_c][$p['id_proceso']]=$p['descripcion'];
			}
		}
		$_T['bottom_jscript']='
			var campanas='.json_encode($campanas).';
			var procesos='.json_encode($procesos).';
			var stats_by_type = [];

			function updateCampanas(id_udn){
				$("#id_id_campana").empty();
				$("#id_id_campana").append("<option value=\"\">Seleccione...</option>");
				$("#id_id_proceso").empty();
				$("#id_id_proceso").append("<option value=\"\">Seleccione...</option>");
				if (id_udn==""){
					return false;
				}
				$.each(campanas[id_udn],function(id_c,c){
					$("#id_id_campana").append("<option value=\""+id_c+"\">"+id_c+" - "+c+"</option>");
				})
			}

			function updateProcesos(id_campana) {
				$("#id_id_proceso").empty();
				if(id_campana==""){
					$("#id_id_proceso").append("<option value=\"\">Seleccione...</option>");
					return false;
				};
				$.each(procesos[id_campana],function(id_p,p){
					$("#id_id_proceso").append("<option value=\""+id_p+"\">"+id_p+" - "+p+"</option>");
				})
			}

			function clic_fechas(me){
				$("#fechas").hide();
				if($(me).val()=="F"){
					$("#fechas").show();
				}
			}

			var ajaxBusy=false;
			function clic_get_report(){
				if ($("#id_id_proceso").val()=="") {
					return;
				}
				var tipo_reporte="T";
				if ($("#chk_x_agente").is(":checked")){
					tipo_reporte="A";
				}
				//setInterval(update,8000);
				update(tipo_reporte);
			}

			function draw(container,data){
				var chart1 = Highcharts.chart(container,{
					title: {
						text: data.descripcion
					},
					xAxis: {
						type: "category"
					},
					plotOptions: {
						pie: {
							allowPointSelect:true,
							cursor:"pointer",
							showInLegend:true
						}
					},
					legend:{
						layout:"vertical",
						align:"left",
						verticalAlign:"middle",
						labelFormat:"{name}: [{y}]",
						title:{
							text:""
						}
					},
					tooltip: {
						headerFormat: "",
						pointFormat: "<b style=\"color:#002EA4;font-size:120%;\">{point.name}: {point.y}</b>"
					},
					drilldown:{
						drillUpButton: {
							position: {
								x: 100,
								y: -20
							}
						}
					},
					series: [],
					chart: {
						type: "pie",
						cursor: "pointer",
						events: {
							drilldown: function(e){
								if(!e.seriesOptions){
									var aux_data=[];
									$.each(stats_by_type[data.id_proceso],function(parametro,count_gestion){
										aux_data.push([parametro,parseInt(count_gestion)]);
									})
									var chart = this,
										drilldowns = {
											gestiones: {
												name: "gestiones",
												data: aux_data,
											}
										},
										series = drilldowns[e.point.name];
									chart.showLoading("Obteniendo datos...");
									setTimeout(function(){
										chart.hideLoading();
										chart.addSeriesAsDrilldown(e.point, series);
									}, 1000);
								}
							},
							click: function(e){
								console.log(e);
							},
						},
					}
				});
				var series={};
				series.name=data.descripcion;
				series.colorByPoint=true;
				var stats=[];
				$.each(data,function(key,value){
					if (key=="id_proceso") return;
					if (key=="descripcion") return;
					if (key=="cuentas") return;
					var aux={};
					aux.name=key;
					aux.y=parseInt(value);
					aux.drilldown=key;
					stats.push(aux);
				})
				series.data=stats;
				chart1.addSeries(series,false,false);
				chart1.redraw();
			}

			function update(tipo_reporte){
				$("#main_report").empty();
				if (ajaxBusy){
					console.log("ocupado");
					return;
				}
				var f = new Date();
				var desde= hasta= f.getDate() + "/" + (f.getMonth() +1) + "/" + f.getFullYear();
				if ($("#fechas_rango").is(":checked")){
					desde = $("#desde").val();
					hasta = $("#hasta").val();
				}
				ajaxBusy=true;
				$.ajax({
					method: "POST",
					url: "?mod=reportes/ajax",
					data: { 
						action: "getStatsMovistar",
						id_proceso: $("#id_id_proceso").val(),
						desde: desde,
						hasta: hasta
					},
					success: function(json_result){
						ajaxBusy=false;
						var result = JSON.parse(json_result);
						if (result.status=="success"){
							if (result.data.length==0){
								$.notify("No existe datos para los parámetros indicados","info");
								return false;
							}
							$.each(result.data,function(i,d){
								$("#main_report").append("<figure class=\"highcharts-figure\"><div style=\"border:1px solid rgb(180,200,255);\" id=\"report_"+i+"\"></div></figure><br>")
								draw("report_"+i,d);
							})
						}else{
							$.notify(result.message,"error");
							return false;
						}
					}
				})
				//get data by users and proccess
				$.ajax({
					method: "POST",
					url: "?mod=reportes/ajax",
					data: { 
						action: "getStatsDetail",
						tipo_reporte: tipo_reporte,
						id_proceso: $("#id_id_proceso").val(),
						desde: desde,
						hasta: hasta
					},
					success: function(json_result){
						ajaxBusy=false;
						var result = JSON.parse(json_result);
						if (result.status=="success"){
							stats_by_type = result.data;
						}else{
							$.notify(result.message,"error");
							return false;
						}
					}
				})
			}

			function click_tipo_reporte(){
				clic_get_report();
			}
		';

		$_T['maintitle']='DashBoard de Gestiones';
		$_T['maincontent']='
			<table>
				<tr>
					<td valign="top" style="padding-right:15px; border-right:1px solid rgb(110,150,255);">
						
						<b>UDN:</b><br>
						<select name="id_udn" id="id_id_udn" class="form-control" onchange="updateCampanas(this.value)"><option value="">Seleccione...</option>';
						foreach($udns as $id_u => $u) {
							$_T['maincontent'].='<option value="'.$id_u.'">'.$id_u.' - '.$u.'</option>';
						}
						$_T['maincontent'].='
						</select><br>
						
						<b>CAMPAÑA:</b><br>
						<select name="id_campana" id="id_id_campana" class="form-control" onchange="updateProcesos(this.value)"><option value="">Seleccione...</option></select><br>
						
						<b>PROCESO</b>
						<br>
						<select name="id_proceso" id="id_id_proceso" class="form-control" multiple size="8"><option value="">Seleccione...</option></select><br>

						<label><input type="radio" name="fechas" id="fechas_hoy" value="H" onclick="clic_fechas(this)" checked/>Hoy('.date('Y-m-d').')</label><br>
						<label><input type="radio" name="fechas" id="fechas_rango" value="F" onclick="clic_fechas(this)"/>Por Fechas</label>
						<div id="fechas" style="display:none;">
							<input class="fecha" id="desde" value="'.date('d/m/Y').'"><br>
							<input class="fecha" id="hasta" value="'.date('d/m/Y').'">
						</div>
						<br><br>
						
						<button class="btn btn-primary" onclick="clic_get_report()">Get Report</button>
						<br><br>
						<a href="?mod=reportes/index">Regresar</a>
					</td>
					<td valign="top" style="padding-left:15px;width:80%;">
						<label><input type="radio" id="chk_x_agente" name="chk_tipo" onclick="click_tipo_reporte()" checked/>Por Agente</label>
						<label><input type="radio" id="chk_x_tipificacion" name="chk_tipo" onclick="click_tipo_reporte()"/>Por Tipificación</label><br><br>
						<div id="main_report"></div>
					</td>
				</tr>
			</table>';
		return 'flow';
	}

	public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {

	}
}