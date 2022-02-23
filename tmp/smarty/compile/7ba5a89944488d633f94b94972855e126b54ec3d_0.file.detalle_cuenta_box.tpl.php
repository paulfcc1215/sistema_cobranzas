<?php
/* Smarty version 3.1.33, created on 2019-10-07 11:46:51
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/detalle_cuenta_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9b6bfbd17734_51519779',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '7ba5a89944488d633f94b94972855e126b54ec3d' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/detalle_cuenta_box.tpl',
      1 => 1570466784,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d9b6bfbd17734_51519779 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/html/cobranza/lib/smarty/libs/plugins/function.implode_not_empty.php','function'=>'smarty_function_implode_not_empty',),1=>array('file'=>'/opt/www/html/cobranza/lib/smarty/libs/plugins/modifier.date_format.php','function'=>'smarty_modifier_date_format',),));
if ($_smarty_tpl->tpl_vars['only_content']->value != true) {?>
<div class="card">
  <div class="card-header">
	<b>Detalle Cuenta Seleccionada</b>
  </div>
  <div class="card-body" style="min-height: 200px; max-height: 200px; overflow-y: auto;" id="id_detalle_cuenta_box">
<?php }?>
	Cuenta: <?php echo $_smarty_tpl->tpl_vars['with_cuenta']->value['cuenta']['cuenta'];?>
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

	<br><br>
	  <table border="1" class="tabla-detalle-cuenta">
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
<?php if ($_smarty_tpl->tpl_vars['only_content']->value != true) {?>	  
  </div>
</div>
<?php }
}
}
