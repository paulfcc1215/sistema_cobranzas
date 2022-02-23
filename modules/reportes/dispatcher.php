<?php

    if(!Auth::hasPrivileges('AUTH_REPORTES_INDEX')) throw new Exception('No Autorizado - AUTH_REPORTES_INDEX');

    try {
        $db=DB::getInstance();
        $query='SELECT r.*
        FROM "public".reportes r
        WHERE r.id_reporte=\''.$db->escape($_GET['id_reporte']).'\'';

        $q0=$db->query($query);
        if($db->numRows($q0)!=1)
            throw new Exception('El reporte indicado no existe');
        $reporte=$db->fetchOne($q0);
        
        if($reporte['status']!='1')
            throw new Exception('El reporte está deshabilitado');

        if(!file_exists(_BASE_USER_PATH.'/'.$reporte['archivo'].'.class.php'))
            throw new Exception('Archivo del reporte "'.$reporte['archivo'].'" no existe');
        
        require(_BASE_USER_PATH.'/'.$reporte['archivo'].'.class.php');
        
        $_reporte=basename($reporte['archivo']);
        if(!class_exists($_reporte))
            throw new Exception('La clase "'.$_reporte.'" no está definida (La clase en el archivo "'.$reporte['archivo'].'" debe llamarse "'.$_reporte.'"');
        $_reporte=new $_reporte();
        $interfaces=class_implements($_reporte);
        if(!in_array('Reporte_Interface',$interfaces))
            throw new Exception('Toda clase de reportes debe implementar la interfaz Reporte_Interface');
        
        
        
        $_T['maintitle']='Reportes - '.$reporte['nombre_reporte'];
        
        
        $_get=$_GET;
        $_post=$_POST;

        $_GET=array();
        $_POST=array();

        // verificamos si tenemos el metodo de validacion del reporte
        // ejecutamos el reportes
        $uid='trx'.uniqid();
        Log::addLog('REPORTES_EXECUTE_BEGIN',__FILE__,array('GET'=>$_get,'POST'=>$_post,'trxuid'=>$uid));
        $returned=$_reporte->execute($_post,$_get,$result,$additional_data,$_T,$execute_metadata);
        Log::addLog('REPORTES_EXECUTE_END',__FILE__,array('GET'=>$_get,'POST'=>$_post,'trxuid'=>$uid));
        
        // si tiene validador, ejecutamos el validador
        if($tiene_validador) {
            $informacion_adicional='';
            Log::addLog('REPORTES_VALIDATIONS_BEGIN',__FILE__,array('GET'=>$_get,'POST'=>$_post,'trxuid'=>$uid));
            $val_ret=$_reporte->validador_default($_post,$_get,$returned,$result,$additional_data,$informacion_adicional,$execute_metadata);
            if(!is_bool($val_ret))
                throw new Exception('El método "validador_default" debe devolver un booleano');
            if($val_ret!==true) {
                Log::addLog('REPORTES_VALIDATIONS_FAILED',__FILE__,array('GET'=>$_get,'POST'=>$_post,'trxuid'=>$uid));
                $returned='flow';
                $result='<h2 style="color: red;">REPORTE NO PASA VALIDACION AUTOMATICA</h2><!-- ERROR BEGIN -->'.$informacion_adicional.'<!-- ERROR END -->';
            }else{
                Log::addLog('REPORTES_VALIDATIONS_PASSED',__FILE__,array('GET'=>$_get,'POST'=>$_post,'trxuid'=>$uid));
            }
        }else{
            Log::addLog('REPORTES_NO_VALIDATIONS',__FILE__,array('GET'=>$_get,'POST'=>$_post,'trxuid'=>$uid));
        }


        // le damos chance al reporte de que haga algo en el postExecute
        $_reporte->postExecute($returned,$_post,$_get,$result,$additional_data,$_T);
        
        switch($returned) {
            case 'file':
                if(!is_array($result))
                    throw new Exception('Para reportes tipo "file" se devuelve un ZIP al cliente. La variable $result debe contener un arreglo. Cada llave tiene el nombre del archivo en el ZIP y el valor será su contenido.');
                    
                $tmp_file=tempnam('/tmp','rpt');
                $zip=new ZipArchive();
                if(!$zip->open($tmp_file,ZipArchive::CREATE))
                    throw new Exception('Error al crear archivo ZIP');

                foreach($result as $filename=>$content) {
                    if(is_null($content)) continue;
                    if(is_array($content)) {
                        // se trata de un csv
                        if(!is_array($content[0]))
                            throw new Exception('Si el contenido de un archivo es un array, se trata de un CSV el cual debe tener en cada item un arreglo para cada linea. La cabecera será el primer item. Ejemplo:<br><br>
                            <pre>
                            Array
                            (
                                [0] => Array
                                    (
                                        [0] => nombre
                                        [1] => apellido
                                    )
                                [1] => Array
                                    (
                                        [0] => fernando
                                        [1] => fuentes
                                    )

                                [2] => Array
                                    (
                                        [0] => fernando2
                                        [1] => fuentes2
                                    )

                            )
                            </pre>
                            ');
                        $head=($content[0]);
                        $params=array(
                            'filename'=>null,                     // $filename=null,
                            'charset'=>'utf-8',                   // $charset='utf-8', (another charset is iso8869-2)
                            'new_line'=>"\x0d\x0a",               // $new_line="\x0d\x0a",
                            'remove_new_lines'=>true,             // $remove_new_lines=true,
                            'separator'=>"\t",                    // $separator="\t",
                            'replace_separator'=>' ',             // $replace_separator=' ',
                            'text_qualifier'=>'',                // $text_qualifier='"',
                            'replace_text_qualifier'=>false,      // $replace_text_qualifier=false
                        );
                        foreach($params as $k=>&$v) {
                            if($k=='filename')
                                continue;
                            if(array_key_exists($k,$additional_data))
                                $v=$additional_data[$k];
                        }
                        unset($v);
                        
                        $_csv = new ReflectionClass('Helpers_CSV_Writer');
                        $csv=$_csv->newInstanceArgs($params);
                        $csv->setLines($content);
                        if(!$zip->addFile($csv->getFilePath(),$filename))
                            throw new Exception('Error al agregar archivo "'.$filename.'" en el ZIP');
                    }else{
                        // se trata de data binaria
                        if(!$zip->addFromString($filename,$content))
                            throw new Exception('Error al agregar archivo "'.$filename.'" en el ZIP');
                    }
                }
                $zip->close();
                foreach($result as $localname=>$path) {
                    unlink($path);
                }
                if(array_keys('filename',$additional_data)) {
                    $filename=$additional_data['filename'];
                }else{
                    $filename='reporte.zip';
                }
                ignore_user_abort(true);
                header('Content-Type: application/octect-stream');
                header('Content-Disposition: Attachment; filename="'.$filename.'"');
                header('Content-Length: '.filesize($tmp_file));
                readfile($tmp_file);
                unlink($tmp_file);
                die();
            break;
            case 'file_list':
                if(!is_array($result))
                    throw new Exception('Para reportes tipo "file_list" se devuelve un ZIP al cliente. La variable $result debe contener un arreglo. Cada llave tiene el nombre del archivo en el ZIP y el valor es una ruta al archivo local. El nucleo automaticamente elimina los archivos locales luego de crear el ZIP.');
                foreach($result as $r) {
                    if(!is_readable($r))
                        throw new Exception('El archivo "'.$r.'" no existe o no se tiene privilegios para leerlo. (Las rutas deben ser absolutas)');
                }
                $tmp_file=tempnam('/tmp','rpt');
                $zip=new ZipArchive();
                if(!$zip->open($tmp_file,ZipArchive::CREATE))
                    throw new Exception('Error al crear archivo ZIP');
                foreach($result as $localname=>$path) {
                    if(!$zip->addFile($path,$localname))
                        throw new Exception('Error al agregar archivo "'.$path.'" en el ZIP');
                }
                $zip->close();
                foreach($result as $localname=>$path) {
                    unlink($path);
                }
                /*if(array_keys('filename',$additional_data)) {
                    $filename=$additional_data['filename'];
                }else{
                    $filename='reporte.zip';
                }*/
                ignore_user_abort(true);
                header('Content-Type: application/octect-stream');
                header('Content-Disposition: Attachment; filename="reporte.zip"');
                //header('Content-Length: '.filesize('reporte.zip'));
                readfile($tmp_file);
                unlink($tmp_file);
                die();
            
            break;
            
            case 'raw_output':
                echo $result;
                die();
            break;
            case 'flow':
                $_T['maincontent'].=$result;
            break;
            case 'debug':
                echo '<pre>';
                print_arr($result);
                die();
            break;
            
            default:
                throw new Exception('El método execute() del reporte '.get_class($_reporte).' debe devolver uno de los siguientes strings {file, screen_file, raw_output, flow}');
            break;
            
        }
    }catch(Exception $e) {
        $_T['maintitle']='REPORTES';
        $_T['maincontent'].='
        <h2 style="color: maroon;">'.$e->getMessage().'</h2>
        <hr>
        <a href="javascript:history.go(-1)">Regresar</a>
        ';
        
        
        
    }