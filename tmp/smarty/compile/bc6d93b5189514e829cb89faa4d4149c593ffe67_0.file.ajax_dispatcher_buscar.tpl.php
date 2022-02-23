<?php
/* Smarty version 3.1.33, created on 2020-09-22 12:23:22
  from '/opt/www/cobranzas/template/smarty/tpls/ajax_dispatcher_buscar.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5f6a330a773b05_19097404',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'bc6d93b5189514e829cb89faa4d4149c593ffe67' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/ajax_dispatcher_buscar.tpl',
      1 => 1600795228,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5f6a330a773b05_19097404 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/modifier.plural.php','function'=>'smarty_modifier_plural',),));
if (count($_smarty_tpl->tpl_vars['records']->value) == 0) {?>
	<h2>No hay registros con la búsqueda indicada <?php echo $_smarty_tpl->tpl_vars['query']->value;?>
</h2>
<?php } else { ?>
	<b><?php echo count($_smarty_tpl->tpl_vars['records']->value);?>
 registro<?php echo smarty_modifier_plural($_smarty_tpl->tpl_vars['records']->value);?>
 encontrado<?php echo smarty_modifier_plural($_smarty_tpl->tpl_vars['records']->value);?>
</b>
	<br>
	<div class="accordion" id="accordionParent">
	<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['records']->value, 'r', false, 'k', 'fe1', array (
  'iteration' => true,
));
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['k']->value => $_smarty_tpl->tpl_vars['r']->value) {
$_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration']++;
?>
	  <div class="card">
		<div class="card-header" id="heading<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration'] : null);?>
">
		  <h2 class="mb-0">
			<button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration'] : null);?>
" aria-expanded="true" aria-controls="collapse<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration'] : null);?>
">
			  <?php echo $_smarty_tpl->tpl_vars['k']->value;?>

			</button>
		  </h2>
		</div>
		<div id="collapse<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration'] : null);?>
" class="collapse" aria-labelledby="heading<?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_fe1']->value['iteration'] : null);?>
" data-parent="#accordionParent">
		  <div class="card-body">
			<table class="table table-striped">
			<tr>
				<th>#</th>
				<th>UDN</th>
				<th>Proceso</th>
				<th>Cuenta</th>
				<th>Identificacion</th>
				<th>Nombres</th>
				<th>Valor Actual (Valor Pagado)</th>
			</tr>
			<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['r']->value, 'i', false, NULL, 'fe2', array (
  'iteration' => true,
));
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['i']->value) {
$_smarty_tpl->tpl_vars['__smarty_foreach_fe2']->value['iteration']++;
?>
				<!-- <tr class="clickable" onclick="window.open('?id_cuenta=<?php echo $_smarty_tpl->tpl_vars['i']->value['id_cuenta'];?>
&user_name=<?php echo $_smarty_tpl->tpl_vars['user_name']->value;?>
')"> -->
				<tr class="clickable" onclick="window.location='?id_cuenta=<?php echo $_smarty_tpl->tpl_vars['i']->value['id_cuenta'];?>
&user_name=<?php echo $_smarty_tpl->tpl_vars['user_name']->value;?>
'">
					<td><?php echo (isset($_smarty_tpl->tpl_vars['__smarty_foreach_fe2']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_fe2']->value['iteration'] : null);?>
</td>
					<td><?php echo $_smarty_tpl->tpl_vars['i']->value['udn'];?>
<br><div style="font-size: 12px;">(<?php echo $_smarty_tpl->tpl_vars['i']->value['campana'];?>
)</div></td>
					<td><?php echo $_smarty_tpl->tpl_vars['i']->value['id_proceso'];?>
 - <?php echo $_smarty_tpl->tpl_vars['i']->value['proceso_descripcion'];?>
</td>
					<td><?php echo $_smarty_tpl->tpl_vars['i']->value['cuenta'];?>
<br><div style="font-size: 10px;">(Id: <?php echo $_smarty_tpl->tpl_vars['i']->value['id_cuenta'];?>
)</div></td>
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
		  </div>
		</div>
	  </div>
	<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
	</div>	
<?php }
}
}
