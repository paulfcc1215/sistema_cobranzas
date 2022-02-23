<?php
/* Smarty version 3.1.33, created on 2019-09-25 10:43:38
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d8b8b2a72db73_34655592',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1e8a830132786f9ba5c723372b946de95aa4208a' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar.tpl',
      1 => 1569426205,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:common/header.tpl' => 1,
    'file:main_gestionar_navbar.tpl' => 1,
    'file:main_gestionar_cliente_box.tpl' => 1,
    'file:main_gestionar_test_box.tpl' => 1,
    'file:main_gestionar_gestiones.tpl' => 1,
    'file:main_gestionar_telefono_box.tpl' => 1,
    'file:common/footer.tpl' => 1,
  ),
),false)) {
function content_5d8b8b2a72db73_34655592 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('main_cuenta', $_smarty_tpl->tpl_vars['data']->value[$_smarty_tpl->tpl_vars['id_cuenta_seleccionada']->value]);
$_smarty_tpl->_subTemplateRender('file:common/header.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('top_elements'=>array('<link rel="stylesheet" href="template/smarty/tpls/gestionar.css" crossorigin="anonymous">')), 0, false);
?>
<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
  <a class="navbar-brand" href="#">Cobranzas FjjF</a>
  <!--
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  -->

  <div class="collapse navbar-collapse" id="navbarsExampleDefault">
  <?php $_smarty_tpl->_subTemplateRender('file:main_gestionar_navbar.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
  </div>
</nav>

<main role="main" class="container-fluid" style="margin-top: 80px;">
  <div class="row">
    <div class="col-7">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar_cliente_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    </div>
	<div class="col">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar_test_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
	</div>
  </div>
  
  

  <div class="row" style="margin-top: 20px;">
    <div class="col">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar_gestiones.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
	</div>  
  </div>
  <div class="row" style="margin-top: 20px;">
    <div class="col-7">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar_telefono_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    </div>
  
    <div class="col">
		<div class="card">
		  <div class="card-header">
			<b>Datos del Teléfono Seleccionado</b>
		  </div>
		  <div class="card-body" style="max-height: 200px; overflow-y: auto;">
		  </div>
		</div>
	</div>


  </div>






</main>

<?php $_smarty_tpl->_subTemplateRender('file:common/footer.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
