{if $only_content neq true}
<div class="card">
	<div class="card-header">
		<b>Detalle Cuenta Seleccionada</b>
	</div>
	<div class="card-body" style="min-height: 200px; max-height: 250px; overflow-y: auto;" id="id_detalle_cuenta_box">
	{/if}
		<!--Cuenta: {$with_cuenta['cuenta']['cuenta']} perteneciente a {implode_not_empty what=array({$with_cuenta['deudor']['primer_nombre']},{$with_cuenta['deudor']['segundo_nombre']},{$with_cuenta['deudor']['primer_apellido']},{$with_cuenta['deudor']['segundo_apellido']})}.
		<br>
		Detalles provenientes de carga {$with_cuenta['data_no_mapeada']['carga']['id_carga']} hecha el {$with_cuenta['data_no_mapeada']['carga']['fecha_carga']|date_format:'%d/%m/%Y %H:%M:%S'}
		<br>-->
		<table>
			<tr>
				<td colspan="2">
					<b>Otros datos</b>
					{if $hooks['smarty_detalle_cuenta_box_pre_otros_datos'] != ''}
                        {fjjf_call_closure fn=$hooks['smarty_detalle_cuenta_box_pre_otros_datos']}
                    {else}
					<table border="1" class="tabla-detalle-cuenta-otros-datos">
						{foreach from=$with_cuenta['data_no_mapeada']['data'] key=k item=v name=fe}
						<tr{if $smarty.foreach.fe.iteration is even} class="even"{else} class="odd"{/if}>
							<th>{$k}</th>
							<td>{$v}</td>
						</tr>
						{/foreach}
					</table>
                    {/if}
				</td>
			</tr>
			<tr>
				<td>
					<b>Pagos</b>
					<table border="1" class="tabla-pagos" style="font-size:85%; font-family:verdana;text-align:right;">
						<thead>
							<tr>
								<th>Ord.</th>
								<th>Tipo</th>
								<th>Cantidad</th>
								<!--<th>Fecha de carga</th>-->
								<th>Fecha de Pago</th>
							</tr>
						</thead>
						<tbody >
							
							{assign var=fecha_carga_pago value=$with_cuenta['data_no_mapeada']['carga']['fecha_carga']}

							{* se muestra historial de pagos petición de Marco Pala 2021-09-29 *}
							{assign var=count value=1}
							{assign var=total value=0}
							{assign var=campana value=$with_cuenta['proceso']['id_campana']}
							{foreach from=$with_cuenta['actualizaciones'] item=v name=fp}
							{* se permite a campaña de movistar mostrar solo ultimo pago, pedido Marco Pala 29-09-2021 *}
								{if $campana==11}
									{if $v['tipo_actualizacion'] == 'PAGO' && $ultimo_pago!=$fecha_carga_pago}
										{assign var=ultimo_pago value=$fecha_carga_pago}
										{assign var=total value=$total+abs($v['diferencia'])}
										<tr>
											<td>{$count}</td>
											<td>{$v['tipo_actualizacion']}</td>
											<td >{number_format(floatval(abs($v['diferencia'])),2)}</td>
											<td>{$v['fecha_actualizacion']|date_format:"%d/%m/%Y"}</td>
											<!--<td>{$fecha_carga_pago|date_format:"%d/%m/%Y"}</td>-->
										</tr>
										{assign var=count value=$count+1}
									{/if}
								{else}
									{if $v['tipo_actualizacion'] == 'PAGO'}
										{assign var=ultimo_pago value=$fecha_carga_pago}
										{assign var=total value=$total+abs($v['diferencia'])}
										<tr>
											<td>{$count}</td>
											<td>{$v['tipo_actualizacion']}</td>
											<td >{number_format(floatval(abs($v['diferencia'])),2)}</td>
											<td>{$v['fecha_actualizacion']|date_format:"%d/%m/%Y"}</td>
											<!--<td>{$fecha_carga_pago|date_format:"%d/%m/%Y"}</td>-->
										</tr>
										{assign var=count value=$count+1}
									{/if}
								{/if}
							{/foreach}
							<tr>
								<td colspan="2"><b>Total Pagos:</b></td>
								<td ><b>{number_format(floatval($total),2)}</b></td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		{if $only_content neq true}
	</div>
</div>
{/if}