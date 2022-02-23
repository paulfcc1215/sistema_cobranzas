<?php
error_reporting(0);
/**
* Variables
*            $_T['maintitle'] = title to put on maincontent (if any). Can be empty.
*                $_T['title'] = title on the <title> tag
*          $_T['showsidebar'] = (boolena) show sidebar
*       $_T['sidebarcontent'] = sidebar raw content
*          $_T['top_content'] = raw content on top of html (before anything else)
*          $_T['maincontent'] = raw maincontent
*          $_T['projectname'] = content of the projectname space
*                 $_T['icon'] = favicon url if any
*            $_T['navbar_li'] = (string) <li> items in the top navbar ie. <li><a href="#">Link</a></li>
*             $_T['basepath'] = templates base path (with trailing slash)
* 
*       $_T['bottom_js_files'] = (array) js files to load at bottom of page (after jquery)
*       $_T['top_js_files'] = (array)  js files to load at bottom of page (after jquery)
*       $_T['bottom_jscript'] = (string) code to execute inside a <script> tag at bottom (after file loads)
*       $_T['top_jscript'] = (string) code to execute inside a <script> tag at top (after all file loads)
*       $_T['css'] = (string) css code inside the <style> 
*       $_T['css_files'] = (array) css files to load 

* 
* 
* 
*/

error_reporting(0);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <meta name="description" content="">
    <meta name="author" content="">
    <?php
    if($_T['icon']!='') {
      echo '<link rel="icon" href="'.$_T['icon'].'">';
    }
   ; ?>
    

    <title><?php echo $_T['title']; ?></title>
    
    <!-- DataTable CSS -->
    <link href="<?php echo $_T['basepath']; ?>assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="<?php echo $_T['basepath']; ?>assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css" rel="stylesheet">
    <link href="<?php echo $_T['basepath']; ?>assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css" rel="stylesheet">

    <!-- Bootstrap core CSS -->
    <link href="<?php echo $_T['basepath']; ?>assets/bs3/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo $_T['basepath']; ?>assets/bs3/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="<?php echo $_T['basepath']; ?>assets/bootstrap-toggle-master/css/bootstrap2-toggle.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="<?php echo $_T['basepath']; ?>/assets/custom.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="<?php echo $_T['basepath']; ?>/assets/dashboard.css" rel="stylesheet">
    
    <!-- Datepicker styles -->
    <link href="<?php echo $_T['basepath']; ?>/assets/bs3/css/bootstrap-datepicker3.min.css" rel="stylesheet">

    <?php
    if(is_array($_T['css_files'])) {
        foreach($_T['css_files'] as $f) {
            echo '<link href="'.$f.'" rel="stylesheet">';
        }
    }

    if(is_array($_T['top_js_files'])) {
        foreach($_T['top_js_files'] as $f) {
            echo '<script src="'.$f.'"></script>';
        }
    }
   ?>
   
    <style>
    tr.clickable:hover {
        cursor: pointer !important;
        background-color: #D4FFD4 !important;
    }
    <?php echo $_T['css']; ?>
    </style>
    <script src="<?php echo $_T['basepath']; ?>assets/jq/js/jquery-3.3.1.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/jscolor/jscolor.js"></script>
    <!-- Notify -->
    <script src="<?php echo $_T['basepath']; ?>assets/notify/notify.min.js"></script>

    <!-- DataTable JS-->
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

    <script>
    <?php echo $_T['top_jscript']; ?>
    </script>
    
  </head>

  <body>
  <?php echo $_T['top_content']; ?>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#"><?php echo $_T['projectname']; ?></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
            <?php echo $_T['navbar_li']; ?>
          </ul>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <?php
        if($_T['showsidebar']) {
        ?>
          <div class="col-sm-3 col-md-2 sidebar">
            <?php echo $_T['sidebarcontent']; ?>
          </div>
          <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
        <?php
        }else{
          ?>
            <div class="col-sm-9 col-md-12 main">
          <?php
        }
        ?>
        <?php
          echo get_navigation('/opt/www/cobranzas/modules/'.$_GET['mod'].'.php');
          echo '<br><br>';
          if($_T['maintitle']!='') {
            echo '<h1 class="page-header"><div class="titulo1"><p>'.$_T['maintitle'].'</p></div></h1>';
          }
          echo $_T['maincontent'];
        ?>
        </div>
      </div>
    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="<?php echo $_T['basepath']; ?>assets/bs3/js/bootstrap.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/bs3/js/bootstrap-datepicker.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/bs3/js/bootstrap-datepicker.es.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/bs3/js/bootstrap3-typeahead.min.js"></script>
    <script src="<?php echo $_T['basepath']; ?>assets/bootstrap-toggle-master/js/bootstrap2-toggle.min.js"></script>

    <!-- HighChart -->
    <script src="lib/HighCharts/code/highcharts.js"></script>
    <script src="lib/HighCharts/code/modules/data.js"></script>
    <script src="lib/HighCharts/code/modules/drilldown.js"></script>
    <script src="lib/HighCharts/code/modules/exporting.js"></script>
    <script src="lib/HighCharts/code/modules/export-data.js"></script>
    <script src="lib/HighCharts/code/modules/accessibility.js"></script>


    <script>
    $(document).ready(function() {
        $(".fecha").datepicker({
            'todayHighlight': true,
            'format': "dd/mm/yyyy",
            'autoclose': true,
            'language': 'es'
            
        });
        
    })
    </script>
    <!-- Incluye complemento javascript ckeditor -->
    <script src="<?php echo $_T['basepath']; ?>assets/ckeditor/ckeditor.js"></script>
    <?php
    if(is_array($_T['bottom_js_files'])) {
        foreach($_T['bottom_js_files'] as $f) {
            if($f=='') continue;
            echo '<script src="'.$f.'"></script>';
        }
    };
    ?>
    <script>
    <?php echo $_T['bottom_jscript']; ?>
    </script>
  </body>
</html>

<?php
die();

