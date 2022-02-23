<?php
/* Smarty version 3.1.33, created on 2019-09-25 10:44:55
  from '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/gestiones.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_5d8b8b774153e1_20405882',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '1a339514fd46b58b9b800a3cbe3efcd1311bf15a' => 
    array (
      0 => '/opt/www/html/cobranza/template/smarty/tpls/main_gestionar/gestiones.tpl',
      1 => 1569426217,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5d8b8b774153e1_20405882 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="card">
  <div class="card-header">
	<b>Gestión <button class="btn btn-primary">Gestionar</button></b>
  </div>
  <div class="card-body" style="max-height: 200px; overflow-y: auto;">
  
  <table class="table table-small">
  <thead>
	<tr>
		<th>Usuario</th>
		<th>Fecha</th>
		<th>Teléfono</th>
		<th>Tipificación</th>
		<th>Sub Tipificacion</th>
		<th>Fecha Compromiso</th>
		<th>Observación</th>
	</tr>
  </thead>
  <tbody>
	<tr>
		<td>FJIMENEZ</td>
		<td>01/09/2019</td>
		<td>0969060548</td>
		<td>CU4 - VOLVER A LLAMAR</td>
		<td>VOLVER A LLAMAR</td>
		<td>- -</td>
		<td>- -</td>
	</tr>
	<tr>
		<td>DALARCON</td>
		<td>12/09/2019</td>
		<td>0969060548</td>
		<td>CU1 - COMPROMISO DE PAGO</td>
		<td>COMPROMISO</td>
		<td>24/09/2019</td>
		<td>Dice que se acercará a pagar la mitad del monto nada más porque actualmente está chiro y no tiene cómo pagar.</td>
	</tr>
	<tr style="background-color: #7FD4FF;">
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>
		<select>
		<option value="">CU1 - PROMESA DE PAGO</option>
		<option value="">CU5 - NO RESPONDE</option>
		<option value="">CU4 - VOLVER A LLAMAR</option>
		</select>
		</td>
		<td>
		<select>
		<option value="">CU1 - PROMESA DE PAGO</option>
		<option value="">CU5 - NO RESPONDE</option>
		<option value="">CU4 - VOLVER A LLAMAR</option>
		</select>
		</td>
		<td><input type="text" class="fecha"></td>
		<td><input type="text" polaceholder="Observaciones..."></td>
	</tr>
  </tbody>
  </table>
  
  
  </div>
</div><?php }
}
