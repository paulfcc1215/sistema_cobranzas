<?php
/* Smarty version 3.1.33, created on 2021-10-14 09:19:06
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/telefono_table.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_61683c5a33b854_25937588',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'e67201ab7f717d1f3e1068679bb84b519dc83df0' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/telefono_table.tpl',
      1 => 1634221134,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_61683c5a33b854_25937588 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.fjjf_box_telefonos.php','function'=>'smarty_function_fjjf_box_telefonos',),1=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/function.box_telefonos_gestionados.php','function'=>'smarty_function_box_telefonos_gestionados',),));
?>
<table class="table table-small">
	<thead>
		<tr style="background-color:#98D858;"><th colspan="5" style="text-align:center;">CONTACTADOS</th></tr>
		<tr>
			<th>Fecha Gestión</th>
			<th>Pertenece a</th>
			<th>Teléfono</th>
			<th>Tipificación</th>
			<th>Fuente</th>
		</tr>
	</thead>

	<tbody>
		<!--<?php echo smarty_function_fjjf_box_telefonos(array('data'=>$_smarty_tpl->tpl_vars['with_cuenta']->value['telefonos']),$_smarty_tpl);?>
-->
		<?php echo smarty_function_box_telefonos_gestionados(array('data'=>$_smarty_tpl->tpl_vars['with_cuenta']->value['deudor']['telefonos_gestionados'],'tipo'=>'contacto'),$_smarty_tpl);?>

	</tbody>
</table>

<table class="table table-small">
	<thead>
		<tr style="background-color:#D85858;"><th colspan="5" style="text-align:center;">NO CONTACTADOS</th></tr>
		<tr>
			<th>Fecha Gestión</th>
			<th>Pertenece a</th>
			<th>Teléfono</th>
			<th>Tipificación</th>
		</tr>
	</thead>

	<tbody>
		<!--<?php echo smarty_function_fjjf_box_telefonos(array('data'=>$_smarty_tpl->tpl_vars['with_cuenta']->value['telefonos']),$_smarty_tpl);?>
-->
		<?php echo smarty_function_box_telefonos_gestionados(array('data'=>$_smarty_tpl->tpl_vars['with_cuenta']->value['deudor']['telefonos_gestionados'],'tipo'=>'no_contacto'),$_smarty_tpl);?>

	</tbody>
</table>

<!--<table class="table table-small">
  <thead>
	  <tr>
		<th>Pertenece A</th>
		<th>Teléfono</th>
		<th>Mejor Gestión</th>
		<th>Fuente</th>
	  </tr>
  </thead>
  
  <tbody>
	<?php echo smarty_function_fjjf_box_telefonos(array('data'=>$_smarty_tpl->tpl_vars['with_cuenta']->value['telefonos']),$_smarty_tpl);?>

  </tbody>
</table>--><?php }
}
