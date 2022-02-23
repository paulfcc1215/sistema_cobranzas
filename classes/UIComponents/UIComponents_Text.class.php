<?php
class UIComponents_Text extends UIComponents_Abstract {
    public function __construct() {
        parent::__construct();
        $this->_common_name='Texto Simple';
        $this->_common_description='Caja donde se puede ingresar cualquier texto';
        $this->_component_type='Básico';
        $this->_custom_properties=array('solo_lectura','valor');
    }
    
    public function configGUI($step,$state) {
        switch($step) {
            case '2':
                try {
                    $html='
                    <u><b>Paso 1/2</b></u>
                    <br>
                    <input type="hidden" name="finished" value="1">
                    <input type="hidden" name="solo_lectura" value="'.$state['solo_lectura'].'">
                    <input type="hidden" name="valor" value="'.$state['valor'].'">
                    ';
                    if($state['solo_lectura']=='1') {
                        $html.='<b>Solo lectura</b>: SI';
                    }else{
                        $html.='<b>Solo lectura</b>: NO';
                    }
                    $html.='
                    <br>
                    Contenido por defecto: "'.$state['valor'].'"
                    ';
                    return $html;
                }catch(Exception $e) {
                    $html='<b style="color:red;">'.$e->getMessage().'</b>';
                    goto lbl_default;
                    
                }
            break;
            default:
                lbl_default:
                try {
                    $html='
                    <u><b>Paso 1/2</b></u>
                    <br>
                    <input type="hidden" name="step" value="2">
                    <input type="checkbox" name="solo_lectura" value="1"> Solo lectura (no se puede cambiar el contenido)
                    <br>
                    Contenido por defecto:
                    <br>
                    <input type="text" name="valor">
                    <br><br>
                    <button type="button" onclick="'.$state['callback'].'">Finalizar</button>
                    ';
                    return $html;
                }catch(Exception $e) {
                    $html='<b style="color:red;">'.$e->getMessage().'</b>';
                    goto lbl_default;
                    
                }
            break;
        }            
    }
    
    public function draw() {
        foreach($this->_properties as $k=>$v) {
            $aux[]=$k.'="'.$v.'"';
        }
        $html='<input type="text"';
        if(!empty($aux)) $html.=' '.implode(' ',$aux);
        if($this->_custom_properties_values['solo_lectura']=='1') $html.=' readonly="1"';
        if(($this->value)=='' && $this->_custom_properties_values['valor']!='') $html.=' value="'.$this->_custom_properties_values['valor'].'"';
        $html.='>';
        $this->_buffer[]=$html;
        return parent::draw();
    }
}