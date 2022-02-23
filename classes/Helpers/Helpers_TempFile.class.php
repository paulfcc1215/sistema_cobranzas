<?php
class Helpers_TempFile {
    private $fhdl;
    private $path;
    private $buffer;
    private $isOpen;
    
    function __construct($replace=true,$prefix=null,$dir='/tmp') {
        if(is_null($prefix)) $prefix=uniqid();
        $this->path=tempnam($dir,$prefix);
        $this->fopen($replace);
        stream_set_write_buffer($this->fhdl,8192);
    }

    function __destruct() {
        $this->fclose();
        unlink($this->path);
        
    }
    
    function fwrite($data) {
        return fwrite($this->fhdl,$data);
    }
    
    function flush() {
        if($this->isOpen) {
            return fflush($this->fhdl);
        }
        return false;
    }
    
    function fgets($length=null) {
        return fgets($this->fhdl,$length);
    }
    
    function fgetcsv($length=null,$delimiter=';',$enclosure='"') {
        return fgetcsv($this->fhdl,$length,$delimiter,$enclosure);
    }
    
    function fputcsv($fields,$delimiter=';',$enclosure='"') {
        return fputcsv($this->fhdl,$fields,$delimiter,$enclosure);
    }
    
    function getPath() {
        return $this->path;
    }
    
    function fclose() {
        $this->flush();
        if($this->isOpen) {
            fclose($this->fhdl);
            $this->isOpen=false;
        }
    }
    
    function fopen($replace) {
        if($replace) {
            $this->fhdl=fopen($this->path,'w+b');
        }else{
            $this->fhdl=fopen($this->path,'a+b');
        }
        if(!$this->fhdl) throw new Exception('TempFile: Error al abrir el archivo!');
        chmod($this->path,0666);
        $this->isOpen=true;
    }
    
    
    
}