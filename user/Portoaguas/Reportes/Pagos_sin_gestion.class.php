<?php
class Pagos_sin_gestion implements Reporte_Interface {
	private $cache=array();

    public function getCamposRequeridos() {
		return array();
	}

	public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array()) {
		
		foreach (getProcesoByCampana(17) as $p) {
			// if ($p['status']!='1') continue;
			$procesos[$p['id_proceso']] = $p['id_proceso'] .' - '. $p['descripcion'];
		}

		switch($_get['step']) {
			case '2':

				if (empty($_post['id_proceso'])) throw new exception ('Seleccione proceso');
				
				$db = DB::getInstance();
				$reporte = 'gestion_'.strtolower($udn['udn']).'_'.date('dmY').'.txt';
				$result[$reporte][]=array(
					'ID_GESTION',
					'CUENTA',
					'FECHA DE CARGA',
					'VALOR_DEUDA_ACTUAL',
					'FECHA_DE_PAGO',
					'MONTO_PAGO'
				);
				$output=&$result[$reporte];

				$q= 'SELECT 
					c.cuenta,c.valor_actual,c.fecha_valor_actual as fecha_carga,
					ca.descripcion as carga,
					p.diferencia as valor_pago,
					p.fecha_actualizacion as fecha_pago,
					null as id_gestion
				FROM cuentas.cuenta_actualizacion p
					JOIN cuentas.cuenta c USING(id_cuenta)
					JOIN cargas.carga ca ON(ca.id_carga=c.id_carga_valor_actual)
				WHERE  
					p.tipo_actualizacion=\'PAGO\' AND 
					c.id_proceso IN('.implode(',',$_post['id_proceso']).') AND
					NOT EXISTS (SELECT id_cuenta FROM gestiones.gestion g WHERE g.id_cuenta=p.id_cuenta)';

				foreach ($db->query($q) as $row) {
					$line = array(
						// 'ID_GESTION',
						$row['id_gestion'],
						// 'CUENTA',
						$row['cuenta'],
						// 'FECHA_DE_CARGA',
						$row['fecha_carga'],
						// 'VALOR_DEUDA_ACTUAL',
						$row['valor_actual'],
						// 'FECHA_DE_PAGO',
						$row['fecha_pago'],
						// 'MONTO_PAGO'
						abs($row['valor_pago'])
					);
					$output[]=$line;
				}
				return 'file';
			break;

			default:

				$_T['maintitle']='PortoAguas - Pagos sin gestión';
				$_T['maincontent']='
				<script>

				</script>
				<form method="POST" action="?'.Helpers::arr_to_url($_get,array(),array('step'=>'2')).'">
					<b>Seleccione Proceso:</b>
					<br>
					<select name="id_proceso[]" size="12" multiple>';
					foreach($procesos as $id_p => $p) {
						$_T['maincontent'].='<option value="'.$id_p.'">'.$p.'</option>';
					}
					$_T['maincontent'].='
					</select>
					<br><br>
					<button class="btn btn-primary">Siguiente</button>
				</form>';

				return 'flow';
			break;
		}


	}

	public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array()) {

	}
}