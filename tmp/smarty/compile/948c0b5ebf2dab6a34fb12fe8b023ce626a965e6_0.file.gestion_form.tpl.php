<?php
/* Smarty version 3.1.33, created on 2019-10-07 12:19:20
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/gestion_form.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9b7398cc7e04_81220445',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '948c0b5ebf2dab6a34fb12fe8b023ce626a965e6' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/gestion_form.tpl',
      1 => 1570468759,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d9b7398cc7e04_81220445 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
>
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
		"fecha_inicio":"<?php echo $_smarty_tpl->tpl_vars['internal_fecha_inicio']->value;?>
",
		"user_name":"<?php echo $_smarty_tpl->tpl_vars['internal_user_name']->value;?>
",
		"telh_id":"<?php echo $_smarty_tpl->tpl_vars['internal_telh_id']->value;?>
",
		"servidor":"<?php echo $_smarty_tpl->tpl_vars['internal_servidor']->value;?>
",
		"id_cuentas":gestionesToSave
	};
	
	$("#gestion-form-content input").add("#gestion-form-content select").add("#gestion-form-content textarea").each(function(k,o) {
		if($(o).prop("id").length==0) {
			console.log("WARNING - No id for element",o);
			return;
		
		}
		var name=($(o).prop("id")).substring(5);
		
		if(o.tagName=="INPUT") {
			if(o.type == "text") {
				data[name]=$(o).val();
			}
		}else if(o.tagName=="SELECT") {
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
	$(btn).prop("disabled",1);
	$(btn).addClass("disabled");

	var allowMultipleCuentas=true;

	// validamos si hay mas de una cuenta
	var cuentas=new Array();
	
	$("#table-cuentas tbody tr").each(function(k,o) {
		cuentas.push(o);
	});
	console.log(cuentas);
	
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

	}
	
	
}
<?php echo '</script'; ?>
>
<img src="template/assets/spinner5.gif" style="display: none;" />
<div class="form-gestionar">
<table class="table table-small" id="gestion-form-content">
<thead>
	<tr style="background-color: #7FD4FF;">
		<td>Teléfono</td>
		<td>Tipificación</td>
		<td>Fecha Promesa</td>
		<td>Monto Promesa</td>
		<td>Observaciones</td>
		<td>&nbsp;</td>
	</tr>
</thead>
<tbody>
	<tr>
		<!-- Telefono -->
		<td><input type="text" id="form_telefono" value="<?php echo $_GET['tel_number'];?>
"></td>
		<!-- Tipificacion -->
		<td>
			<select id="form_tipificacion">
			<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['main_cuenta']->value['tipificaciones'], 'i', false, 'k', 'it', array (
));
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['k']->value => $_smarty_tpl->tpl_vars['i']->value) {
?>
			<option value="<?php echo $_smarty_tpl->tpl_vars['i']->value['id_tipificacion'];?>
"><?php echo $_smarty_tpl->tpl_vars['i']->value['descripcion'];?>
</option>
			<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
			</select>
		</td>
		<!-- Fecha Promesa -->
		<td><input type="text" id="form_fecha_promesa" value="25/10/2019"></td>
		<!-- Monto Promesa -->
		<td><input type="text" id="form_monto_promesa" value="129.85"></td>
		<!-- Observaciones -->
		<td><input type="text" id="form_observaciones" placeholder="Observaciones..." value="Esta es una observacion"></td>
		<!-- Boton -->
		<td><button id="form_boton_guardar" class="btn btn-primary" onclick="guardarGestion(this)">Guardar</button></td>
		
	</tr>
</tbody>
</table>
</div><?php }
}
