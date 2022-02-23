<?php
  error_reporting(0);
  /**
  * Variables
  *            $_T['title'] = title on the <title> tag
  *           $_T['errmsg'] = Error message (if any)
  *          $_T['userval'] = Username to write in the value of the user textbox (if any)
  *         $_T['basepath'] = templates base path (with trailing slash)
  *           $_T['action'] = action
  * 
  */
?>

<!DOCTYPE html>
<!--
Template Name: Metronic - Bootstrap 4 HTML, React, Angular 11 & VueJS Admin Dashboard Theme
Author: KeenThemes
Website: http://www.keenthemes.com/
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
Purchase: https://1.envato.market/EA4JP
Renew Support: https://1.envato.market/EA4JP
License: You must have a valid license purchased only from themeforest(the above link) in order to legally use the theme for your project.
-->
<html lang="en">
	<!--begin::Head-->
	<head>
    <base href="../../../../">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta charset="utf-8" />
		<title><?php echo $_T['title']; ?></title>
		<meta name="description" content="Login Orion System" />
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
		<link rel="canonical" href="https://keenthemes.com/metronic" />
		<!--begin::Fonts-->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
		<!--end::Fonts-->
		<!--begin::Page Custom Styles(used by this page)-->
		<link href="<?php echo $_T['basepath_metronic']; ?>assets/css/pages/login/classic/login-5.css" rel="stylesheet" type="text/css" />
		<!--end::Page Custom Styles-->
		<!--begin::Global Theme Styles(used by all pages)-->
		<link href="<?php echo $_T['basepath_metronic']; ?>assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo $_T['basepath_metronic']; ?>assets/plugins/custom/prismjs/prismjs.bundle.css" rel="stylesheet" type="text/css" />
		<link href="<?php echo $_T['basepath_metronic']; ?>assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
		<!--end::Global Theme Styles-->
		<!--begin::Layout Themes(used by all pages)-->
		<!--end::Layout Themes-->
		<link rel="shortcut icon" href="<?php echo $_T['basepath_metronic']; ?>assets/media/logos/favicon.ico" />
	</head>
	<!--end::Head-->
	<!--begin::Body-->
	<body id="kt_body" class="header-mobile-fixed subheader-enabled aside-enabled aside-fixed aside-secondary-enabled page-loading">
		<!--begin::Main-->
		<div class="d-flex flex-column flex-root">
			<!--begin::Login-->
			<div class="login login-5 login-signin-on d-flex flex-row-fluid" id="kt_login">
				<div class="d-flex flex-center bgi-size-cover bgi-no-repeat flex-row-fluid" style="background-image: url(<?php echo $_T['basepath_metronic']; ?>assets/media/bg/bg-2.jpg);">
					<div class="login-form text-center text-white p-7 position-relative overflow-hidden">
						<!--begin::Login Header-->
						<div class="d-flex flex-center mb-15">
								<img src="<?php echo $_T['basepath_metronic']; ?>assets/media/logos/logo_orion_2.png" class="max-h-250px" alt="" />
						</div>
						<!--end::Login Header-->
						<!--begin::Login Sign in form-->
						<div class="login-signin">
							<div class="mb-20">
								<h3 class="opacity-40 font-weight-normal">Inicio de Sesión</h3>
								<p class="opacity-40">Ingrese sus credenciales de acceso:</p>
							</div>
							<form class="form" id="kt_login_signin_form" action="<?php echo $_T['action']; ?>" method="post">
                <span class="error"><?php echo $_T['errmsg']; ?></span>
								<div class="form-group">
									<input class="form-control h-auto text-white bg-white-o-5 rounded-pill border-0 py-4 px-8" type="text" required="" autofocus="" placeholder="Usuario" name="username" autocomplete="off" value="<?php echo $_T['userval']; ?>"/>
								</div>
								<div class="form-group">
									<input class="form-control h-auto text-white bg-white-o-5 rounded-pill border-0 py-4 px-8" type="password" placeholder="Contraseña" name="password" />
								</div>
								<div class="form-group text-center mt-10">
									<button id="kt_login_signin_submit" class="btn btn-pill btn-primary opacity-90 px-15 py-3" type="submit">Iniciar</button>
								</div>
							</form>
						</div>
						<!--end::Login Sign in form-->
					</div>
				</div>
			</div>
			<!--end::Login-->
		</div>
		<!--end::Main-->
		<script>var HOST_URL = "https://preview.keenthemes.com/metronic/theme/html/tools/preview";</script>
		<!--begin::Global Config(global config for global JS scripts)-->
		<script>var KTAppSettings = { "breakpoints": { "sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1200 }, "colors": { "theme": { "base": { "white": "#ffffff", "primary": "#1BC5BD", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#6993FF", "warning": "#FFA800", "danger": "#F64E60", "light": "#F3F6F9", "dark": "#212121" }, "light": { "white": "#ffffff", "primary": "#1BC5BD", "secondary": "#ECF0F3", "success": "#C9F7F5", "info": "#E1E9FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0" }, "inverse": { "white": "#ffffff", "primary": "#ffffff", "secondary": "#212121", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff" } }, "gray": { "gray-100": "#F3F6F9", "gray-200": "#ECF0F3", "gray-300": "#E5EAEE", "gray-400": "#D6D6E0", "gray-500": "#B5B5C3", "gray-600": "#80808F", "gray-700": "#464E5F", "gray-800": "#1B283F", "gray-900": "#212121" } }, "font-family": "Poppins" };</script>
		<!--end::Global Config-->
		<!--begin::Global Theme Bundle(used by all pages)-->



		<!--<script src="<?php echo $_T['basepath_metronic']; ?>assets/plugins/global/plugins.bundle.js"></script>
		<script src="<?php echo $_T['basepath_metronic']; ?>assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
		<script src="<?php echo $_T['basepath_metronic']; ?>assets/js/scripts.bundle.js"></script>-->




		<!--end::Global Theme Bundle-->
		<!--begin::Page Scripts(used by this page)-->


		<!--<script src="<?php echo $_T['basepath_metronic']; ?>assets/js/pages/custom/login/login-general.js"></script>-->



		<!--end::Page Scripts-->
	</body>
	<!--end::Body-->
</html>