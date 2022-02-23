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

      <form class="form-signin" action="<?php echo $_T['action']; ?>" method="POST">
        <h2 class="form-signin-heading">Por favor ingrese credenciales</h2>
        <span class="error"><?php echo $_T['errmsg']; ?></span>
        <label class="sr-only">Usuario</label>
        <input id="inputEmail" class="form-control" placeholder="Usuario" required="" autofocus="" type="text" name="username" value="<?php echo $_T['userval']; ?>">
        <label class="sr-only">Contraseña</label>
        <input id="inputPassword" class="form-control" placeholder="Contraseña" required="" type="password"  name="password">
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