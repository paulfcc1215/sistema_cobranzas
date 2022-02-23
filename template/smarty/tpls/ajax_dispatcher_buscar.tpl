{if $records|@count == 0}
	<h2>No hay registros con la búsqueda indicada {$query}</h2>
{else}
	<b>{$records|@count} registro{$records|plural} encontrado{$records|plural}</b>
	<br>
	<div class="accordion" id="accordionParent">
	{foreach from=$records item=$r key=$k name=fe1}
	  <div class="card">
		<div class="card-header" id="heading{$smarty.foreach.fe1.iteration}">
		  <h2 class="mb-0">
			<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse{$smarty.foreach.fe1.iteration}" aria-expanded="true" aria-controls="collapse{$smarty.foreach.fe1.iteration}">
			  {$k}
			</button>
		  </h2>
		</div>
		<div id="collapse{$smarty.foreach.fe1.iteration}" class="collapse" aria-labelledby="heading{$smarty.foreach.fe1.iteration}" data-parent="#accordionParent">
		  <div class="card-body">
			<table class="table table-striped">
			<tr>
				<th>#</th>
				<th>UDN</th>
				<th>Proceso</th>
				<th>Cuenta</th>
				<th>Identificacion</th>
				<th>Nombres</th>
				<th>Valor Actual (Valor Pagado)</th>
			</tr>
			{foreach from=$r item=$i name=fe2}
				<!-- <tr class="clickable" onclick="window.open('?id_cuenta={$i['id_cuenta']}&user_name={$user_name}')"> -->
				<tr class="clickable" onclick="window.location='?id_cuenta={$i['id_cuenta']}&user_name={$user_name}'">
					<td>{$smarty.foreach.fe2.iteration}</td>
					<td>{$i['udn']}<br><div style="font-size: 12px;">({$i['campana']})</div></td>
					<td>{$i['id_proceso']} - {$i['proceso_descripcion']}</td>
					<td>{$i['cuenta']}<br><div style="font-size: 10px;">(Id: {$i['id_cuenta']})</div></td>
					<td>{$i['identificacion']}</td>
					<td>{$i['primer_nombre']} {$i['segundo_nombre']} {$i['primer_apellido']} {$i['segundo_apellido']}</td>
					<td>USD {$i['valor_actual']} ({(100-((($i['valor_actual'])*100)/$i['valor_original']))|round:2} %)</td>
				</tr>
			{/foreach}
			</table>
		  </div>
		</div>
	  </div>
	{/foreach}
	</div>	
{/if}