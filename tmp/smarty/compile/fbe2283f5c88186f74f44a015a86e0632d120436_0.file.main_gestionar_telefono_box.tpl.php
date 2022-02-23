<?php
/* Smarty version 3.1.33, created on 2019-09-23 16:07:40
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar_telefono_box.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d89341c090087_53111815',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fbe2283f5c88186f74f44a015a86e0632d120436' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar_telefono_box.tpl',
      1 => 1569272859,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d89341c090087_53111815 (Smarty_Internal_Template $_smarty_tpl) {
?><style>
.contactado {
	background-color: #AAFFAA;
}
.contactado_otro {
	background-color: #FFAAFF;
}
.gestionado {
	background-color: #FFFFAA;
}
.sep {
background-color: #ccc;
}
</style>
		<div class="card">
		  <div class="card-header">
			<b>Teléfonos</b>
		  </div>
		  <div class="card-body" style="min-height: 200px; max-height: 200px; overflow-y: auto;">
		  <table class="table table-small">
			  <thead>
				  <tr>
					<th>Pertenece A</th>
					<th>Cedente</th>
					<th>Teléfono</th>
					<th>Ultima Gestion</th>
					<th>Fuente</th>
				  </tr>
			  </thead>
			  
			  <tbody>
				<tr class="sep">
				<td colspan="6">CONTACTADOS</td>
				</tr>
			  
				<tr class="contactado">
					<td>TITULAR</td>
					<td>Interaguas</td>
					<td>0969060548</td>
					<td>SI</td>
					<td>BASE</td>
				</tr>
				<tr class="contactado_otro">
					<td>TITULAR</td>
					<td>Claro</td>
					<td>0969060548</td>
					<td>2019-09-23 15:50:00</td>
					<td>BASE</td>
				</tr>




				<tr class="sep">
				<td colspan="6">NO CONTACTADOS</td>
				</tr>
				
				<tr class="gestionado">
					<td>CONYUGUE</td>
					<td>Claro</td>
					<td>0969060548</td>
					<td>SI</td>
					<td>2019-09-23 15:50:00</td>
					<td>BASE</td>
				</tr>
				<tr class="gestionado">
					<td>TITULAR</td>
					<td>Claro</td>
					<td>0969060548</td>
					<td>2019-09-23 15:50:00</td>
					<td>REPOSITORIO</td>
				</tr>
				<tr class="">
					<td>TITULAR</td>
					<td>Claro</td>
					<td>0969060548</td>
					<td>2019-09-23 15:50:00</td>
					<td>REPOSITORIO</td>
				</tr>
				<tr class="">
					<td>TITULAR</td>
					<td>Claro</td>
					<td>0969060548</td>
					<td>2019-09-23 15:50:00</td>
					<td>REPOSITORIO</td>
				</tr>
				
				<tr class="sep">
				<td colspan="6">NO GESTIONADOS</td>
				</tr>
				
			  </tbody>
		  </table>
		  </div>
		</div><?php }
}
