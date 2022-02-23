<?php
/* Smarty version 3.1.33, created on 2021-09-30 09:21:41
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/detalle_cuenta_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6155c7f56b7eb9_79397631',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '8bc673f56308a3e62678a8a2d2328e76e869bcfd' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/detalle_cuenta_box.tpl',
      1 => 1633011692,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6155c7f56b7eb9_79397631 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.implode_not_empty.php','function'=>'smarty_function_implode_not_empty',),1=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/modifier.date_format.php','function'=>'smarty_modifier_date_format',),2=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.fjjf_call_closure.php','function'=>'smarty_function_fjjf_call_closure',),));
if ($_smarty_tpl->tpl_vars['only_content']->value != true) {?>
<div class="card">
	<div class="card-header">
		<b>Detalle Cuenta Seleccionada</b>
	</div>
	<div class="card-body" style="min-height: 200px; max-height: 250px; overflow-y: auto;" id="id_detalle_cuenta_box">
	<?php }?>
		<!--Cuenta: <?php echo $_smarty_tpl->tpl_vars['with_cuenta']->value['cuenta']['cuenta'];?>
 perteneciente a <?php ob_start();
echo $_smarty_tpl->tpl_vars['with_cuenta']->value['deudor']['primer_nombre'];
$_prefixVariable1 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['with_cuenta']->value['deudor']['segundo_nombre'];
$_prefixVariable2 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['with_cuenta']->value['deudor']['primer_apellido'];
$_prefixVariable3 = ob_get_clean();
ob_start();
echo $_smarty_tpl->tpl_vars['with_cuenta']->value['deudor']['segundo_apellido'];
$_prefixVariable4 = ob_get_clean();
echo smarty_function_implode_not_empty(array('what'=>array($_prefixVariable1,$_prefixVariable2,$_prefixVariable3,$_prefixVariable4)),$_smarty_tpl);?>
.
		<br>
		Detalles provenientes de carga <?php echo $_smarty_tpl->tpl_vars['with_cuenta']->value['data_no_mapeada']['carga']['id_carga'];?>
 hecha el <?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['with_cuenta']->value['data_no_mapeada']['carga']['fecha_carga'],'%d/%m/%Y %H:%M:%S');?>

		<br>-->
		<table>
			<tr>
				<td colspan="2">
					<b>Otros datos</b>
					<?php if ($_smarty_tpl->tpl_vars['hooks']->value['smarty_detalle_cuenta_box_pre_otros_datos'] != '') {?>
                        <?php echo smarty_function_fjjf_call_closure(array('fn'=>$_smarty_tpl->tpl_vars['hooks']->value['smarty_detalle_cuenta_box_pre_otros_datos']),$_smarty_tpl);?>

                    <?php } else { ?>
					<table border="1" class="tabla-detalle-cuenta-otros-datos">
						<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['with_cuenta']->value['data_no_mapeada']['data'], 'v', false, 'k', 'fe', array (
  'iteration' => true,
));
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['k']->value => $_smarty_tpl->tpl_vars['v']->value) {
$_smarty_tpl->tpl_vars['__smarty_foreach_fe']->value['iteration']++;
?>
						<tr<?php if (!(1 & (isset($_smarty_tpl->tpl_vars['__smarty_foreach_fe']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_fe']->value['iteration'] : null))) {?> class="even"<?php } else { ?> class="odd"<?php }?>>
							<th><?php echo $_smarty_tpl->tpl_vars['k']->value;?>
</th>
							<td><?php echo $_smarty_tpl->tpl_vars['v']->value;?>
</td>
						</tr>
						<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
					</table>
                    <?php }?>
				</td>
			</tr>
			<tr>
				<td>
					<b>Pagos</b>
					<table border="1" class="tabla-pagos" style="font-size:85%; font-family:verdana;text-align:right;">
						<thead>
							<tr>
								<th>Ord.</th>
								<th>Tipo</th>
								<th>Cantidad</th>
								<!--<th>Fecha de carga</th>-->
								<th>Fecha de Pago</th>
							</tr>
						</thead>
						<tbody >
							
							<?php $_smarty_tpl->_assignInScope('fecha_carga_pago', $_smarty_tpl->tpl_vars['with_cuenta']->value['data_no_mapeada']['carga']['fecha_carga']);?>

														<?php $_smarty_tpl->_assignInScope('count', 1);?>
							<?php $_smarty_tpl->_assignInScope('total', 0);?>
							<?php $_smarty_tpl->_assignInScope('campana', $_smarty_tpl->tpl_vars['with_cuenta']->value['proceso']['id_campana']);?>
							<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['with_cuenta']->value['actualizaciones'], 'v', false, NULL, 'fp', array (
));
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['v']->value) {
?>
															<?php if ($_smarty_tpl->tpl_vars['campana']->value == 11) {?>
									<?php if ($_smarty_tpl->tpl_vars['v']->value['tipo_actualizacion'] == 'PAGO' && $_smarty_tpl->tpl_vars['ultimo_pago']->value != $_smarty_tpl->tpl_vars['fecha_carga_pago']->value) {?>
										<?php $_smarty_tpl->_assignInScope('ultimo_pago', $_smarty_tpl->tpl_vars['fecha_carga_pago']->value);?>
										<?php $_smarty_tpl->_assignInScope('total', $_smarty_tpl->tpl_vars['total']->value+abs($_smarty_tpl->tpl_vars['v']->value['diferencia']));?>
										<tr>
											<td><?php echo $_smarty_tpl->tpl_vars['count']->value;?>
</td>
											<td><?php echo $_smarty_tpl->tpl_vars['v']->value['tipo_actualizacion'];?>
</td>
											<td ><?php echo number_format(floatval(abs($_smarty_tpl->tpl_vars['v']->value['diferencia'])),2);?>
</td>
											<td><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['v']->value['fecha_actualizacion'],"%d/%m/%Y");?>
</td>
											<!--<td><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['fecha_carga_pago']->value,"%d/%m/%Y");?>
</td>-->
										</tr>
										<?php $_smarty_tpl->_assignInScope('count', $_smarty_tpl->tpl_vars['count']->value+1);?>
									<?php }?>
								<?php } else { ?>
									<?php if ($_smarty_tpl->tpl_vars['v']->value['tipo_actualizacion'] == 'PAGO') {?>
										<?php $_smarty_tpl->_assignInScope('ultimo_pago', $_smarty_tpl->tpl_vars['fecha_carga_pago']->value);?>
										<?php $_smarty_tpl->_assignInScope('total', $_smarty_tpl->tpl_vars['total']->value+abs($_smarty_tpl->tpl_vars['v']->value['diferencia']));?>
										<tr>
											<td><?php echo $_smarty_tpl->tpl_vars['count']->value;?>
</td>
											<td><?php echo $_smarty_tpl->tpl_vars['v']->value['tipo_actualizacion'];?>
</td>
											<td ><?php echo number_format(floatval(abs($_smarty_tpl->tpl_vars['v']->value['diferencia'])),2);?>
</td>
											<td><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['v']->value['fecha_actualizacion'],"%d/%m/%Y");?>
</td>
											<!--<td><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['fecha_carga_pago']->value,"%d/%m/%Y");?>
</td>-->
										</tr>
										<?php $_smarty_tpl->_assignInScope('count', $_smarty_tpl->tpl_vars['count']->value+1);?>
									<?php }?>
								<?php }?>
							<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
							<tr>
								<td colspan="2"><b>Total Pagos:</b></td>
								<td ><b><?php echo number_format(floatval($_smarty_tpl->tpl_vars['total']->value),2);?>
</b></td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</table>
		<?php if ($_smarty_tpl->tpl_vars['only_content']->value != true) {?>
	</div>
</div>
<?php }
}
}
