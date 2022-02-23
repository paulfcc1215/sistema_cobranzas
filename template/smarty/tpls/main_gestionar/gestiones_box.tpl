{if $only_content neq true}
<div class="card">
  <div class="card-header">
	<b>Gestión</b><!-- <button class="btn btn-primary">Gestionar</button> -->
  </div>
  <div class="card-body div-gestiones" id="id_gestiones_box">
  {include file="main_gestionar/gestion_form.tpl"}
 {/if}
  <div id="id_gestiones_box_content">
  <table class="table table-small table-gestiones">
  <thead>
	<tr>
		<th>Fecha</th>
		<th>Tipificación</th>
		<th>Teléfono</th>
		<th>Usuario</th>
		<th>Observación</th>
	</tr>
  </thead>
  <tbody>
  {foreach from=$with_cuenta['gestiones'] item=$i key=$k name=it}
  <tr class="{if $smarty.foreach.it.iteration is even}even{else}odd{/if}" title="Id gestion: {$i['id_gestion']} | Id Llamada: {$i['telh_id']}">
  
  <td>{$i['fecha_inicio']|date_format:"%d/%m/%Y %H:%M:%S"}</td>
  {if $i['fecha_compromiso'] != ''}
  <td>{$i['tipificacion']['descripcion']}<br><div class="detalle-compromiso">{$i['fecha_compromiso']} (${$i['monto_compromiso']})</div></td>
  {else}
  <td>{$i['tipificacion']['descripcion']}</td>
  {/if}
  <td>{$i['tel_number']}</td>
  <td>{$i['user_name']}</td>
  <td><textarea readonly="1" cols="40" style="width: 100%">{$i['observacion']}</textarea></td>
  </tr>
  {/foreach}
  </tbody>
  </table>
  </div>
{if $only_content neq true}  
  </div>
</div>
{/if}