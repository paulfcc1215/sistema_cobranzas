<?php
/* Smarty version 3.1.33, created on 2019-10-10 11:11:32
  from '/opt/www/html/cobranza/template/smarty/tpls/main_dispatcher_buscar.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9f58349f63c9_24996166',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'b84863702f21747e378378c57a5c7a23f83bb0c1' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_dispatcher_buscar.tpl',
      1 => 1570723887,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:common/header.tpl' => 1,
    'file:common/footer.tpl' => 1,
  ),
),false)) {
function content_5d9f58349f63c9_24996166 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('top_elements', array(''));?>

<?php $_smarty_tpl->_assignInScope('footer_elements', array("<script src=\"template/smarty/tpls/dispatcher_buscar.js\"></script>"));?>



<?php $_smarty_tpl->_subTemplateRender("file:common/header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('top_elements'=>$_smarty_tpl->tpl_vars['top_elements']->value), 0, false);
?>

<div class="container">
<div class="card">
  <div class="card-header">
    <b>Búsqueda - 0906561329 - Gestiones 0915496509</b>
  </div>
  
  <div class="card-body">
	<div class="btn-group btn-group-toggle" data-toggle="buttons">	
		<label class="btn btn-primary<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'cedula' ? ' active' : '';?>
">
		<input type="radio" name="buscar_por" value="cedula" id="option1" autocomplete="off"<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'cedula' ? ' checked' : '';?>
> Cédula
		</label>
		<label class="btn btn-primary<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'cuenta' ? ' active' : '';?>
">
		<input type="radio" name="buscar_por" value="cuenta" id="option2" autocomplete="off"<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'cuenta' ? ' checked' : '';?>
> Cuenta
		</label>
		<label class="btn btn-primary<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'id_cuenta' ? ' active' : '';?>
">
		<input type="radio" name="buscar_por" value="id_cuenta" id="option3" autocomplete="off"<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'id_cuenta' ? ' checked' : '';?>
> Id Cuenta
		</label>
		<label class="btn btn-primary<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'nombres' ? ' active' : '';?>
">
		<input type="radio" name="buscar_por" value="nombres" id="option4" autocomplete="off"<?php echo $_smarty_tpl->tpl_vars['buscar_por']->value == 'nombres' ? ' checked' : '';?>
> Nombre / Apellido
		</label>
	</div>

	<div id="error" style="padding: 0px !important; margin: 12px 0px 0px 0px !important; display: none;">
	<div class="alert alert-danger" role="alert" id="error_text"></div>
	</div>
	
    <p class="card-text">
	  <div class="form-group">
		<input type="text" class="form-control" placeholder="Ingrese datos..." name="q" id="terms">
	  </div>
	  <hr>
	  <button type="button" class="btn btn-primary" onclick="buscar_cuenta(this)">
	  Buscar
	  </button>
	</p>
    
  </div>
</div>
</div>
<div class="container" style="margin-top: 30px;">
	<div class="card">
		<div class="card-body">
			<div id="ajax_container">
			
			</div>
		</div>
	</div>
</div>

<?php $_smarty_tpl->_subTemplateRender("file:common/footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('footer_elements'=>$_smarty_tpl->tpl_vars['footer_elements']->value), 0, false);
}
}
