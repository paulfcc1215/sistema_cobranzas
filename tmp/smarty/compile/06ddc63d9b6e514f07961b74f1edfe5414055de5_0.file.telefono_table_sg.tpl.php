<?php
/* Smarty version 3.1.33, created on 2021-10-14 09:18:51
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/telefono_table_sg.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_61683c4b025e74_72544154',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '06ddc63d9b6e514f07961b74f1edfe5414055de5' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/telefono_table_sg.tpl',
      1 => 1634221123,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_61683c4b025e74_72544154 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.fjjf_box_telefonos.php','function'=>'smarty_function_fjjf_box_telefonos',),));
?>
<table class="table table-small">
	<thead>
		<tr>
			<th>Pertenece a</th>
			<th>Persona</th>
			<th>Teléfono</th>
			<th>Fuente</th>
		</tr>
	</thead>

	<tbody>
		<!--<?php echo smarty_function_fjjf_box_telefonos(array('data'=>$_smarty_tpl->tpl_vars['with_cuenta']->value['telefonos']),$_smarty_tpl);?>
-->
				<?php echo smarty_function_fjjf_box_telefonos(array('data'=>$_smarty_tpl->tpl_vars['with_cuenta']->value['telefonos']),$_smarty_tpl);?>

	</tbody>
</table>
<?php }
}
