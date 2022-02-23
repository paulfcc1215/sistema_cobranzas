<?php
function smarty_function_fjjf_box_cliente($params,$template) {
	$html='
		<div class="card">
		  <div class="card-header">
			<b>Cliente</b>
		  </div>
		  <div class="card-body" style="min-height: 200px; max-height: 200px; overflow-y: auto;">
			<!--
			<h5 class="card-title">1757181571 - FERNANDO JAVIER JIMENEZ FUENTES</h5>
			<p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
			-->
			<table class="table table-small table-striped">
			  <thead>
				<tr>
				  <th>#</th>
				  <th>Cuenta</th>
				  <th>Identificacion</th>
				  <th>Nombre</th>
				  <th>Cedente</th>
				  <th>Fecha Cuenta</th>
				  <th>Valor Original</th>
				  <th>Valor Actual</th>
				  <th>% Pagado</th>
				  
				</tr>
			  </thead>
			  <tbody>
				<tr>
					<td>1</td>
					<td>123456</td>
					<td>1757181571</td>
					<td>FERNANDO JAVIER JIMENEZ FUENTES</td>
					<td>DE PRATI</td>
					<td>11/09/2019</td>
					<td>$ 30.00</td>
					<td>$ 11.73</td>
					<td>39.1 %</td>
				</tr>
				<tr>
					<td>2</td>
					<td>X120920191</td>
					<td>1757181571</td>
					<td>FERNANDO JAVIER JIMENEZ FUENTES</td>
					<td>BIESS</td>
					<td>21/09/2019</td>
					<td>$ 1181.00</td>
					<td>$ 789.32</td>
					<td>66.83 %</td>
				</tr>
				<tr>
					<td>3</td>
					<td>X120920191</td>
					<td>1757181571</td>
					<td>FERNANDO JIMENEZ FUENTES</td>
					<td>ETA FASHION</td>
					<td>15/07/2019</td>
					<td>$ 250.00</td>
					<td>$ 125.00</td>
					<td>50.00 %</td>
				</tr>
			  </tbody>
			</table>
		  </div>
		</div>
	
	';
	return $html;
}