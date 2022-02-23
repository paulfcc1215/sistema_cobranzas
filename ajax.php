<?php
require 'config.php';
$_action=$_REQUEST['a'];
//$_post=$_POST;
//$_get=$_GET;
$_request=$_REQUEST;
unset($_POST);
unset($_GET);
unset($_REQUEST);

$db=DB::getInstance();

try {
	if(!is_readable(dirname(__FILE__).'/ajax/'.preg_replace('#[^a-zA-Z_0-9]#','',$_request['a']).'.php'))
		throw new Exception('Accion invalida');
	ob_clean();
	ob_start();
	require dirname(__FILE__).'/ajax/'.preg_replace('#[^a-zA-Z_0-9]#','',$_request['a']).'.php';
	$ret=ob_get_clean();
	$ret=array(
		'success'=>true,
		'data'=>$ret
	);
	echo json_encode($ret);
	die();
}catch(Exception $e) {
	$ret=array(
		'success'=>false,
		'error'=>$e->getMessage()
	);
	echo json_encode($ret);
	die();
}