<?php
	require '../config.php';
	try {

		$db = DB::getInstance();
		$q='SELECT * FROM campanas.proceso WHERE id_campana='.$_POST['id_campana'].' ORDER BY id_proceso DESC';
		$q0 = $db->query($q);

	}catch(Exception $e) {
		throw new Exception($e->getMessage());
	}

	echo json_encode($db->fetchAll($q0));
	die();