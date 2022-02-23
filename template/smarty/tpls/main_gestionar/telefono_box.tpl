<style>
.sin_gestion {
}

.gestionado {
	background-color: #FFFFAA;
}

.gestion_primera_persona {
background-color: #D47FFF;
}

.gestion_tercera_persona {
	background-color: #FFAAD4;
}

.gestion_promesa {
	background-color: #AAFF7F;
}

</style>
<div class="card">
	<div class="card-header">
		<b>Teléfonos</b>
	</div>
	<table>
		<tr>
			<td>
				<div class="card-body" style="min-height: 350px; max-height: 350px; overflow-y: auto;" id="id_telefono_box">
					<b>Gestionados:</b>
					{include file="main_gestionar/telefono_table.tpl" with_cuenta=$main_cuenta}
				</div>
			</td>
			<td>
				<div class="card-body" style="min-height: 350px; max-height: 350px; overflow-y: auto;" id="id_telefono_box">
					<b>NO Gestionados:</b>
					{include file="main_gestionar/telefono_table_sg.tpl" with_cuenta=$main_cuenta}
				</div>
			</td>
		</tr>
	</table>
</div>