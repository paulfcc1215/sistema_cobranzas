<?php
/* Smarty version 3.1.33, created on 2022-01-18 17:24:17
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/main.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_61e73e11d056a4_58234277',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '4a2663cafb2cb096b351d1e1cd089954206ccfae' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/main.tpl',
      1 => 1642544656,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:common/header.tpl' => 1,
    'file:common/modal.tpl' => 1,
    'file:common/toast.tpl' => 1,
    'file:main_gestionar/navbar.tpl' => 1,
    'file:main_gestionar/cliente_box.tpl' => 1,
    'file:main_gestionar/detalle_cuenta_box.tpl' => 1,
    'file:main_gestionar/gestiones_box.tpl' => 1,
    'file:main_gestionar/telefono_box.tpl' => 1,
    'file:main_gestionar/telefono_detalle_box.tpl' => 1,
    'file:common/footer.tpl' => 1,
  ),
),false)) {
function content_61e73e11d056a4_58234277 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('main_cuenta', $_smarty_tpl->tpl_vars['data']->value['cuentas'][$_smarty_tpl->tpl_vars['id_cuenta_seleccionada']->value]);
$_smarty_tpl->_subTemplateRender('file:common/header.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('top_elements'=>array('<link rel="stylesheet" href="template/smarty/tpls/gestionar.css" crossorigin="anonymous">','<link href="template/assets/bs3/css/bootstrap-datepicker3.min.css" rel="stylesheet">')), 0, false);
?>

<?php $_smarty_tpl->_subTemplateRender("file:common/modal.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_id'=>"modal"), 0, false);
$_smarty_tpl->_subTemplateRender("file:common/toast.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_id'=>"modal"), 0, false);
?>


<?php echo '<script'; ?>
>
var cuentaSeleccionada=<?php echo $_smarty_tpl->tpl_vars['id_cuenta_seleccionada']->value;?>
;

function ajax_agregarTelefono() {
	var pertenece_a=$("#id_pertenece_a");
	var ubicacion_telefono=$("#id_ubicacion_telefono");
	var telefono=$("#id_telefono");
	var error=$("#agregarTelefonoModalError");
	try {
		if(telefono.val()=="") {
			throw ("Debe indicar n??mero telef??nico");
		}
		$.ajax({
			"url":"ajax.php?a=addTelefono",
			"method":"POST",
			"data":{
				"telefono":telefono.val(),
				"pertenece_a":pertenece_a.val(),
				"ubicacion_telefono":ubicacion_telefono.val(),
				"cuenta":cuentaSeleccionada
			},
			"success":function(e) {
				try {
					e=$.parseJSON(e);
					if(!e.success) throw e.error;
					updateCuentaData(cuentaSeleccionada);
					$(".modal").modal("hide");
				}catch(err){
					error.html(err);
				}
			}
		});
	}catch(err){
		error.html(err);
	}
}

function agregarTelefonoModal() {
	var content = '<div id="agregarTelefonoModalError" style="color: maroon; font-weight: bold;"></div><br>';
	content += '<table style="width:100%;"><tr>';
	content += '<td style="text-align:right;">Pertenece a:</td><td><select class="form-control" id="id_pertenece_a"><option value="DEUDOR">DEUDOR</option><option value="GARANTE">GARANTE</option><select/></td>';
	content += '<td style="text-align:right;">Ubicaci??n Tel??fono:</td><td><select class="form-control" id="id_ubicacion_telefono"><option value="RESIDENCIA">RESIDENCIA</option><option value="TRABAJO">TRABAJO</option><select/></td>';
	content += '<td style="text-align:right;">N??mero Tel??fono:</td><td><input type="text" maxlength="10" id="id_telefono" class="form-control" /></td>';
	content += '</tr></table>';
	var params={
		"buttons":[
			{
				"class":"btn btn-primary",
				"action":ajax_agregarTelefono,
				"label":"Guardar"
			},
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cancelar"
			}
		]
	};
	showModal("Agregar Tel??fonos",content,params);
}

function ajax_agregarDireccion() {
	
	var error=$("#agregarDireccionModalError");
	try {
		$.ajax({
			"url":"ajax.php?a=addDireccion",
			"method":"POST",
			"data":{
				"tipo_direccion":$('#tipo_direccion').val(),
				"id_parroquia":$('#id_parroquia').val(),
				"calle_principal":$('#id_calle_principal').val(),
				"calle_secundaria":$('#id_calle_secundaria').val(),
				"numeracion":$('#id_numeracion').val(),
				"referencia":$('#id_referencia').val(),
				"latitud":$('#id_latitud').val(),
				"longitud":$('#id_longitud').val(),
				"cuenta":cuentaSeleccionada
			},
			"success":function(e) {
				try {
					e=$.parseJSON(e);
					if(!e.success) throw e.error;
					$(".modal").modal("hide");
				}catch(err){
					error.html(err);
				}
			}
		});
	}catch(err){
		error.html(err);
	}
}

function agregarDireccionModal() {

	var script = document.createElement('script');
	script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyCcAIM1m0ReHC8DS7dslCfAdxTgp1Na_QA&callback=initMap&libraries=&v=weekly';
	script.async = true;

	window.initMap = function() {
		const map = new google.maps.Map(document.getElementById("map"), {
			zoom: 17,
			center: { lat: -0.1801058809690268, lng: -78.48938336063523 },
		});
		map.addListener('click', (e) =>{
			const marker = new google.maps.Marker({
				position: e.latLng,
				map: map
			});
			map.setZoom(17);
			map.panTo(e.latLng);
			$("#id_latitud").val(e.latLng.toJSON().lat);
			$("#id_longitud").val(e.latLng.toJSON().lng);
		});
		const geocoder = new google.maps.Geocoder();
		document.getElementById("btn_geolocalizar").addEventListener("click", () => {
			geocodeAddress(geocoder, map);
		});
	};

	document.head.appendChild(script);

	var catalogo_ubicacion = <?php echo json_encode($_smarty_tpl->tpl_vars['catalogo_ubicacion']->value);?>
;
	var content = "<div id='agregarDireccionModalError' style='color: maroon; font-weight: bold;'></div><br>";
	content += '<input type="hidden" id="id_latitud" /><input type="hidden" id="id_longitud" />';
	content += '<table style="width:100%;"><tr><td>';
	content += 'Tipo Direcci??n:<select id="tipo_direccion" class="form-control"><option value="">Seleccione...</option><option value="RESIDENCIA">RESIDENCIA</option><option value="TRABAJO">TRABAJO</option><option value="OTROS">OTROS</option></select></td></tr>';
	content += '<tr><td>Provincia:<select id="id_provincia" class="form-control" onchange="cargar_cantones()"><option value="">Seleccione...</option>';
	$.each(catalogo_ubicacion,function(i,o){
		content += '<option value="'+i+'">'+o+'</option>';
	})
	content += '</select></td>';
	content += '<td>Canton: <select id="id_canton" class="form-control" onchange="cargar_parroquias()"><option value="">Seleccione...</option></select></td>';
	content += '<td>Parroquia: <select id="id_parroquia" class="form-control"><option value="">Seleccione...</option></select></td></tr>';
	content += '<tr><td>Calle principal: <input type="text" id="id_calle_principal" class="form-control"/></td>';
	content += '<td>Calle secundaria: <input type="text" id="id_calle_secundaria" class="form-control"/></td>';
	content += '<td>Numeraci??n: <input type="text" id="id_numeracion" class="form-control"/></td></tr>';
	content += '<tr><td colspan="3">Referencia: <input type="text" id="id_referencia" class="form-control"/></td></tr>';
	content += '<tr><td colspan="3"><input id="btn_geolocalizar" class="btn btn-success" type="button" value="GeoLocalizar" /></td></tr>';
	content += '<tr><td colspan="3"><div id="map" style="width:100%; height:550px; border:1px solid blue;"></div></td></tr></table>';
	var params={
		"buttons":[
			{
				"class":"btn btn-primary",
				"action":ajax_agregarDireccion,
				"label":"Guardar"
			},
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cancelar"
			}
		]
	};
	showModal("Agregar Direcci??n",content,params);
}

function geocodeAddress(geocoder, resultsMap) {
    var provincia = document.getElementById("id_provincia");
    provincia = provincia.selectedOptions.item(0).innerText;
    var canton = document.getElementById("id_canton");
    canton = canton.selectedOptions.item(0).innerText;
    var parroquia = document.getElementById("id_parroquia");
    parroquia = parroquia.selectedOptions.item(0).innerText;
    var calle_principal = document.getElementById("id_calle_principal").value;
    var calle_secundaria = document.getElementById("id_calle_secundaria").value;
    var numeracion = document.getElementById("id_numeracion").value;
    if (provincia=='Seleccione...') {
        $.notify("Seleccione provincia: ","warn");
        return false;
    }
    if (canton=='Seleccione...') {
        $.notify("Seleccione cant??n: ","warn");
        return false;
    }
    if (parroquia=='Seleccione...') {
        $.notify("Seleccione parroquia: ","warn");
        return false;
    }
    if (calle_principal=='') {
        $.notify("Ingrese calle principal: ","warn");
        return false;
    }
    if (calle_secundaria=='') {
        $.notify("Ingrese calle secundaria: ","warn");
        return false;
    }

    const address = 'Ecuador, '+provincia+', '+canton+', '+parroquia+', '+calle_principal+' y '+calle_secundaria+' '+numeracion;
    geocoder.geocode({ address: address }, (results, status) => {
        if (status === "OK") {
            resultsMap.setZoom(17);
            resultsMap.panTo(results[0].geometry.location);
            const marker = new google.maps.Marker({
                map: resultsMap,
                position: results[0].geometry.location,
            });
            $("#id_latitud").val(results[0].geometry.location.toJSON().lat);
            $("#id_longitud").val(results[0].geometry.location.toJSON().lng);
        } else {
            $.notify("No se ha logrado localizar la direccion ingresada: ","error");
        }
    });
}

function cargar_parroquias(){
	$('#id_parroquia').empty();
	$('#id_parroquia').append('<option value="">Seleccione...</option>');
	if ($('#id_canton').val()=='') return false;
	$.ajax({
		"url":"ajax.php?a=getParroquiasByIdCanton",
		"method":"POST",
		"data":{
			"id_canton":$('#id_canton').val()
		},
		"success":function(e) {
			try {
				e=$.parseJSON(e);
				if(!e.success) throw e.error;
				$('#id_parroquia').append(e.data);
			}catch(err){
				error.html(err);
			}
		}
	});
}

function cargar_cantones(){
	$('#id_canton').empty();
	$('#id_canton').append('<option value="">Seleccione...</option>');
	$('#id_parroquia').empty();
	$('#id_parroquia').append('<option value="">Seleccione...</option>');
	if ($('#id_provincia').val()=='') return false;
	$.ajax({
		"url":"ajax.php?a=getCantonesByIdProvincia",
		"method":"POST",
		"data":{
			"id_provincia":$('#id_provincia').val()
		},
		"success":function(e) {
			try {
				e=$.parseJSON(e);
				if(!e.success) throw e.error;
				$('#id_canton').append(e.data);
			}catch(err){
				error.html(err);
			}
		}
	});
}

function MostrarGarantias() {
	var params={
		"buttons":[
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cerrar"
			}
		]
	};
	$.ajax({
		"url":"ajax.php?a=getGarantias",
		"method":"POST",
		"data":{
			"id_cuenta":cuentaSeleccionada
		},
		"success":function(e) {
			try {
				e=$.parseJSON(e);
				if(!e.success) throw e.error;
				showModal("Garant??as",e.data,params);
			}catch(err){
				error.html(err);
			}
		}
	});
}

function MostrarScriptModal() {
	var params={
		"buttons":[
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cerrar"
			}
		]
	};
	$.ajax({
		"url":"ajax.php?a=getScript",
		"method":"POST",
		"data":{
			"id_cuenta":cuentaSeleccionada
		},
		"success":function(e) {
			try {
				e=$.parseJSON(e);
				if(!e.success) throw e.error;
				showModal("Gui??n de Agente",e.data,params);
			}catch(err){
				error.html(err);
			}
		}
	});
}

function MostrarHistoricoGestion() {
	var params={
		"buttons":[
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cerrar"
			}
		]
	};
	$.ajax({
		"url":"ajax.php?a=getHistoricoGestion",
		"method":"POST",
		"data":{
			"id_cuenta":cuentaSeleccionada
		},
		"success":function(e) {
			try {
				e=$.parseJSON(e);
				if(!e.success) throw e.error;
				showModal("Historico de Gestion",e.data,params);
			}catch(err){
				error.html(err);
			}
		}
	});
}

function MostrarDirecciones() {
	var params={
		"buttons":[
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cerrar"
			}
		]
	};
	$.ajax({
		"url":"ajax.php?a=getDireccionesByIdentificacion",
		"method":"POST",
		"data":{
			"id_cuenta":cuentaSeleccionada
		},
		"success":function(e) {
			try {
				e=$.parseJSON(e);
				if(!e.success) throw e.error;
				showModal("Direcciones",e.data,params);
			}catch(err){
				error.html(err);
			}
		}
	});
}

<?php echo '</script'; ?>
>

<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
	<a class="navbar-brand" href="#">Ori??n - Gesti??n de Cobranzas</a>
	<!--
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
	<span class="navbar-toggler-icon"></span>
	</button>
	-->
	<div class="collapse navbar-collapse" id="navbarsExampleDefault">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/navbar.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
	</div>
</nav>

<main role="main" class="container-fluid" style="margin-top: 80px;">

	<div class="row">
		<div class="col-8">
			<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/cliente_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
		</div>
		<div class="col-4">
			<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/detalle_cuenta_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_cuenta'=>$_smarty_tpl->tpl_vars['main_cuenta']->value), 0, false);
?>
		</div>
	</div>

	<div class="row" style="margin-top: 20px;">
		<div class="col-12">
			<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/gestiones_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_cuenta'=>$_smarty_tpl->tpl_vars['main_cuenta']->value), 0, false);
?>
		</div>  
	</div>

	<div class="row" style="margin-top: 20px;">
		<div class="col-12">
			<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/telefono_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
		</div>
		<!--<div class="col-5">
			<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/telefono_detalle_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
		</div>-->
	</div>

</main>

<div style="height: 200px;"></div>
<?php $_smarty_tpl->_subTemplateRender('file:common/footer.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('footer_elements'=>array('<script src="template/assets/jquery.mask.min.js"></script>','<script src="template/assets/bs3/js/bootstrap-datepicker.min.js"></script>','<script src="template/assets/bs3/js/bootstrap-datepicker.es.min.js"></script>','<script>
	$(document).ready(function() {
        $(".fecha").datepicker({
            "todayHighlight": true,
            "format": "dd/mm/yyyy",
            "autoclose": true,
            "language": "es"
        });
    })
	$("#form_fecha_promesa").mask("00/00/0000",{placeholder: "dd/mm/anio"});
	$("#form_monto_promesa").mask("00000.00", {reverse: true});
	</script>')), 0, false);
}
}
