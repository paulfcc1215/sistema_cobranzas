<?php
/* Smarty version 3.1.33, created on 2019-10-07 12:20:22
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/main.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9b73d6907260_10261172',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'a33de9cc193395bd083117688e049e244fa140aa' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/main.tpl',
      1 => 1570468820,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:common/header.tpl' => 1,
    'file:common/modal.tpl' => 1,
    'file:common/toast.tpl' => 1,
    'file:main_gestionar/navbar.tpl' => 1,
    'file:main_gestionar/cliente_box.tpl' => 1,
    'file:main_gestionar/detalle_cuenta_box.tpl' => 1,
    'file:main_gestionar/gestiones_box.tpl' => 1,
    'file:main_gestionar/telefono_box.tpl' => 1,
    'file:main_gestionar/telefono_detalle_box.tpl' => 1,
    'file:common/footer.tpl' => 1,
  ),
),false)) {
function content_5d9b73d6907260_10261172 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_assignInScope('main_cuenta', $_smarty_tpl->tpl_vars['data']->value['cuentas'][$_smarty_tpl->tpl_vars['id_cuenta_seleccionada']->value]);
$_smarty_tpl->_subTemplateRender('file:common/header.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('top_elements'=>array('<link rel="stylesheet" href="template/smarty/tpls/gestionar.css" crossorigin="anonymous">')), 0, false);
?>

<?php $_smarty_tpl->_subTemplateRender("file:common/modal.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_id'=>"modal"), 0, false);
$_smarty_tpl->_subTemplateRender("file:common/toast.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_id'=>"modal"), 0, false);
?>

<?php echo '<script'; ?>
>
var cuentaSeleccionada=<?php echo $_smarty_tpl->tpl_vars['id_cuenta_seleccionada']->value;?>
;
<?php echo '</script'; ?>
>

<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
  <a class="navbar-brand" href="#">Cobranzas FjjF</a>
  <!--
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  -->

  <div class="collapse navbar-collapse" id="navbarsExampleDefault">
  <?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/navbar.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
  </div>
</nav>

<main role="main" class="container-fluid" style="margin-top: 80px;">
  <div class="row">
    <div class="col-7">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/cliente_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    </div>
	<div class="col">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/detalle_cuenta_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_cuenta'=>$_smarty_tpl->tpl_vars['main_cuenta']->value), 0, false);
?>
	</div>
  </div>
  
  

  <div class="row" style="margin-top: 20px;">
    <div class="col">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/gestiones_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('with_cuenta'=>$_smarty_tpl->tpl_vars['main_cuenta']->value), 0, false);
?>
	</div>  
  </div>
  <div class="row" style="margin-top: 20px;">
    <div class="col-7">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/telefono_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    </div>
  
    <div class="col">
		<?php $_smarty_tpl->_subTemplateRender('file:main_gestionar/telefono_detalle_box.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
	</div>


  </div>






</main>
<div style="height: 200px;"></div>
<?php $_smarty_tpl->_subTemplateRender('file:common/footer.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('footer_elements'=>array('<script src="template/assets/jquery.mask.min.js"></script>','<script>
	$("#form_fecha_promesa").mask("00/00/0000",{placeholder: "dd/mm/anio"});
	$("#form_monto_promesa").mask("00000.00", {reverse: true});
	</script>')), 0, false);
}
}
