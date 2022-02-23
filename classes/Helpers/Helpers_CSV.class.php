<?php
class Helpers_CSV implements Iterator {
    private $fhdl;
    private $file_path;
    private $header;
    private $separator;
    private $text_qualifier;
    private $new_line;
    private $current_line;
    private $line_no;
    private $debug=false;
	private $withHeader=true;
    
    function __construct($file_path,$separator="\t",$text_qualifier='"',$new_line="\r\n") {
        if(!$this->fhdl=fopen($file_path,'rb'))
            throw new Exception('Helpers_CSV::__construct - no se pudo abrir archivo "'.$file_path.'"');
        $this->file_path=$file_path;
        $this->separator=$separator;
        $this->text_qualifier=$text_qualifier;
        $this->new_line=$new_line;
        $this->header=fgetcsv($this->fhdl,null,$this->separator,$this->text_qualifier);
    }
	
	public function setWithHeader($withHeader) {
		$this->withHeader=$withHeader;
	}

    public function current () {
        return $this->current_line;
    }
    public function key () {
        return $this->line_no;
    }
    public function next () {
        do {
            $ok=true;
            $this->current_line=array();
            $line=fgetcsv($this->fhdl,null,$this->separator,$this->text_qualifier);
            if($this->first) $this->first=false;
            foreach($this->header as $k=>$h) {
				if($this->withHeader) {
					$this->current_line[$h]=$line[$k];
				}else{
					$this->current_line[$k]=$line[$k];
				}
            }
            $this->line_no++;
            if(trim(implode('',$this->current_line))=='') {
                $ok=false;
            }
        }while(!$ok && !feof($this->fhdl));
    }
    public function rewind () {
        $this->line_no=0;
        fseek($this->fhdl,0,SEEK_SET);
        $this->next();
        $this->next();
    }
    public function valid () {
        $valid=!(trim(implode($this->current_line))=='');
        return $valid;
    }
    
    public function __destruct() {
        fclose($this->fhdl);
    }
    public function getHeader() {
        return $this->header;
    }
    
}