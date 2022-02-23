<?php
class Helpers_CSV_Writer {
    private $filename;
    private $fhdl;
    private $charset;
    private $new_line;
    private $remove_new_lines;
    private $separator;
    private $replace_separator;
    private $text_qualifier;
    private $replace_text_qualifier;
    
    function __construct(
        $filename=null,
        $charset='utf-8',
        $new_line="\x0d\x0a",
        $remove_new_lines=true,
        $separator="\t",
        $replace_separator=' ',
        $text_qualifier='"',
        $replace_text_qualifier=false
    ) {
        $this->new_line=$new_line;
        $this->remove_new_lines=$remove_new_lines;
        $this->separator=$separator;
        $this->replace_separator=$replace_separator;
        $this->text_qualifier=$text_qualifier;
        $this->replace_text_qualifier=$replace_text_qualifier;
        
        $detect_order=array(
            'ASCII',
            //'Windows-1251',
            //'Windows-1252',
            //'ISO-8859-2',
            'UTF-8'
        );
        mb_detect_order($detect_order);
        
        if(!is_null($filename)) {
            $this->filename=$filename;
            if(file_exists($filename)){
                throw new Exception('El archivo "'.$filename.'" ya existe');
            }
            if(!is_writeable($filename)){
                throw new Exception('El archivo "'.$filename.'" no puede ser escrito');
            }
        }else{
            $aux=uniqid();
            $aux=substr($aux,strlen($aux)-4);
            $this->filename=tempnam('/tmp',$aux);
        }
        
        $this->fhdl=fopen($this->filename,'w+b');
        if(!$this->fhdl)
            throw new Exception('Error al crear el archivo "'.$this->filename.'"');
        fclose($this->fhdl);
        
        $charset=strtolower($charset);
        if($charset=='ansi') $charset='iso-8859-1';
        if($charset=='ascii') $charset='iso-8859-1';
        
        if(!in_array($charset,array('utf-8','ascii','iso-8859-1')))
            throw new Exception(__METHOD__.' - Solo se soportan los juegos de caracteres UTF-8 y ISO-8859-1');
        $this->charset=$charset;
    }
    
    function setLines($data) {
        $this->data=$data;
        $this->_write();
    }
    
    private function _write() {
        $this->fhdl=fopen($this->filename,'w+b');
        if(!$this->fhdl)
            throw new Exception('Error al crear el archivo "'.$this->filename.'"');
        fseek($this->fhdl,0,SEEK_SET);
        $buffer=array();
        $count=0;
        foreach($this->data as $l) {
            // print_r($l);
            $count++;
            $final_line=array();
			/*
			$filename=null,
			$remove_new_lines=true,
			$separator="\t",
			$replace_separator=' ',
			$text_qualifier='"',
			$replace_text_qualifier='\'',
			$charset='utf-8'
			*/            
            foreach($l as &$ll) {
                // print_r($ll);
                // die();
                if($this->remove_new_lines) {
                    $ll=str_replace("\x0d\x0a",' ',$ll);
                    $ll=str_replace("\x0d",' ',$ll);
                    $ll=str_replace("\x0a",' ',$ll);
                }
                if($this->replace_separator!==false)
                    $ll=str_replace($this->separator,$this->replace_separator,$ll);
                if($this->replace_text_qualifier!==false)
                    $ll=str_replace($this->text_qualifier,$this->replace_text_qualifier,$ll);
                $ll=str_replace($this->text_qualifier,$this->text_qualifier.$this->text_qualifier,$ll);
                $ll=$this->text_qualifier.$ll.$this->text_qualifier;
                $curr_enc=strtolower(mb_detect_encoding($ll));

                if($curr_enc!=$this->charset)
                    $ll=mb_convert_encoding($ll,$this->charset,$curr_enc);
            }
            unset($ll);
            $buffer[]=implode($this->separator,$l);
            if($count==1000) {
                fwrite($this->fhdl,implode($this->new_line,$buffer).$this->new_line);
                fflush($this->fhdl);
                $buffer=array();
                $count=0;
            }
        }
        
        if(!empty($buffer)) {
            fwrite($this->fhdl,implode($this->new_line,$buffer));
            $buffer=array();
        }
        fflush($this->fhdl);
        fclose($this->fhdl);
    }
    
    function getFilePath() {
        return $this->filename;
    }
}