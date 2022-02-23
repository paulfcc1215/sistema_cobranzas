<?php
/* Smarty version 3.1.33, created on 2019-10-11 15:47:58
  from '/opt/www/cobranzas/template/smarty/tpls/common/modal.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5da0ea7ead04d0_73284641',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '447042bbfdce0450fa449353aa97b86b9a0cf9a7' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/common/modal.tpl',
      1 => 1570826877,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5da0ea7ead04d0_73284641 (Smarty_Internal_Template $_smarty_tpl) {
echo '<script'; ?>
>
function showModal(title,body) {
	$("#<?php echo $_smarty_tpl->tpl_vars['with_id']->value;?>
 .modal-header button").remove();
	var params={
		"allowClose":true,
		"buttons": [
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cerrar"
			}
		]
	};
	if(arguments[2]!=null) {
		for(var i in arguments[2]) {
			params[i]=arguments[2][i];
		}
	}
	$("#<?php echo $_smarty_tpl->tpl_vars['with_id']->value;?>
 .modal-title").html(title);
	$("#<?php echo $_smarty_tpl->tpl_vars['with_id']->value;?>
 .modal-body").html(body);
	
	var html_buttons=new Array();
	for(var b in params.buttons) {
		var button=params.buttons[b];
		var html_button='<button class="'+button.class+'"';
		if(typeof button.action == "string") {
			if(button.action=="close") {
				html_button=html_button+' data-dismiss="modal"';
			}
		}else if(typeof button.action == "function") {
			html_button=html_button+' onclick="'+button.action.name+'(this)"';
		}
		html_button=html_button+">"+button.label+"</button>";
		html_buttons.push(html_button);
	}
	$("#<?php echo $_smarty_tpl->tpl_vars['with_id']->value;?>
 .modal-footer").html(html_buttons.join(""));
	
	$("#<?php echo $_smarty_tpl->tpl_vars['with_id']->value;?>
").modal()
}
<?php echo '</script'; ?>
>
<!-- The Modal -->
<div class="modal fade" id="<?php echo $_smarty_tpl->tpl_vars['with_id']->value;?>
" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Modal Heading</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        Modal body..
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<!-- end modal --><?php }
}
