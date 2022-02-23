<table class="table table-small">
	<thead>
		<tr>
			<th>Pertenece a</th>
			<th>Persona</th>
			<th>Teléfono</th>
			<th>Fuente</th>
		</tr>
	</thead>

	<tbody>
		<!--{fjjf_box_telefonos data=$with_cuenta['telefonos']}-->
		{* {var_dump($with_cuenta['telefonos'])} *}
		{fjjf_box_telefonos data=$with_cuenta['telefonos']}
	</tbody>
</table>
