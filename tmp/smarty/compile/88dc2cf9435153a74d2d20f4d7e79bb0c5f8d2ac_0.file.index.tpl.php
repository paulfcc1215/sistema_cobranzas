<?php
/* Smarty version 3.1.33, created on 2019-09-02 14:45:30
  from '/opt/www/html/cobranza/template/smarty/tpls/simple_generic/index.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d6d715a6ea4e7_55539398',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '88dc2cf9435153a74d2d20f4d7e79bb0c5f8d2ac' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/simple_generic/index.tpl',
      1 => 1567453525,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:simple_generic/header.tpl' => 1,
    'file:simple_generic/body.tpl' => 1,
    'file:simple_generic/footer.tpl' => 1,
  ),
),false)) {
function content_5d6d715a6ea4e7_55539398 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->smarty->ext->configLoad->_loadConfigFile($_smarty_tpl, "main.conf", null, 0);
?>

<?php $_smarty_tpl->_subTemplateRender("file:simple_generic/header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
$_smarty_tpl->_subTemplateRender("file:simple_generic/body.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
$_smarty_tpl->_subTemplateRender("file:simple_generic/footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
