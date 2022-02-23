<?php
class UIComponents_Form extends UIComponents_Container {
    public function __construct() {
        parent::__construct();
        $this->_custom_properties=array('error');
        $this->_common_name='Formulario';
        $this->_common_description='Formulario con tag html "form"';
        $this->_component_type='Interno';
    }
    
    public function draw() {
        $aux=array();
        foreach($this->_properties as $k=>$v) {
            $aux[]=$k.'="'.$v.'"';
        }
        if(!empty($aux)) {
            $this->_buffer[]='<form '.implode(' ',$aux).'>';
        }else{
            $this->_buffer[]='<form>';
        }
        if($this->getCustomProp('error')!='') {
            $this->_buffer[]='<h2 style="color: maroon; font-weight: bold;">'.$this->getCustomProp('error').'</h2>';
        }
        parent::draw();
        $this->_buffer[]='</form>';
        return implode("\r\n",$this->_buffer);
    }
    
}