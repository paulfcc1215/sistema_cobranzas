<script>
	function updateCuentaData(id_cuenta) {
		$("#id_detalle_cuenta_box").html("<img src='template/assets/spinner5.gif' width='32'/> Cargando...");
		$("#id_gestiones_box_content").html("<img src='template/assets/spinner5.gif' width='32'/> Cargando...");
		$("#id_telefono_box").html("<img src='template/assets/spinner5.gif' width='32'/> Cargando...");
		$.ajax({
			"url":"ajax.php",
			"method":"POST",
			"data":{
				"a":"get_cuenta_data",
				"id_cuenta":id_cuenta,
			},
			"success":function(d) {
				try {
					d=$.parseJSON(d);
					if(!d.success)
						throw d.error;
					d=$.parseJSON(d.data);
					console.log(d);
					$("#id_detalle_cuenta_box").html(d.html.carga_no_mapeada);
					$("#id_gestiones_box_content").html(d.html.gestiones);
					$("#id_telefono_box").html(d.html.telefonos);
				}catch(err) {
					alert(err);
				}
			}
		});
	}
	function clickOnCuenta(row_cuenta) {
		row_cuenta=$(row_cuenta);
		var parents=row_cuenta.parents("tbody");
		parents.find(".selected_cuenta").each(function(k,o) {
			$(o).removeClass("selected_cuenta");
		});
		row_cuenta.addClass("selected_cuenta");
		cuentaSeleccionada=row_cuenta.data("id-cuenta");
		updateCuentaData(cuentaSeleccionada);
	}
</script>
{assign var=valor_deuda value=$main_cuenta['cuenta']['valor_original']|string_format:"%.2f"}
{assign var=valor_pagado value=$main_cuenta['cuenta']['valor_pagado']|string_format:"%.2f"}
{if $main_cuenta['proceso']['id_campana']==11 }
	{assign var=valor_deuda value=$main_cuenta['data_no_mapeada']['data']['cobro empresa2']|string_format:"%.2f"}
	{assign var=valor_pagado value=$main_cuenta['data_no_mapeada']['data']['pagos']|string_format:"%.2f"}
{/if}


<div class="card box_cliente">

	<div class="card-header">
		<b>Cuentas</b>
	</div>
	<div class="card-body" style="min-height: 250px; max-height: 200px; overflow-y: auto;">
		<!--
		<h5 class="card-title">1757181571 - FERNANDO JAVIER JIMENEZ FUENTES</h5>
		<p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
		-->
		
		<table class="table table-small" id="table-cuentas">
			<thead>
				<tr>
					<th>#</th>
					<th>Cuenta</th>
					<th>Identificacion</th>
					<th>Nombre</th>
					<th>Cedente</th>
					<!--<th>Fecha Cuenta</th>-->
					<th>Deuda Original</th>
					<th>
						{if $main_cuenta['proceso']['id_campana']==11 }
							Tiene Pagos
						{else}
							Valor Pagado
						{/if}
					</th>
					<th>Deuda Actual</th>
					<!--<th>% Pagado</th>-->
					{fjjf_call_closure fn=$hooks['smarty_client_box_header_append_th']}
				</tr>
			</thead>
			<tbody>
				<!-- cuenta seleccionada -->
				<tr class="cliente_row main_cuenta clickable selected_cuenta" onclick="clickOnCuenta(this);" data-id-cuenta="{$main_cuenta['cuenta']['id_cuenta']}">

					{if $main_cuenta['proceso']['id_campana']==11 }
					{$valor_pagado}
						{if $valor_pagado!=0}
							{assign var=valor_pagado value='SI'}
						{else}
							{assign var=valor_pagado value='NO'}
						{/if}
					{/if}

					<td class="num">1</td>
					<td class="cuenta">{$main_cuenta['cuenta']['cuenta']}<br><div style="font-size: 10px;">(Id: {$main_cuenta['cuenta']['id_cuenta']})</div></td>
					<td class="identificacion">{$main_cuenta['deudor']['identificacion']}</td>
					<td class="nombre">{implode_not_empty what=array({$main_cuenta['deudor']['primer_nombre']},{$main_cuenta['deudor']['segundo_nombre']},{$main_cuenta['deudor']['primer_apellido']},{$main_cuenta['deudor']['segundo_apellido']})}</td>
					<td class="udn">{$main_cuenta['udn']['udn']}<br>({$main_cuenta['campana']['campana']})<br>{$main_cuenta['proceso']['descripcion']}</td>
					<!--<td class="fecha_creacion">{$main_cuenta['cuenta']['fecha_creacion']|date_format:"%d/%m/%Y"}</td>-->
					<td class="valor_original">$ {$valor_deuda}</td>
					<td class="valor_pagado">$ {$valor_pagado}</td>
					<td class="valor_actual">$ {$main_cuenta['cuenta']['valor_actual']|string_format:"%.2f"}</td>
					<!--<td class="prcnt_pagado" style="white-space: nowrap !important;">{(100-($main_cuenta['cuenta']['valor_actual']*100/$main_cuenta['cuenta']['valor_original']))|string_format:"%.2f"} %</td>-->
					{fjjf_call_closure fn=$hooks['smarty_client_box_content_append_td']}
				</tr>
				<!-- fin cuenta seleccionada -->

				{assign var=count value=1}
				{assign var=total value=$main_cuenta['cuenta']['valor_actual']}
				{foreach from=$data['cuentas'] item=$i key=$k}
					{if $k!=$id_cuenta_seleccionada}
						{assign var=count value=$count+1}
						{assign var=total value=$total+$i['cuenta']['valor_actual']}
						{assign var=ultimopago value=$i['cuenta']['valor_pagado']}

						{if $main_cuenta['proceso']['id_campana']==11 }
							{if $ultimopago!=0}
								{assign var=ultimopago value='SI'}
							{else}
								{assign var=ultimopago value='NO'}
							{/if}
						{/if}

						<tr class="cliente_row clickable" onclick="clickOnCuenta(this);" data-id-cuenta="{$i['cuenta']['id_cuenta']}">
							<td class="num">{$count}</td>
							<td class="cuenta">{$i['cuenta']['cuenta']}<br><div style="font-size: 10px;">(Id: {$i['cuenta']['id_cuenta']})</div></td>
							<td class="identificacion">{$i['deudor']['identificacion']}</td>
							<td class="nombre">{implode_not_empty what=array({$i['deudor']['primer_nombre']},{$i['deudor']['segundo_nombre']},{$i['deudor']['primer_apellido']},{$i['deudor']['segundo_apellido']})}</td>
							<td class="udn">{$i['udn']['udn']}<br>({$i['campana']['campana']})<br>{$i['proceso']['descripcion']}</td>
							<!--<td class="fecha_creacion">{$i['cuenta']['fecha_creacion']|date_format:"%d/%m/%Y"}</td>-->
							<td class="valor_original">$ {$i['cuenta']['valor_original']}</td>
							<td class="valor_pagado">$ {$ultimopago}</td>
							<td class="valor_actual">$ {$i['cuenta']['valor_actual']}</td>
							<!--<td class="prcnt_pagado" style="white-space: nowrap !important;">{(100-($i['cuenta']['valor_actual']*100/$i['cuenta']['valor_original']))|string_format:"%.2f"} %</td>-->
						</tr>
					{/if}
				{/foreach}
			</tbody>
			<tfoot>
				<tr style="color:blue;">
					<td colspan="7" style="text-align:right;font-size:100%;"><b>Tot. a Cobrar:</b></td>
					<td style="font-size:100%;"><b>$ {$total}</b></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
