<?php
/* Smarty version 3.1.33, created on 2019-10-10 15:59:01
  from '/opt/www/cobranzas/template/smarty/tpls/common/header.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d9f9b95d777f6_22133799',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '657fb461b38720720c4f0dafb2b47007b543f61b' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/common/header.tpl',
      1 => 1567616978,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d9f9b95d777f6_22133799 (Smarty_Internal_Template $_smarty_tpl) {
?><!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $_smarty_tpl->smarty->ext->configLoad->_getConfigVariable($_smarty_tpl, 'lib_path');?>
/css/bootstrap.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo $_smarty_tpl->smarty->ext->configLoad->_getConfigVariable($_smarty_tpl, 'tpl_path');?>
/common/common.css" crossorigin="anonymous">
	<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['top_elements']->value, 'i');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['i']->value) {
?>
	<?php echo $_smarty_tpl->tpl_vars['i']->value;?>

	<?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>

    <title><?php echo (($tmp = @$_smarty_tpl->tpl_vars['title']->value)===null||$tmp==='' ? 'CRM' : $tmp);?>
</title>
  </head>
  <body>
<?php }
}
