<?php
class TGReplyKeyboardMarkup implements iKeyboard {
    private $buttons;
    private $resize_keyboard;
    private $one_time_keyboard;
    private $selective;
    
    function __construct($resize_keyboard=null,$one_time_keyboard=null,$selective=null) {
        $this->buttons=array();
        $this->resize_keyboard=true;
        $this->one_time_keyboard=true;
        $this->selective=false;
        if(!is_null($resize_keyboard)) $this->resize_keyboard=$resize_keyboard;
        if(!is_null($one_time_keyboard)) $this->one_time_keyboard=$one_time_keyboard;
        if(!is_null($selective)) $this->selective=$selective;
    }
    
    function setResizeKeyboard($resize) {
        if(!is_bool($resize)) throw new Exception(__FILE__.':'.__LINE__.' must be boolean');
        $this->resize_keyboard=$resize;
    }
    
    function setOneTimeKB($one_time) {
        if(!is_bool($one_time)) throw new Exception(__FILE__.':'.__LINE__.' must be boolean');
        $this->one_time_keyboard=$one_time;
    }
    
    function setSelective($selective) {
        if(!is_bool($selective)) throw new Exception(__FILE__.':'.__LINE__.' must be boolean');
        $this->selective=$selective;
    }
    
    function addButton($text,$request_contact=false,$request_location=false) {
        $this->buttons[]=array(
            'text'=>$text,
            'request_contact'=>$request_contact,
            'request_location'=>$request_location
        );
        return count($this->buttons)-1;
    }
    
    function addLineBreak() {
        $this->buttons[]='n';
    }
    
    
    function get() {
        $aux=array();
        $ptr=0;
        foreach($this->buttons as $v) {
            if(is_string($v)) {
                $ptr++;
            }else{
                $aux[$ptr][]=$v;
            }
        }
        
        $ret=array(
            'keyboard'=>$aux,
            'resize_keyboard'=>$this->resize_keyboard,
            'one_time_keyboard'=>$this->one_time_keyboard,
            'selective'=>$this->selective,
        );
        return json_encode($ret);
    }
    
}