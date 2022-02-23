<?php
class UIComponents_Select extends UIComponents_Abstract {
    function __construct() {
        parent::__construct();
        $this->_custom_properties=array('options');
        $this->_common_name='Menu desplegable';
        $this->_common_description='Menu desplegable que permite seleccionar una opcion';
        $this->_component_type='Básico';
    }
    
    function setOptions($array_values) {
        $this->__set('options',$array_values);
    }
    
    function setSelected($array_selected) {
        if(is_array($array_selected)) $array_selected=array($array_selected);
        $this->_custom_props['selected']=$array_selected;
    }
    
    public function draw() {
        $this->_buffer[]='<select'.$this->genTagAttributes().'>';
        foreach($this->_custom_properties_values['options'] as $k=>$v) {
            $this->_buffer[]='<option value="'.$k.'">'.$v.'</option>';
        }
        $this->_buffer[]='</select>';
        return parent::draw();
    }
    
    function configGUI($step,$state) {
        switch($step) {
            default:
                $html='
                <u><b>Paso 1</b></u>
                <br><br>
                <table>
                <tr><th style="text-align: right;">Fuente:</th><td style="padding-left: 10px;">Catálogo</td></tr>
                <tr><th style="text-align: right;">Catálogo:</th><td style="padding-left: 10px;"><select></select></td></tr>
                ';
                return $html;
            break;
        }
    }
    
}