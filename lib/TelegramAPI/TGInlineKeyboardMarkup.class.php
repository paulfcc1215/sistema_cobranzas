<?php
class TGInlineKeyboardMarkup implements iKeyboard {
    private $buttons;
    
    function __construct() {
        $this->buttons=array();
    }

    function addLineBreak() {
        $this->buttons[]='n';
    }
    
    function addURLButton($label,$url) {
        $btn=array(
            'text'=>$label,
            'url'=>$url
        );
        $this->buttons[]=$btn;
    }

    function addCallBackButton($label,$data=null) {
        if(is_null($data)) $data=$label;
        $btn=array(
            'text'=>$label,
            'callback_data'=>$data
        );
        $this->buttons[]=$btn;
    }
    
    function addSwitchInlineButton($label,$string) {
        throw new Exception('Not Implemented');
    }
    function addSwitchInlineCurrentChatButton($label,$string) {
        throw new Exception('Not Implemented');
    }
    function addCallbackGameButton($label,$callback_game) {
        throw new Exception('Not Implemented');
    }
    function addPayButton($label) {
        throw new Exception('Not Implemented');
    }
    
    function get() {
        $aux=array();
        $ptr=0;
        foreach($this->buttons as $b) {
            if(is_string($b) && $b=='n') {
                $ptr++;
            }else{
                $aux[$ptr][]=$b;
            }
        }
        $ret=array(
            'inline_keyboard'=>$aux
        );
        return json_encode($ret);
    }
    
    
    
    
    
}