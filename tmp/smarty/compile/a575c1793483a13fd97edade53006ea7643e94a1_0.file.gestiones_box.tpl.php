<?php
/* Smarty version 3.1.33, created on 2019-10-10 15:59:30
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/gestiones_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9f9bb26d4904_99699052',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a575c1793483a13fd97edade53006ea7643e94a1' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/gestiones_box.tpl',
      1 => 1570468042,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:main_gestionar/gestion_form.tpl' => 1,
  ),
),false)) {
function content_5d9f9bb26d4904_99699052 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/opt/www/cobranzas/lib/smarty/libs/plugins/modifier.date_format.php','function'=>'smarty_modifier_date_format',),));
if ($_smarty_tpl->tpl_vars['only_content']->value != true) {?>
<div class="card">
  <div class="card-header">
	<b>Gestión</b><!-- <button class="btn btn-primary">Gestionar</button> -->
  </div>
  <div class="card-body div-gestiones" id="id_gestiones_box">
  <?php $_smarty_tpl->_subTemplateRender("file:main_gestionar/gestion_form.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
 <?php }?>
  <div id="id_gestiones_box_content">
  <table class="table table-small table-gestiones">
  <thead>
	<tr>
		<th>Fecha</th>
		<th>Tipificación</th>
		<th>Teléfono</th>
		<th>Usuario</th>
		<th>Observación</th>
	</tr>
  </thead>
  <tbody>
  <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['with_cuenta']->value['gestiones'], 'i', false, 'k', 'it', array (
  'iteration' => true,
));
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['k']->value => $_smarty_tpl->tpl_vars['i']->value) {
$_smarty_tpl->tpl_vars['__smarty_foreach_it']->value['iteration']++;
?>
  <tr class="<?php if (!(1 & (isset($_smarty_tpl->tpl_vars['__smarty_foreach_it']->value['iteration']) ? $_smarty_tpl->tpl_vars['__smarty_foreach_it']->value['iteration'] : null))) {?>even<?php } else { ?>odd<?php }?>" title="Id gestion: <?php echo $_smarty_tpl->tpl_vars['i']->value['id_gestion'];?>
 | Id Llamada: <?php echo $_smarty_tpl->tpl_vars['i']->value['telh_id'];?>
">
  
  <td><?php echo smarty_modifier_date_format($_smarty_tpl->tpl_vars['i']->value['fecha_inicio'],"%d/%m/%Y %H:%M:%S");?>
</td>
  <?php if ($_smarty_tpl->tpl_vars['i']->value['fecha_compromiso'] != '') {?>
  <td><?php echo $_smarty_tpl->tpl_vars['i']->value['tipificacion']['descripcion'];?>
<br><div class="detalle-compromiso"><?php echo $_smarty_tpl->tpl_vars['i']->value['fecha_compromiso'];?>
 ($<?php echo $_smarty_tpl->tpl_vars['i']->value['monto_compromiso'];?>
)</div></td>
  <?php } else { ?>
  <td><?php echo $_smarty_tpl->tpl_vars['i']->value['tipificacion']['descripcion'];?>
</td>
  <?php }?>
  <td><?php echo $_smarty_tpl->tpl_vars['i']->value['tel_number'];?>
</td>
  <td><?php echo $_smarty_tpl->tpl_vars['i']->value['user_name'];?>
</td>
  <td><textarea readonly="1" cols="40" style="width: 100%"><?php echo $_smarty_tpl->tpl_vars['i']->value['observacion'];?>
</textarea></td>
  </tr>
  <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
  </tbody>
  </table>
  </div>
<?php if ($_smarty_tpl->tpl_vars['only_content']->value != true) {?>  
  </div>
</div>
<?php }
}
}
