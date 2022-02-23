<?php
require '../config.php';
$odbc_geco = new ODBC('Gecko','sgecoaccess','Sg2019');

$ret = array();
switch ($_POST['accion']) {
	case 'getCargas':
		$q='SELECT DISTINCT(d.fecha_carga), count(*) AS numRegistros FROM dbo.Deudores d JOIN Creditos cr on(cr.id_deudor=d.id_deudor) WHERE cr.cedente=\''.$_POST['cedente'].'\' GROUP BY d.fecha_carga ORDER BY 1 DESC';
		foreach ($odbc_geco->query($q) as $value) {
			$ret[$value['fecha_carga']]=$value['numRegistros'];
		}
	break;
	
	case 'getNumRegistros':
		$q='SELECT count(*) as registros FROM(SELECT DISTINCT cr.numero_operacion,cr.saldo FROM dbo.Deudores d JOIN Creditos cr on(cr.id_deudor=d.id_deudor) WHERE cr.cedente=\''.$_POST['cedente'].'\' AND d.FECHA_CARGA=\''.$_POST['carga'].'\') AS t1';
		foreach ($odbc_geco->query($q) as $value) {
			$ret[]=$value['registros'];
		}
	break;
}
echo json_encode($ret);
