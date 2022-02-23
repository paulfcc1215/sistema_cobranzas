<?php
class UIComponents_Button extends UIComponents_Abstract {
    public function __construct() {
        parent::__construct();
        $this->_custom_properties=array('text');
        $this->_common_name='Boton';
        $this->_common_description='Boton simple con tag html "button"';
        $this->_component_type='Básico';
    }
    function draw() {
        $html='<button';
        $html.=$this->genTagAttributes();
        $html.='>';
        $html.=$this->_custom_properties_values['text'];
        $html.='</button>';
        $this->_buffer[]=$html;
        return parent::draw();
    }
    
    function __set($k,$v) {
        parent::__set($k,$v);
    }
    
}