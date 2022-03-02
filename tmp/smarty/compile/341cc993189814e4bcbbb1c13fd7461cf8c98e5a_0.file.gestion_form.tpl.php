<?php
/* Smarty version 3.1.33, created on 2022-02-24 13:50:01
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/gestion_form.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6217d359e17e55_52990221',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '341cc993189814e4bcbbb1c13fd7461cf8c98e5a' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/gestion_form.tpl',
      1 => 1645728598,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6217d359e17e55_52990221 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.fjjf_call_closure.php','function'=>'smarty_function_fjjf_call_closure',),));
echo '<script'; ?>
>
ajaxBusy=false;
gestionesToSave=[];
var cuentas=new Array();

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
		"telh_id":$("#form_id_llamada").val(),
		"servidor":"<?php echo $_smarty_tpl->tpl_vars['internal_servidor']->value;?>
",
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
console.log(data['id_cuentas']);
//return false;
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

function selectedAllGestion(me){
	gestionesToSave=new Array();
	if ($(me).is(':checked')){
		$.each($('#tbl_gestiones tbody tr input'),function(i,o){
			$(o).attr('checked','checked');
		})
		for(var i in cuentas) {
			gestionesToSave.push($(cuentas[i]).data("id-cuenta").toString());
		}
	}else{
		$.each($('#tbl_gestiones tbody tr input'),function(i,o){
			$(o).removeAttr('checked');
		})
	}
	console.log(gestionesToSave);
}

function guardarGestion(btn) {
	if(!confirm("Está seguro que desea guardar?"))
		return false;
	$(btn).prop("disabled",1);
	$(btn).addClass("disabled");

	var allowMultipleCuentas=true;

	// validamos si hay mas de una cuenta
	cuentas = new Array();
	
	$("#table-cuentas tbody tr").each(function(k,o) {
		cuentas.push(o);
	});
	
	gestionesToSave=new Array();
	
	if(allowMultipleCuentas && cuentas.length>1) {
		var html="Por favor seleccione las cuentas en las que desea aplicar la gestión:<br><br><input type='checkbox' id='seleccionarTodos' onchange='selectedAllGestion($(this))' checked>Todas"
		+"<table class='table table-small' id='tbl_gestiones'>"
		+"<thead>"
		+"<tr>"
		+"<th></th>"
		+"<th>#</th>"
		+"<th>Cuenta</th>"
		+"<th>Identificacion</th>"
		+"<th>Nombre</th>"
		+"<th>Cedente</th>"
		+"<th>Valor Original</th>"
		+"<th>Valor Actual</th>"
		+"<th>% Pagado</th>"
		+"</tr>"
		+"</thead>"
		+"<tbody>";
		for(var i in cuentas) {

			gestionesToSave.push($(cuentas[i]).data("id-cuenta").toString());

			html=html+"<tr>";
			var checked=true;
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
		gestionesToSave.push(cuentaSeleccionada.toString());	
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

<?php echo '</script'; ?>
>
<img src="template/assets/spinner5.gif" style="display: none;" />
<div class="form-gestionar">
<table class="table table-small" id="gestion-form-content">
<thead>
	<tr style="background-color: #7FD4FF;">
		<td>IdLlamada</td>
		<td>Teléfono</td>
		<td>Email</td>
		<td>Tipificación</td>
		<?php if ($_smarty_tpl->tpl_vars['hooks']->value['smarty_gestion_form_after_tipificacion_th'] != '') {?>
			<?php echo smarty_function_fjjf_call_closure(array('fn'=>$_smarty_tpl->tpl_vars['hooks']->value['smarty_gestion_form_after_tipificacion_th']),$_smarty_tpl);?>

		<?php }?>
		<td>Fecha Promesa</td>
		<td>Monto Promesa</td>
		<td>Observaciones</td>
		<td>&nbsp;</td>
	</tr>
</thead>
<tbody>
	<tr>
		<!-- Id Llamada -->
		<td><input type="text" id="form_id_llamada" value="<?php echo $_GET['telh_id'];?>
"></td>
		<!-- Telefono -->
		<td><input type="text" id="form_telefono" value="<?php echo $_GET['tel_number'];?>
"></td>
		<!-- Email -->
		<td><input type="text" id="form_email" value="<?php echo $_GET['email'];?>
"></td>
		<!-- Tipificacion -->
		<td>
			<select id="form_tipificacion" onchange="change_tipificacion()">
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
		<?php if ($_smarty_tpl->tpl_vars['hooks']->value['smarty_gestion_form_after_tipificacion_td'] != '') {?>
			<?php echo smarty_function_fjjf_call_closure(array('fn'=>$_smarty_tpl->tpl_vars['hooks']->value['smarty_gestion_form_after_tipificacion_td']),$_smarty_tpl);?>

		<?php }?>
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
</div><?php }
}
