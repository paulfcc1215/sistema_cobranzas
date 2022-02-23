<?php
/* Smarty version 3.1.33, created on 2021-04-07 17:59:22
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/telefono_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_606e394a76c527_96504777',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '84bee1fbb7604a5c28d5b01d0423002808325945' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/telefono_box.tpl',
      1 => 1617836360,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:main_gestionar/telefono_table.tpl' => 1,
    'file:main_gestionar/telefono_table_sg.tpl' => 1,
  ),
),false)) {
function content_606e394a76c527_96504777 (Smarty_Internal_Template $_smarty_tpl) {
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
	<table>
		<tr>
			<td>
				<div class="card-body" style="min-height: 350px; max-height: 350px; overflow-y: auto;" id="id_telefono_box">
					<b>Gestionados:</b>
					<?php $_smarty_tpl->_subTemplateRender("file:main_gestionar/telefono_table.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_cuenta'=>$_smarty_tpl->tpl_vars['main_cuenta']->value), 0, false);
?>
				</div>
			</td>
			<td>
				<div class="card-body" style="min-height: 350px; max-height: 350px; overflow-y: auto;" id="id_telefono_box">
					<b>NO Gestionados:</b>
					<?php $_smarty_tpl->_subTemplateRender("file:main_gestionar/telefono_table_sg.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_cuenta'=>$_smarty_tpl->tpl_vars['main_cuenta']->value), 0, false);
?>
				</div>
			</td>
		</tr>
	</table>
</div><?php }
}
