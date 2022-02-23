<?php
/* Smarty version 3.1.33, created on 2019-09-04 14:05:06
  from '/opt/www/html/cobranza/template/smarty/tpls/ajax_dispatcher_get_cedula.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d700ae26a14a5_57903910',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'aa5fccaea8d4e24419b3fe8fda6e06edee6bce1c' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/ajax_dispatcher_get_cedula.tpl',
      1 => 1567623899,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d700ae26a14a5_57903910 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/html/cobranza/lib/smarty/libs/plugins/modifier.plural.php','function'=>'smarty_modifier_plural',),));
if (count($_smarty_tpl->tpl_vars['records']->value) == 0) {?>
	<h2>No hay registros con la búsqueda indicada <?php echo $_smarty_tpl->tpl_vars['query']->value;?>
</h2>
<?php } else { ?>
	<b><?php echo count($_smarty_tpl->tpl_vars['records']->value);?>
 registro<?php echo smarty_modifier_plural($_smarty_tpl->tpl_vars['records']->value);?>
 encontrado<?php echo smarty_modifier_plural($_smarty_tpl->tpl_vars['records']->value);?>
</b>
	<br>
	<table class="table table-striped">
	<tr>
		<th>UDN</th>
		<th>Cuenta</th>
		<th>Identificacion</th>
		<th>Nombres</th>
		<th>Valor Actual (Valor Pagado)</th>
	</tr>
	<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['records']->value, 'i');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['i']->value) {
?>
		<tr class="clickable" onclick="window.location='?id_cuenta=<?php echo $_smarty_tpl->tpl_vars['i']->value['id_cuenta'];?>
'">
			<td><?php echo $_smarty_tpl->tpl_vars['i']->value['udn'];?>
</td>
			<td><?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta'];?>
</td>
			<td><?php echo $_smarty_tpl->tpl_vars['i']->value['identificacion'];?>
</td>
			<td><?php echo $_smarty_tpl->tpl_vars['i']->value['primer_nombre'];?>
 <?php echo $_smarty_tpl->tpl_vars['i']->value['segundo_nombre'];?>
 <?php echo $_smarty_tpl->tpl_vars['i']->value['primer_apellido'];?>
 <?php echo $_smarty_tpl->tpl_vars['i']->value['segundo_apellido'];?>
</td>
			<td>USD <?php echo $_smarty_tpl->tpl_vars['i']->value['valor_actual'];?>
 (<?php echo round((100-((($_smarty_tpl->tpl_vars['i']->value['valor_actual'])*100)/$_smarty_tpl->tpl_vars['i']->value['valor_original'])),2);?>
 %)</td>
		</tr>
	<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
	</table>

<?php }
}
}
