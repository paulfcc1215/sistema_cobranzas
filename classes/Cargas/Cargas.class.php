<?php
class Cargas {
	function stage1($id_proceso,$file_path,$sp) {
		$db=DB::getInstance();
		if(!$db->isInTransaction()) throw new Exception('Debe encontrarse en una transacción para hacer la carga');
		$original_search_path=$db->query('SHOW SEARCH_PATH')->current()['search_path'];
		$db->query('SET SEARCH_PATH TO \'upload_stage_1\'');

		
		// cargamos data bruta
		$file_data=file_get_contents($file_path);
        if(!mb_check_encoding($file_data,'UTF-8'))
                throw new Exception('El archivo debe estar codificado en UTF-8');
		
		
		
		// abrimos archivo
		$csv=new Helpers_CSV($file_path);
		// creamos tabla temporal
		$db->query('DROP TABLE IF EXISTS "upload"');
		
		$uid=uniqid();
		$db->query('CREATE SEQUENCE seq_'.$uid);
		
		$query='CREATE /*TEMPORARY*/ TABLE upload (';
		foreach($csv->getHeader() as $h) {
			$aux[]='"'.$h.'" text';
		}
		$aux[]='__id INT PRIMARY KEY DEFAULT nextval(\'seq_'.$uid.'\')';
		$aux[]='__uid_carga text';
		$query.=implode(',',$aux);
		$query.=')';
		$db->query($query);
		
		
		chmod($file_path,0666);
		// cargamos la data
		$db->query('COPY upload ("'.implode('","',$csv->getHeader()).'") FROM \''.$file_path.'\'');

		$db->query('ALTER TABLE upload ALTER COLUMN __id SET DEFAULT NULL');
		$db->query('UPDATE upload SET __uid_carga=\''.$uid.'\'');
		
		$db->query('DROP SEQUENCE seq_'.$uid);
		// eliminamos la cabecera
		$db->query('DELETE FROM upload WHERE __id=1');
		
		// llamamos al stored procedure
		try {
		$sp_res=$db->query('SELECT '.$sp.'()');
		}catch(Exception $e) {
			if($db->getNotice()===false || $db->getNotice()=='')
				throw new Exception($e->getMessage());
			throw new Exception('El stored procedure arrojó un error:<br><br>'.$db->getNotice());
		}
		$sp_res=$sp_res->current();
		
		$db->commit();
		
		// almacenar en tabla temporal
		
		// almacenar archivo original
		
		die();
	}
}