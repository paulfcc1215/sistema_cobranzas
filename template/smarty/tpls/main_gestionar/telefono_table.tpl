<table class="table table-small">
	<thead>
		<tr style="background-color:#98D858;"><th colspan="5" style="text-align:center;">CONTACTADOS</th></tr>
		<tr>
			<th>Fecha Gestión</th>
			<th>Pertenece a</th>
			<th>Teléfono</th>
			<th>Tipificación</th>
			<th>Fuente</th>
		</tr>
	</thead>

	<tbody>
		<!--{fjjf_box_telefonos data=$with_cuenta['telefonos']}-->
		{box_telefonos_gestionados data=$with_cuenta['deudor']['telefonos_gestionados'] tipo='contacto'}
	</tbody>
</table>

<table class="table table-small">
	<thead>
		<tr style="background-color:#D85858;"><th colspan="5" style="text-align:center;">NO CONTACTADOS</th></tr>
		<tr>
			<th>Fecha Gestión</th>
			<th>Pertenece a</th>
			<th>Teléfono</th>
			<th>Tipificación</th>
		</tr>
	</thead>

	<tbody>
		<!--{fjjf_box_telefonos data=$with_cuenta['telefonos']}-->
		{box_telefonos_gestionados data=$with_cuenta['deudor']['telefonos_gestionados'] tipo='no_contacto'}
	</tbody>
</table>

<!--<table class="table table-small">
  <thead>
	  <tr>
		<th>Pertenece A</th>
		<th>Teléfono</th>
		<th>Mejor Gestión</th>
		<th>Fuente</th>
	  </tr>
  </thead>
  
  <tbody>
	{fjjf_box_telefonos data=$with_cuenta['telefonos']}
  </tbody>
</table>-->