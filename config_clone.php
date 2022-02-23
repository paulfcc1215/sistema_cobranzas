<?php
try {
    error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);
    set_time_limit(0);
    
	// GLOBAL CACHE
	$_CACHE=array();
    // rutas fisicas del sistema
	define ('_SIPLAM_TOKEN','15db087e23c4fb');
    define ('_BASE_SYS_PATH',dirname(__FILE__));
    define ('_BASE_USER_PATH',_BASE_SYS_PATH.'/user');
    define ('_BASE_INSTRUMENTOS_PATH',_BASE_USER_PATH.'/Instrumentos');
    define ('_BASE_REPORTES_PATH',_BASE_USER_PATH.'/Reportes');
    define ('_VOLATILE_CACHE_PATH',_BASE_SYS_PATH.'/cache/volatile');
    define ('_STATIC_CACHE_PATH',_BASE_SYS_PATH.'/cache/static');
    define ('_USE_VOLATILE_CACHE',true);
	define ('_UDN_FOLDER_TEMPLATE','classes,gui,hooks,upload_hdl');
	define ('_CAMPAIGN_FOLDER_TEMPLATE','classes,gui,hooks,upload_hdl');
	define ('_ZERO_THRESHOLD',0.000001);
    
    define ('_MANTIS_VALIDATE_BUG_ID',true);
    define ('_MANTIS_DB_HOST','10.0.210.221');
    define ('_MANTIS_DB_USER','root');
    define ('_MANTIS_DB_PW','recapt2015');
    define ('_MANTIS_DB_DBNAME','bugtracker');
	
	define('_SHOW_ACCOUNTS_ON_ALL_UDNS',true);
    
    // nombre de la sesion
    define('_SESSION_NAME','cobranza');
    // datos de la base
    define('_DB_HOST','127.0.0.1');
    define('_DB_USER','postgres');
    define('_DB_PASSWORD','postgres');  //cobranzas desde otro host
    define('_DB_DBNAME','cobranzas_clone');
    

    // schema de tablas log
    define('_DB_SCHEMA_LOG_TABLES','log');
    // duracion maxima de archivos temporales (default: 1 hora)
    define('_TMP_MAX_TIME',1*60*60);
    // carpeta temporal de subidas
    define('_TMP_UPLOAD_FOLDER',dirname(__FILE__).'/tmp');
    // separador
    define('_UPLOADS_SEPARATOR',"\t");
    define('_UPLOADS_TEXT_QUALIFIER','"');
    define('_UPLOADS_NEW_LINE',"\r\n");
    
    // encryption key
    define('_ENCRYPTION_KEY',"\x52\x73\xC1\xBA\x9A\x84\x64\x84\x9F\xD5\x9F\x53\x58\xFD\x15\x0E\x28\x0F\xA9\x11\x18\xFD\x9F\x3C\x17\xF3\xAB\x62\x68\x21\x8F\x8F\x3A\x21\xB0\x71\xCE\xEA\x33\x3E\xFA\x35\x31\x16\x85\x19\xB5\xF0\x6C\xE6\x54\x82\x13\x2C\xC2\xC6\x66\x61\x4D\x68\x04\x7A\xF6\x7B\xAA\x6D\x05\x08\xD7\xED\x6D\x50\xFE\x94\x71\x48\xD8\x41\x0F\x4E\x36\x0A\xAD\x52\xE9\xAD\x8C\x00\xF5\xA4\x23\xB2\xE7\xDC\x6F\xEA\xA5\x23\xAC\xDF\x30\xED\x5A\xA0\xA8\xD5\xC0\xF5\xAC\xA9\xA7\xAA\xF7\x68\x0E\x0F\x81\x6E\x9A\x2D\xA1\x06\x45\x70\x67\xBE\x7E\x03\xC6\xFB\x61\xE4\xF6\xB5\xC0\x49\x90\xD5\x15\x6C\x65\xC3\x34\x5B\x12\xAC\xA4\x5F\xBD\x0D\x69\x16\xF9\x24\x21\x98\x8D\x0D\xE5\xF9\xE8\x43\xD9\x09\x04\x60\x34\xAF\x4A\x6F\xA9\xFD\x91\xF6\x38\xAE\xE4\x9B\x10\x0E\xC7\xE6\x26\x4B\xB3\xE5\xCF\x9E\xD3\x6A\x41\x73\x25\xEB\x6C\x8F\xCF\x2D\x90\x3B\x1D\xD9\xFF\x4C\x46\xD8\xAC\xEB\x0B\x56\x57\xA6\x5A\x8B\x9C\x6A\xBF\x18\x71\xF2\xE9\x7C\xDB\x72\xD4\x32\x69\x1D\x64\x64\xA8\x5C\x34\x05\xE5\x78\x56\x34\x21\x7C\x0C\x42\xF2\x05\xC5\xEA\xE7\x93\xF2\xBA\xEA\x07\x7D\xFE\x16\x18");
    define('_ENCRYPTION_CIPHER_ALGORITHM','AES-256-CTR');
    
    
    if(!is_writable(_TMP_UPLOAD_FOLDER))
        throw new Exception('La carpeta temporal para subidas "'._TMP_UPLOAD_FOLDER.'" no es escribible');

        
    // hace que el garbage collector no limpie la carpeta temporal
    define('_GC_SKIP_UNLINK',false);
    
    // fecha estandard
    date_default_timezone_set('America/Guayaquil');
    // clase base de Modelo
    require dirname(__FILE__).'/classes/Modelo/AutoModel.class.php';
    require dirname(__FILE__).'/classes/Modelo/AutoModelRecord.class.php';
    
	require _BASE_SYS_PATH.'/lib/phpspreadsheet/vendor/autoload.php';
	require _BASE_SYS_PATH.'/lib/PSR16/load.php';
	
    // auto carga de clases
    spl_autoload_register(function ($class) {
        // default loader
        $base=dirname(__FILE__).'/classes';
        $subdivided_class=array(
            'subfolder'=>substr($class,0,strpos($class,'_')),
            'classname'=>substr($class,strpos($class,'_')+1),
        );
        if(
            $subdivided_class['subfolder']=='Instrumento'
            && $subdivided_class['classname']!='Abstract'
            && $subdivided_class['classname']!='Interface'
        
        ) {
            include_once _BASE_INSTRUMENTOS_PATH.'/'.$subdivided_class['classname'].'/'.$subdivided_class['subfolder'].'_'.$subdivided_class['classname'].'.class.php';
        }elseif(file_exists($base.'/'.$class.'/'.$class.'.class.php')){
            include_once $base.'/'.$class.'/'.$class.'.class.php';
        }elseif(file_exists($base.'/'.$subdivided_class['subfolder'].'/'.$class.'.class.php')){
            include_once $base.'/'.$subdivided_class['subfolder'].'/'.$class.'.class.php';
        }else{
            include_once $base.'/'.$class.'.class.php';
        }
    },false);
    
    // funciones helper globales
    require dirname(__FILE__).'/lib.php';

    // conexion a base
    DB::connect('pgsql',array(
        'host'=>_DB_HOST,
        'user'=>_DB_USER,
        'password'=>_DB_PASSWORD,
        'dbname'=>_DB_DBNAME,
    ));
    
    
    
    // servidores dragon
    $servidores_dragon=array(
        '10.0.210.201'=>array(
            'db'=>array(
                'host'=>'10.0.210.203',
                'user'=>'postgres',
                'pass'=>'orangeDragon$2017',
                'dbname'=>'dragontech',
                'port'=>'5432',
            ),
            'tel'=>array(
                'host'=>'10.0.210.202',
                'user'=>'root',
                'pass'=>'orangeDragon$2017',
            ),
            'app'=>array(
                'ssh_port'=>22,
                'host'=>'10.0.210.201',
                'user'=>'root',
                'pass'=>'orangeDragon$2017',
                'gui_user'=>'SYSTEM',
                'gui_pass'=>'SYS@2018.',
            )
        ),
        '10.0.210.204'=>array(
            'db'=>array(
                'host'=>'10.0.210.204',
                'user'=>'postgres',
                'pass'=>'orangeDragon$2017',
                'dbname'=>'dragontech',
                'port'=>'5432',
            ),
            'tel'=>array(
                'host'=>'10.0.210.204',
                'user'=>'root',
                'pass'=>'orangeDragon$2017',
            ),
            'app'=>array(
                'ssh_port'=>22,
                'host'=>'10.0.210.204',
                'user'=>'root',
                'pass'=>'orangeDragon$2017',
                'gui_user'=>'SYSTEM',
                'gui_pass'=>'SYS@2018.',
            )
        ),
    );    

    // limpiamos los temporales
    Helpers_TempFolderGC::clean();

    // detalles default de la plantilla
    $_T=array(
        'basepath'=>'template/',
        'title'=>'Cobranzas V2',
        'css_files'=>array()
    );
    $_T['css_files'][]=$_T['basepath'].'assets/custom.css';

    //$odbc_geco = new ODBC('Gecko','sgecoaccess','Sg2019');
	// Smarty
	require dirname(__FILE__).'/lib/smarty/libs/Smarty.class.php';
	$smarty=new Smarty();
	$smarty->setConfigDir(dirname(__FILE__).'/template/smarty/conf/');
	$smarty->setTemplateDir(dirname(__FILE__).'/template/smarty/tpls/');
	$smarty->setCompileDir(_TMP_UPLOAD_FOLDER.'/smarty/compile/');
	$smarty->setCacheDir(_TMP_UPLOAD_FOLDER.'/smarty/cache/');
	$smarty->debugging = false;
	$smarty->configLoad('main.conf');
	
	
	//pdfi
	require dirname(__FILE__).'/lib/fpdf/fpdf.php';
	require dirname(__FILE__).'/lib/fpdi_1_5_1/fpdi.php';
    
    
}catch(Exception $e) {
    
    echo '
    <style>
    body {
        background-color: #ccc;
        font-family: Tahoma;
    }
    
    pre {
        background-color: white;
        border-radius: 5px;
        padding: 10px;
    }
    </style>
    <h2>Excepción no controlada</h2>
    <div style="color: maroon; font-weight: bold; margin-left: 20px;">'.$e->getMessage().'</div>
    <hr>    
    <table>
    <tr>
    <td valign="top">
    Exception
    <pre>
';
    var_dump($e);
    echo '
    </pre>
    </td>
    <td valign="top">POST<pre>'.print_r($_POST,true).'</pre></td>
    <td valign="top">GET<pre>'.print_r($_GET,true).'</pre></td>
    <td valign="top">FILES<pre>'.print_r($_FILES,true).'</pre></td>
     </tr>
    </table>
    ';
    die();
}
