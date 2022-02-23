<?php
class Helpers_TempFolderGC {
    private function __construct() {
        // no instanciable
    }
    
    /**
    * Elimina los archivos en la carpeta _TMP_UPLOAD_FOLDER
    * que hayan sido modificado hace mas de _TMP_MAX_TIME segundos
    */
    static function clean() {
        $dirhdl=opendir(_TMP_UPLOAD_FOLDER);
        if(!$dirhdl) return false;
        $now=time();
        while($ptr=readdir($dirhdl)) {
            if(!is_file(_TMP_UPLOAD_FOLDER.'/'.$ptr)) continue;
            if($now-filemtime(_TMP_UPLOAD_FOLDER.'/'.$ptr) > _TMP_MAX_TIME) {
                if(!defined('_GC_SKIP_UNLINK') || _GC_SKIP_UNLINK===false)
                    unlink(_TMP_UPLOAD_FOLDER.'/'.$ptr);
            }
        }
        closedir($dirhdl);
    }
}