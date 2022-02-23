<?php
error_reporting(0);
/**
* Variables
*            $_T['title'] = title on the <title> tag
*           $_T['errmsg'] = Error message (if any)
*          $_T['userval'] = Username to write in the value of the user textbox (if any)
*         $_T['basepath'] = templates base path (with trailing slash)
*           $_T['action'] = action
*  $_T['nombre_completo'] = nombre del usuario
* 
* 
* 
* 
* 
*/
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="http://getbootstrap.com/favicon.ico">

    <title><?php echo $_T['title']; ?></title>

    <!-- Bootstrap core CSS -->
    <link href="<?php echo $_T['basepath']; ?>assets/bs3/css/bootstrap.css" rel="stylesheet">

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="<?php echo $_T['basepath']; ?>assets/bs3/css/ie10-viewport-bug-workaround.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="<?php echo $_T['basepath']; ?>assets/signin.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy these 2 lines! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->
    <script src="<?php echo $_T['basepath']; ?>assets/ie-emulation-modes-warning.js"></script>

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="container">

      <form class="form-signin" action="<?php echo $_T['action']; ?>" method="POST" style="max-width: 600px; background-color: white; border: solid 1px #ccc; border-radius: 10px;">
      <input type="hidden" name="step" value="2">
      <div style="text-align: justify; font-size: 20px;">
      <b>
        Estimado <?php echo $_T['nombre_completo']; ?>. Es primera vez que utiliza el sistema o solicitó un cambio de contraseña.
        Para su seguridad, por favor ingrese una nueva contraseña.
        <br><br>
        <span style="color: red;">Debe tomar en cuenta que esta contraseña se almacena encriptada en la base de datos y por lo tanto, el equipo de sistema NO TIENE FORMA DE RECUPERARLA</span>
      </b>
      <br><br>
      <div style="font-size: 14px; font-weight: bold;">
       La contraseña nueva debe cumplir con los siguientes requisitos:
       <ul>
       <li>Debe contener al menos 8 caracteres</li>
       <li>Debe contener al menos una letra mayúscula</li>
       <li>Debe contener al menos una letra minúscula</li>
       <li>Debe contener al menos uno de los siguientes caracteres !@$%^&*()</li>
       </ul>
      </div>
      </div>
        <h2 class="form-signin-heading" style="text-align: center; margin-top: 20px;">INGRESE NUEVAS CREDENCIALES</h2>
        <div align="center" style="margin-top: 20px; margin-bottom: 20px;">
        <span class="error"><?php echo $_T['errmsg']; ?></span>
        </div>
        <label class="sr-only">Ingrese Contraseña</label>
        <input id="inputEmail" class="form-control" placeholder="Contraseña" required="true" autofocus="" type="password" name="password1">
        <label class="sr-only">Confirme Contraseña</label>
        <input id="inputPassword" class="form-control" placeholder="Confirme Contraseña" required="true" type="password"  name="password2">
        <br>
        <!--
        <div class="checkbox">
          <label>
            <input value="remember-me" type="checkbox"> Remember me
          </label>
        </div>
        -->
        <button class="btn btn-lg btn-primary btn-block" type="submit">Ingresar</button>
      </form>

    </div> <!-- /container -->


    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="<?php echo $_T['basepath']; ?>assets/ie10-viewport-bug-workaround.js"></script>
  

</body></html>