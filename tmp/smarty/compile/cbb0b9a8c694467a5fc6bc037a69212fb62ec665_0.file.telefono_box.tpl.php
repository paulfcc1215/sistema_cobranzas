<?php
/* Smarty version 3.1.33, created on 2019-10-08 09:37:59
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/telefono_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9c9f471e1712_41897337',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'cbb0b9a8c694467a5fc6bc037a69212fb62ec665' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/telefono_box.tpl',
      1 => 1570545476,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:main_gestionar/telefono_table.tpl' => 1,
  ),
),false)) {
function content_5d9c9f471e1712_41897337 (Smarty_Internal_Template $_smarty_tpl) {
?><style>
.sin_gestion {
}

.gestionado {
	background-color: #FFFFAA;
}

.gestion_primera_persona {
background-color: #D47FFF;
}

.gestion_tercera_persona {
	background-color: #FFAAD4;
}

.gestion_promesa {
	background-color: #AAFF7F;
}

</style>
		<div class="card">
		  <div class="card-header">
			<b>Teléfonos</b>
		  </div>
		  <div class="card-body" style="min-height: 200px; max-height: 200px; overflow-y: auto;" id="id_telefono_box">
		  <?php $_smarty_tpl->_subTemplateRender("file:main_gestionar/telefono_table.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_cuenta'=>$_smarty_tpl->tpl_vars['main_cuenta']->value), 0, false);
?>
		  </div>
		</div><?php }
}
