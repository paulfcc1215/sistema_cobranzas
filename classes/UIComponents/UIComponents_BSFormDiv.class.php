<?php
class UIComponents_BSFormDiv extends UIComponents_Container {
    public function __construct() {
        parent::__construct();
        $this->_custom_properties=array('label','explicacion');
        $this->_common_name='Div bootstrap con etiqueta';
        $this->_common_description='Div que contiene descripcion y etiqueta con estilo bootstrap';
        $this->_component_type='Visualización';
    }
    
    public function draw() {
        $this->_buffer[]='<div class="form-group">';
        $this->_buffer[]='<label>'.$this->__get('label').'</label>';
        $this->_buffer[]='<br>';
        $this->_buffer[]='<small class="form-text text-muted">'.$this->__get('explicacion').'</small>';
        $this->_buffer[]='<br>';
        parent::draw();
        $this->_buffer[]='</div>';
        return implode("\r\n",$this->_buffer);
    }
        
}