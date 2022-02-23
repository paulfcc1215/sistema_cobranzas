<?php
class TGReplyKeyboardRemove implements iKeyboard {
    private $selective;
    
    function __construct($selective=false) {
        $this->selective=$selective;
    }
    
    function get() {
        $ret=array(
            'remove_keyboard'=>true,
            'selective'=>$this->selective,
        );
        return json_encode($ret);
    }
    
}