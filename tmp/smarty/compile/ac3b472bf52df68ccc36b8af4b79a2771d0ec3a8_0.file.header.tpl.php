<?php
/* Smarty version 3.1.33, created on 2019-09-02 14:48:16
  from '/opt/www/html/cobranza/template/smarty/tpls/simple_generic/header.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d6d7200813699_74633859',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'ac3b472bf52df68ccc36b8af4b79a2771d0ec3a8' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/simple_generic/header.tpl',
      1 => 1567453691,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d6d7200813699_74633859 (Smarty_Internal_Template $_smarty_tpl) {
?><!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?php echo $_smarty_tpl->smarty->ext->configLoad->_getConfigVariable($_smarty_tpl, 'lib_path');?>
/css/bootstrap.min.css" crossorigin="anonymous">

    <title><?php echo (($tmp = @$_smarty_tpl->tpl_vars['title']->value)===null||$tmp==='' ? 'CRM' : $tmp);?>
</title>
  </head>
  <body>
<?php }
}
