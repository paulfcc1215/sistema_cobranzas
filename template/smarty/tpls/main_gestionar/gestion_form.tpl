<script>
ajaxBusy=false;
gestionesToSave=[];

function updateSelectedGestionForSave(cb) {
	var checked=$(cb).prop("checked");
	var aux=[];
	for(var i in gestionesToSave) {
		if(gestionesToSave[i]==cb.value) continue;
		aux.push(gestionesToSave[i]);
	}
	if(checked) {
		aux.push(cb.value);
	}
	gestionesToSave=aux;

}

function guardarGestionAjax() {
	if(ajaxBusy) {
		alert("Hay una operación pendiente. Por favor inténtelo más tarde");
		return;
	}
	var btn=$("#gestion-form-content button#form_boton_guardar");
	ajaxBusy=true;
	btn.prop("disabled",true);
	btn.addClass("disabled");
	btn.html('<img src="template/assets/spinner5.gif" width="24"/> Guardando...');
	
	var data={
		"fecha_inicio":"{$internal_fecha_inicio}",
		"user_name":"{$internal_user_name}",
		"telh_id":$("#form_id_llamada").val(),
		"servidor":"{$internal_servidor}",
		"id_cuentas":gestionesToSave
	};
	
	$("#gestion-form-content input").add("#gestion-form-content select").add("#gestion-form-content textarea").each(function(k,o) {
		if($(o).prop("id").length==0) {
			console.log("WARNING - No id for element",o);
			return;
		}
		// the id prop for custom fields should begin with "form_custom_"
		var name=($(o).prop("id")).substring(5);
		
		if(o.tagName=="INPUT") {
			if(o.type == "text") {
				data[name]=$(o).val();
			}
		}else if(o.tagName=="SELECT") {
			data[name]=$(o).val();
		}else if(o.tagName=="TEXTAREA") {
			data[name]=$(o).val();
		}
	});

	$.ajax({
		"url":"ajax.php?a=storeGestion",
		"method":"POST",
		"data":data,
		"success":function(e) {
			try {
				e=$.parseJSON(e);
				if(!e.success) throw e.error;
                alert("Se ha almacenado la gestión satisfactoriamente");
                $("#gestion-form-content input").each(function(k,o) {
                    $(o).val("");
                });
                $("#gestion-form-content textarea").each(function(k,o) {
                    $(o).val("");
                });

				updateCuentaData(cuentaSeleccionada);
			}catch(err){
				var content='<div class="alert alert-danger" role="alert">'
				+err
				+'</div>';
				setTimeout(function() {
					showModal("<div style='color: red;'>Error al guardar gestión</div>",content);
				},500);
				
				
			}
			btn.html('Guardar');
			btn.removeClass("disabled");
			btn.prop("disabled",false);
			ajaxBusy=false;
		}
	});

}

function cancelGuardarGestion(btn) {
	$(btn).parents(".modal").modal("hide");
	var btn2=$("#gestion-form-content button#form_boton_guardar");
	btn2.removeClass("disabled");
	btn2.prop("disabled",false);
	gestionesToSave=new Array();
	
}

function guardarGestionFromModal(btn) {
	$(btn).parents(".modal").modal("hide");
	guardarGestionAjax();

}

function guardarGestion(btn) {
	if(!confirm("Está seguro que desea guardar?"))
		return false;
	$(btn).prop("disabled",1);
	$(btn).addClass("disabled");

	var allowMultipleCuentas=true;

	// validamos si hay mas de una cuenta
	var cuentas=new Array();
	
	$("#table-cuentas tbody tr").each(function(k,o) {
		cuentas.push(o);
	});
	
	gestionesToSave=new Array();
	gestionesToSave.push(cuentaSeleccionada.toString());
	if(allowMultipleCuentas && cuentas.length>1) {
		var html="Por favor seleccione las cuentas en las que desea aplicar la gestión:<br><br>"
		+"<table class='table table-small'>"
		+"<thead>"
		+"<tr>"
		+"<th>#</th>"
		+"<th>Cuenta</th>"
		+"<th>Identificacion</th>"
		+"<th>Nombre</th>"
		+"<th>Cedente</th>"
		+"<th>Fecha Cuenta</th>"
		+"<th>Valor Original</th>"
		+"<th>Valor Actual</th>"
		+"<th>% Pagado</th>"
		+"</tr>"
		+"</thead>"
		+"<tbody>";
		for(var i in cuentas) {
			html=html+"<tr>";
			var checked=false;
			if($(cuentas[i]).data("id-cuenta")==cuentaSeleccionada) {
				checked=true;
			}
			
			html=html+"<td><input type='checkbox' class='storeGestionCuenta' value='"+$(cuentas[i]).data("id-cuenta")+"' "+(checked?" checked='checked'":"")+" onchange='updateSelectedGestionForSave(this)'></td>";
			html=html+cuentas[i].innerHTML;
			html=html+"</tr>";
		}
		html=html+"</tbody>";
		html=html+"</table>";

		showModal("Seleccione Cuentas",html,{
			"allowClose":false,
			"buttons":[
				{
					"class":"btn btn-primary",
					"action":guardarGestionFromModal,
					"label":"Guardar"
				},
				{
					"class":"btn btn-danger",
					"action":cancelGuardarGestion,
					"label":"Cancelar"
				}
			]
		});
	}else{
		
		guardarGestionAjax();
		
	}
	
}

function change_tipificacion(){
	//si la tipificacion es 17=>email no se aplica id_llamada
	if($("#form_tipificacion").val()==17){
		$("#form_id_llamada").val('');
		$("#form_id_llamada").attr("disabled","disabled");
	}else{
	$("#form_id_llamada").removeAttr("disabled");
	}
}

</script>
<img src="template/assets/spinner5.gif" style="display: none;" />
<div class="form-gestionar">
<table class="table table-small" id="gestion-form-content">
<thead>
	<tr style="background-color: #7FD4FF;">
		<td>IdLlamada</td>
		<td>Teléfono</td>
		<td>Email</td>
		<td>Tipificación</td>
		{if $hooks['smarty_gestion_form_after_tipificacion_th'] != ''}
			{fjjf_call_closure fn=$hooks['smarty_gestion_form_after_tipificacion_th']}
		{/if}
		<td>Fecha Promesa</td>
		<td>Monto Promesa</td>
		<td>Observaciones</td>
		<td>&nbsp;</td>
	</tr>
</thead>
<tbody>
	<tr>
		<!-- Id Llamada -->
		<td><input type="text" id="form_id_llamada" value="{$smarty.get.telh_id}"></td>
		<!-- Telefono -->
		<td><input type="text" id="form_telefono" value="{$smarty.get.tel_number}"></td>
		<!-- Email -->
		<td><input type="text" id="form_email" value="{$smarty.get.email}"></td>
		<!-- Tipificacion -->
		<td>
			<select id="form_tipificacion" onchange="change_tipificacion()">
			{foreach from=$main_cuenta['tipificaciones'] item=$i key=$k name=it}
			<option value="{$i['id_tipificacion']}">{$i['descripcion']}</option>
			{/foreach}
			</select>
		</td>
		{if $hooks['smarty_gestion_form_after_tipificacion_td'] != ''}
			{fjjf_call_closure fn=$hooks['smarty_gestion_form_after_tipificacion_td']}
		{/if}
		<!-- Fecha Promesa -->
		<td><input type="text" class="fecha" id="form_fecha_promesa" value=""></td>
		<!-- Monto Promesa -->
		<td><input type="text" id="form_monto_promesa" value=""></td>
		<!-- Observaciones -->
		<!--<td><input type="text" id="form_observaciones" placeholder="Observaciones..." value=""></td>-->
		<td><textarea id="form_observaciones" style="width: 100%" placeholder="Observaciones..."></textarea></td>
		<!-- Boton -->
		<td><button id="form_boton_guardar" class="btn btn-primary btn-sm" onclick="guardarGestion(this)">Guardar</button></td>
		
	</tr>
</tbody>
</table>
</div>