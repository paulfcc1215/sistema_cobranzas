<?php
/* Smarty version 3.1.33, created on 2019-09-04 14:39:23
  from '/opt/www/html/cobranza/template/smarty/tpls/main_fatal.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d7012ebb15d46_70647715',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '95063d252ba544c55486edbf2c4b9dc113469c58' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_fatal.tpl',
      1 => 1567625881,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:common/header.tpl' => 1,
    'file:common/footer.tpl' => 1,
  ),
),false)) {
function content_5d7012ebb15d46_70647715 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender("file:common/header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>


<div class="container">
<div class="card">
  <div class="card-header">
    <b>ERROR FATAL - UNHANDLED EXCEPTION</b>
  </div>
  
  <div class="card-body">
	<form method="POST">

	
    <p class="card-text">
	<pre style="border: solid 1px #ccc; border-radius: 3px; background-color: #efefef; padding: 5px;"><?php echo trim($_smarty_tpl->tpl_vars['error']->value);?>
</pre>


	</p>
    
  </div>
</div>

</div>


<?php $_smarty_tpl->_subTemplateRender("file:common/footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
