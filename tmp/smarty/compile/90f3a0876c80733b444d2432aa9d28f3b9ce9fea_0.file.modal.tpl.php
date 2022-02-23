<?php
/* Smarty version 3.1.33, created on 2019-10-02 15:35:58
  from '/opt/www/html/cobranza/template/smarty/tpls/common/modal.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d950a2e193c78_05128854',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '90f3a0876c80733b444d2432aa9d28f3b9ce9fea' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/common/modal.tpl',
      1 => 1570048556,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d950a2e193c78_05128854 (Smarty_Internal_Template $_smarty_tpl) {
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

<!-- Flexbox container for aligning the toasts -->
<div aria-live="polite" aria-atomic="true" class="d-flex justify-content-center align-items-center" style="min-height: 200px; position: absolute; top: 0; right: 0; z-index: 1000;">

  <!-- Then put toasts within -->
  <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header">
      <img src="..." class="rounded mr-2" alt="...">
      <strong class="mr-auto">Bootstrap</strong>
      <small>11 mins ago</small>
      <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="toast-body">
      Hello, world! This is a toast message.
    </div>
  </div>
</div><?php }
}
