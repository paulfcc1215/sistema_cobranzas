<?php
class UIComponents_ColorPicker extends UIComponents_Abstract {
    public function __construct() {
        parent::__construct();
        $this->_common_name='Texto Selector de Color';
        $this->_common_description='Campo para elegir colores';
        $this->_component_type='Básico';
    }
    
    public function draw() {
        $aux=array();
        if(!array_key_exists('class',$this->_properties)) $this->_properties['class']='jscolor';
        foreach($this->_properties as $k=>$v) {
            if($k=='_component_type') continue;
            if($k=='class' && strpos($v,'jscolor')===false) $v.=' jscolor';
            $aux[]=$k.'="'.$v.'"';
        }
        
        if(!empty($aux)) {
            $aux=' '.implode(' ',$aux);
        }else{
            $aux='';
        }
        
        $html='<input type="text" readonly="true"'.$aux.'>';
        $this->_buffer[]=$html;
        return parent::draw();
    }
}