<?php
/* Smarty version 3.1.33, created on 2019-10-08 10:54:45
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/telefono_table.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9cb1453b24a3_58045396',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'd20816cba9c582074ce3ef51299c40488c878dc7' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/telefono_table.tpl',
      1 => 1570550083,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d9cb1453b24a3_58045396 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/html/cobranza/lib/smarty/libs/plugins/function.fjjf_box_telefonos.php','function'=>'smarty_function_fjjf_box_telefonos',),));
?>
<table class="table table-small">
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
</table><?php }
}
