<?php
require dirname(__FILE__).'/PortoaguasFTP.class.php';

$PA = new PortoaguasFTP('1791967437','Recapp7437');


switch($_GET['step']) {
	case '2':
		$nowDateTime = new DateTime();
		$yesterdayDateTime = new DateTime();
		$yesterdayDateTime->sub(new DateInterval('P1D'));
		
		$time = microtime(true);
		$tmpUid = 't'.uniqid();
		
		$PA->login();
		$data = utf8_encode($PA->download(base64_decode($_GET['f'])));
		header('Content-Type: application/octect-stream');
		header('Content-Disposition: Attachment; filename="'.basename(base64_decode($_GET['f'])).'"');
		echo $data;
		die();
		
		//file_put_contents('/tmp/fer.bin',utf8_encode($PA->download(base64_decode($_GET['f']))));
		//$data = (file_get_contents('/tmp/fer.bin'));
	break;
	
	default:
		$PA->login();
		$files = $PA->browse('Recapp/Home');
		



		$_T['maincontent'].='
		Seleccione el archivo a procesar:
		<ul>
		';
		foreach($files as $f) {
			$_T['maincontent'].='<li><a href="?mod='.$_GET['mod'].'&step=2&f='.base64_encode($f).'">'.$f.'</li>';
		}
		$_T['maincontent'].='
		</ul>
		';
		
	break;
}