<?php
/* Smarty version 3.1.33, created on 2019-10-08 10:57:46
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/cliente_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9cb1fae931e7_35758806',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'ae833781fcb243db112f9eed1e50463f163e0937' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/cliente_box.tpl',
      1 => 1570550265,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d9cb1fae931e7_35758806 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/html/cobranza/lib/smarty/libs/plugins/function.implode_not_empty.php','function'=>'smarty_function_implode_not_empty',),1=>array('file'=>'/opt/www/html/cobranza/lib/smarty/libs/plugins/modifier.date_format.php','function'=>'smarty_modifier_date_format',),));
echo '<script'; ?>
>
function updateCuentaData(id_cuenta) {
	$.ajax({
		"url":"ajax.php",
		"method":"POST",
		"data":{
			"a":"get_cuenta_data",
			"id_cuenta":id_cuenta,
		},
		"success":function(d) {
			try {
				d=$.parseJSON(d);
				if(!d.success)
					throw d.error;
				d=$.parseJSON(d.data);
				console.log(d);
				$("#id_detalle_cuenta_box").html(d.html.carga_no_mapeada);
				$("#id_gestiones_box_content").html(d.html.gestiones);
				$("#id_telefono_box").html(d.html.telefonos);
			}catch(err) {
				alert(err);
			}
		}
		
	});

}

function clickOnCuenta(row_cuenta) {
	row_cuenta=$(row_cuenta);
	var parents=row_cuenta.parents("tbody");
	parents.find(".selected_cuenta").each(function(k,o) {
		$(o).removeClass("selected_cuenta");
	});
	row_cuenta.addClass("selected_cuenta");
	
	cuentaSeleccionada=row_cuenta.data("id-cuenta");
	updateCuentaData(cuentaSeleccionada);
}

<?php echo '</script'; ?>
>

		<div class="card box_cliente">
		  <div class="card-header">
			<b>Cuentas</b>
		  </div>
		  <div class="card-body" style="min-height: 200px; max-height: 200px; overflow-y: auto;">
			<!--
			<h5 class="card-title">1757181571 - FERNANDO JAVIER JIMENEZ FUENTES</h5>
			<p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
			-->
			<table class="table table-small" id="table-cuentas">
			  <thead>
				<tr>
				  <th>#</th>
				  <th>Cuenta</th>
				  <th>Identificacion</th>
				  <th>Nombre</th>
				  <th>Cedente</th>
				  <th>Fecha Cuenta</th>
				  <th>Valor Original</th>
				  <th>Valor Actual</th>
				  <th>% Pagado</th>
				  
				</tr>
			  </thead>
			  <tbody>
				<!-- cuenta seleccionada -->
				<tr class="cliente_row main_cuenta clickable selected_cuenta" onclick="clickOnCuenta(this);" data-id-cuenta="<?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['id_cuenta'];?>
">
					<td class="num">1</td>
					<td class="cuenta"><?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['cuenta'];?>
<br><div style="font-size: 10px;">(Id: <?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['id_cuenta'];?>
)</div></td>
					<td class="identificacion"><?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['deudor']['identificacion'];?>
</td>
					<td class="nombre"><?php ob_start();
echo $_smarty_tpl->tpl_vars['main_cuenta']->value['deudor']['primer_nombre'];
$_prefixVariable1 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['main_cuenta']->value['deudor']['segundo_nombre'];
$_prefixVariable2 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['main_cuenta']->value['deudor']['primer_apellido'];
$_prefixVariable3 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['main_cuenta']->value['deudor']['segundo_apellido'];
$_prefixVariable4 = ob_get_clean();
echo smarty_function_implode_not_empty(array('what'=>array($_prefixVariable1,$_prefixVariable2,$_prefixVariable3,$_prefixVariable4)),$_smarty_tpl);?>
</td>
					<td class="udn"><?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['udn']['udn'];?>
<br>(<?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['campana']['campana'];?>
)</td>
					<td class="fecha_creacion"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['fecha_creacion'],"%d/%m/%Y");?>
</td>
					<td class="valor_original">$ <?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_original'];?>
</td>
					<td class="valor_actual">$ <?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_actual'];?>
</td>
					<td class="prcnt_pagado" style="white-space: nowrap !important;"><?php echo sprintf("%.2f",(100-($_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_actual']*100/$_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_original'])));?>
 %</td>
				</tr>
				<!-- fin cuenta seleccionada -->

				<?php $_smarty_tpl->_assignInScope('count', 1);?>
				<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['data']->value['cuentas'], 'i', false, 'k');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['k']->value => $_smarty_tpl->tpl_vars['i']->value) {
?>
					<?php if ($_smarty_tpl->tpl_vars['k']->value != $_smarty_tpl->tpl_vars['id_cuenta_seleccionada']->value) {?>
					<?php $_smarty_tpl->_assignInScope('count', $_smarty_tpl->tpl_vars['count']->value+1);?>
					<tr class="cliente_row clickable" onclick="clickOnCuenta(this);" data-id-cuenta="<?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta']['id_cuenta'];?>
">
						<td class="num"><?php echo $_smarty_tpl->tpl_vars['count']->value;?>
</td>
						<td class="cuenta"><?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta']['cuenta'];?>
<br><div style="font-size: 10px;">(Id: <?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta']['id_cuenta'];?>
)</div></td>
						<td class="identificacion"><?php echo $_smarty_tpl->tpl_vars['i']->value['deudor']['identificacion'];?>
</td>
						<td class="nombre"><?php ob_start();
echo $_smarty_tpl->tpl_vars['i']->value['deudor']['primer_nombre'];
$_prefixVariable5 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['i']->value['deudor']['segundo_nombre'];
$_prefixVariable6 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['i']->value['deudor']['primer_apellido'];
$_prefixVariable7 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['i']->value['deudor']['segundo_apellido'];
$_prefixVariable8 = ob_get_clean();
echo smarty_function_implode_not_empty(array('what'=>array($_prefixVariable5,$_prefixVariable6,$_prefixVariable7,$_prefixVariable8)),$_smarty_tpl);?>
</td>
						<td class="udn"><?php echo $_smarty_tpl->tpl_vars['i']->value['udn']['udn'];?>
</td>
						<td class="fecha_creacion"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['i']->value['cuenta']['fecha_creacion'],"%d/%m/%Y");?>
</td>
						<td class="valor_original">$ <?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_original'];?>
</td>
						<td class="valor_actual">$ <?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_actual'];?>
</td>
						<td class="prcnt_pagado" style="white-space: nowrap !important;"><?php echo sprintf("%.2f",(100-($_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_actual']*100/$_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_original'])));?>
 %</td>
					</tr>
					<?php }?>
				<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
			  </tbody>
			</table>
		  </div>
		</div>
<?php }
}
