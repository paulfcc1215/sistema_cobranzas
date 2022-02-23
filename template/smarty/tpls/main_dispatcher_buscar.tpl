{assign var="top_elements" value=array(
	''
)}

{assign var="footer_elements" value=array(
	"<script src=\"template/smarty/tpls/dispatcher_buscar.js\"></script>"
)}

{include file="common/header.tpl" top_elements=$top_elements}

<script>
	usuario="{$smarty.get.user_name}";
</script>
<div class="container">
	<div class="card">
		<div class="card-header">
			<b>Búsqueda - Gestiones</b>
		</div>
		<div class="card-body">
			<div class="btn-group btn-group-toggle" data-toggle="buttons">	
				<label class="btn btn-primary{($buscar_por=='cedula')?' active':''}">
					<input type="radio" name="buscar_por" value="cedula" id="option1" autocomplete="off"{($buscar_por=='cedula')?' checked':''}> Cédula
				</label>
				<label class="btn btn-primary{($buscar_por=='cuenta')?' active':''}">
					<input type="radio" name="buscar_por" value="cuenta" id="option2" autocomplete="off"{($buscar_por=='cuenta')?' checked':''}> Cuenta
				</label>
				<label class="btn btn-primary{($buscar_por=='id_cuenta')?' active':''}">
					<input type="radio" name="buscar_por" value="id_cuenta" id="option3" autocomplete="off"{($buscar_por=='id_cuenta')?' checked':''}> Id Cuenta
				</label>
				<label class="btn btn-primary{($buscar_por=='nombres')?' active':''}">
					<input type="radio" name="buscar_por" value="nombres" id="option4" autocomplete="off"{($buscar_por=='nombres')?' checked':''}> Nombre / Apellido
				</label>
				<label class="btn btn-primary{($buscar_por=='telefono')?' active':''}">
					<input type="radio" name="buscar_por" value="telefono" id="option5" autocomplete="off"{($buscar_por=='telefono')?' checked':''}> Teléfono
				</label>
			</div>

			<div id="error" style="padding: 0px !important; margin: 12px 0px 0px 0px !important; display: none;">
				<div class="alert alert-danger" role="alert" id="error_text"></div>
			</div>

			<p class="card-text">
				<div class="form-group">
					<input type="text" class="form-control" placeholder="Ingrese datos..." name="q" id="terms">
				</div><hr>
				<button type="button" class="btn btn-primary" onclick="buscar_cuenta(this)">Buscar</button>
			</p>
		</div>
	</div>
</div>
<div class="container" style="margin-top: 30px;">
	<div class="card">
		<div class="card-body">
			<div id="ajax_container"></div>
		</div>
	</div>
</div>

{include file="common/footer.tpl" footer_elements=$footer_elements}