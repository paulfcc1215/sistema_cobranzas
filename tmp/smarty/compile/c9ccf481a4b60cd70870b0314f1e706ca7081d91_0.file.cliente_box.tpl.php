<?php
/* Smarty version 3.1.33, created on 2022-01-18 17:25:52
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/cliente_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_61e73e70e7f8a1_26640245',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'c9ccf481a4b60cd70870b0314f1e706ca7081d91' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/cliente_box.tpl',
      1 => 1642544749,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_61e73e70e7f8a1_26640245 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.fjjf_call_closure.php','function'=>'smarty_function_fjjf_call_closure',),1=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.implode_not_empty.php','function'=>'smarty_function_implode_not_empty',),2=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/modifier.date_format.php','function'=>'smarty_modifier_date_format',),));
echo '<script'; ?>
>
	function updateCuentaData(id_cuenta) {
		$("#id_detalle_cuenta_box").html("<img src='template/assets/spinner5.gif' width='32'/> Cargando...");
		$("#id_gestiones_box_content").html("<img src='template/assets/spinner5.gif' width='32'/> Cargando...");
		$("#id_telefono_box").html("<img src='template/assets/spinner5.gif' width='32'/> Cargando...");
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
<?php $_smarty_tpl->_assignInScope('valor_deuda', sprintf("%.2f",$_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_original']));
$_smarty_tpl->_assignInScope('valor_pagado', sprintf("%.2f",$_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_pagado']));
if ($_smarty_tpl->tpl_vars['main_cuenta']->value['proceso']['id_campana'] == 11) {?>
	<?php $_smarty_tpl->_assignInScope('valor_deuda', sprintf("%.2f",$_smarty_tpl->tpl_vars['main_cuenta']->value['data_no_mapeada']['data']['cobro empresa2']));?>
	<?php $_smarty_tpl->_assignInScope('valor_pagado', sprintf("%.2f",$_smarty_tpl->tpl_vars['main_cuenta']->value['data_no_mapeada']['data']['pagos']));
}?>


<div class="card box_cliente">

	<div class="card-header">
		<b>Cuentas</b>
	</div>
	<div class="card-body" style="min-height: 250px; max-height: 200px; overflow-y: auto;">
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
					<!--<th>Fecha Cuenta</th>-->
					<th>Deuda Original</th>
					<th>
						<?php if ($_smarty_tpl->tpl_vars['main_cuenta']->value['proceso']['id_campana'] == 11) {?>
							Tiene Pagos
						<?php } else { ?>
							Valor Pagado
						<?php }?>
					</th>
					<th>Deuda Actual</th>
					<!--<th>% Pagado</th>-->
					<?php echo smarty_function_fjjf_call_closure(array('fn'=>$_smarty_tpl->tpl_vars['hooks']->value['smarty_client_box_header_append_th']),$_smarty_tpl);?>

				</tr>
			</thead>
			<tbody>
				<!-- cuenta seleccionada -->
				<tr class="cliente_row main_cuenta clickable selected_cuenta" onclick="clickOnCuenta(this);" data-id-cuenta="<?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['id_cuenta'];?>
">

					<?php if ($_smarty_tpl->tpl_vars['main_cuenta']->value['proceso']['id_campana'] == 11) {?>
					<?php echo $_smarty_tpl->tpl_vars['valor_pagado']->value;?>

						<?php if ($_smarty_tpl->tpl_vars['valor_pagado']->value != 0) {?>
							<?php $_smarty_tpl->_assignInScope('valor_pagado', 'SI');?>
						<?php } else { ?>
							<?php $_smarty_tpl->_assignInScope('valor_pagado', 'NO');?>
						<?php }?>
					<?php }?>

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
)<br><?php echo $_smarty_tpl->tpl_vars['main_cuenta']->value['proceso']['descripcion'];?>
</td>
					<!--<td class="fecha_creacion"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['fecha_creacion'],"%d/%m/%Y");?>
</td>-->
					<td class="valor_original">$ <?php echo $_smarty_tpl->tpl_vars['valor_deuda']->value;?>
</td>
					<td class="valor_pagado">$ <?php echo $_smarty_tpl->tpl_vars['valor_pagado']->value;?>
</td>
					<td class="valor_actual">$ <?php echo sprintf("%.2f",$_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_actual']);?>
</td>
					<!--<td class="prcnt_pagado" style="white-space: nowrap !important;"><?php echo sprintf("%.2f",(100-($_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_actual']*100/$_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_original'])));?>
 %</td>-->
					<?php echo smarty_function_fjjf_call_closure(array('fn'=>$_smarty_tpl->tpl_vars['hooks']->value['smarty_client_box_content_append_td']),$_smarty_tpl);?>

				</tr>
				<!-- fin cuenta seleccionada -->

				<?php $_smarty_tpl->_assignInScope('count', 1);?>
				<?php $_smarty_tpl->_assignInScope('total', $_smarty_tpl->tpl_vars['main_cuenta']->value['cuenta']['valor_actual']);?>
				<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['data']->value['cuentas'], 'i', false, 'k');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['k']->value => $_smarty_tpl->tpl_vars['i']->value) {
?>
					<?php if ($_smarty_tpl->tpl_vars['k']->value != $_smarty_tpl->tpl_vars['id_cuenta_seleccionada']->value) {?>
						<?php $_smarty_tpl->_assignInScope('count', $_smarty_tpl->tpl_vars['count']->value+1);?>
						<?php $_smarty_tpl->_assignInScope('total', $_smarty_tpl->tpl_vars['total']->value+$_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_actual']);?>
						<?php $_smarty_tpl->_assignInScope('ultimopago', $_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_pagado']);?>

						<?php if ($_smarty_tpl->tpl_vars['main_cuenta']->value['proceso']['id_campana'] == 11) {?>
							<?php if ($_smarty_tpl->tpl_vars['ultimopago']->value != 0) {?>
								<?php $_smarty_tpl->_assignInScope('ultimopago', 'SI');?>
							<?php } else { ?>
								<?php $_smarty_tpl->_assignInScope('ultimopago', 'NO');?>
							<?php }?>
						<?php }?>

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
<br>(<?php echo $_smarty_tpl->tpl_vars['i']->value['campana']['campana'];?>
)<br><?php echo $_smarty_tpl->tpl_vars['i']->value['proceso']['descripcion'];?>
</td>
							<!--<td class="fecha_creacion"><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['i']->value['cuenta']['fecha_creacion'],"%d/%m/%Y");?>
</td>-->
							<td class="valor_original">$ <?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_original'];?>
</td>
							<td class="valor_pagado">$ <?php echo $_smarty_tpl->tpl_vars['ultimopago']->value;?>
</td>
							<td class="valor_actual">$ <?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_actual'];?>
</td>
							<!--<td class="prcnt_pagado" style="white-space: nowrap !important;"><?php echo sprintf("%.2f",(100-($_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_actual']*100/$_smarty_tpl->tpl_vars['i']->value['cuenta']['valor_original'])));?>
 %</td>-->
						</tr>
					<?php }?>
				<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
			</tbody>
			<tfoot>
				<tr style="color:blue;">
					<td colspan="7" style="text-align:right;font-size:100%;"><b>Tot. a Cobrar:</b></td>
					<td style="font-size:100%;"><b>$ <?php echo $_smarty_tpl->tpl_vars['total']->value;?>
</b></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
<?php }
}
