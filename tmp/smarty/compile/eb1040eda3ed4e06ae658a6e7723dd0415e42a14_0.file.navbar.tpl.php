<?php
/* Smarty version 3.1.33, created on 2021-04-07 17:06:37
  from '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/navbar.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_606e2ced41c615_78547726',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'eb1040eda3ed4e06ae658a6e7723dd0415e42a14' => 
    array (
      0 => '/opt/www/cobranzas/template/smarty/tpls/main_gestionar/navbar.tpl',
      1 => 1617832932,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_606e2ced41c615_78547726 (Smarty_Internal_Template $_smarty_tpl) {
?>	<ul class="navbar-nav mr-auto">
		<li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Medios de Contacto</a>
			<div class="dropdown-menu" aria-labelledby="dropdown01">
				<a class="dropdown-item" href="javascript:agregarTelefonoModal()">Agregar Teléfono</a>
				<a class="dropdown-item" href="javascript:agregarDireccionModal()">Agregar Dirección</a>
			</div>
		</li>
		<!--
		<li class="nav-item active">
			<a class="nav-link" href="#">Detalle Cliente <span class="sr-only">(current)</span></a>
		</li>
		<li class="nav-item">
			<a class="nav-link" href="#">Detalle Cuenta</a>
		</li>
		<li class="nav-item">
			<a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
		</li>
		<li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Dropdown</a>
			<div class="dropdown-menu" aria-labelledby="dropdown01">
				<a class="dropdown-item" href="#">Action</a>
				<a class="dropdown-item" href="#">Another action</a>
				<a class="dropdown-item" href="#">Something else here</a>
			</div>
		</li>
		-->
	</ul>
	
	<ul class="navbar-nav mr-auto">
		<li class="nav-item dropdown">
			<a class="nav-link dropdown-toggle" href="#" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Opciones de Gestión</a>
			<div class="dropdown-menu" aria-labelledby="dropdown01">
				<a class="dropdown-item" href="javascript:MostrarHistoricoGestion()">Historico Gestión</a>
				<a class="dropdown-item" href="javascript:MostrarDirecciones()">Direcciones</a>
				<a class="dropdown-item" href="javascript:MostrarGarantias()">Garantías</a>
			</div>
		</li>
	</ul>
	
    <a class="btn btn-outline-light" href="javascript:window.location='dispatcher.php?user_name=<?php echo $_GET['user_name'];?>
'">Buscar otro Registro</a>
    &nbsp;
    <a class="btn btn-outline-light" href="javascript:MostrarScriptModal()">Script</a>
<!--
    <form class="form-inline my-2 my-lg-0">
      <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
      <button class="btn btn-secondary my-2 my-sm-0" type="submit">Search</button>
    </form>
--><?php }
}
