<?php
class UIComponents_SelectAdder extends UIComponents_Abstract {
    private static $_common_js_written=false;
    public function __construct() {
        parent::__construct();
        $this->_custom_properties=array('name','placeholder','options','form');
        $this->_common_name='Agregar/Quitar elementos';
        $this->_common_description='Select que permite agregar o quitar elementos arbitrarios';
        $this->_component_type='Complejo';
    }
    
    
    
    public function draw() {
        if($this->getCustomProp('form')=='')
            throw new Exception('La propiedad "form" para '.__CLASS__.' no está definida');
        $text=new UIComponents_Text();
        $text->id='sa_text_'.$this->_internal_id;
        $text->placeholder=$this->_custom_properties_values['placeholder'];
        $text->style='width: 300px;';
        
        $select=new UIComponents_Select();
        $select->class='form-control';
        $select->multiple='true';
        $select->name=$this->_custom_properties_values['name'].'[]';
        $select->id='sa_select_'.$this->_internal_id;
        $select->options=$this->_custom_properties_values['options'];

        $button_add=new UIComponents_Button();
        $button_add->text='Agregar';
        $button_add->class='btn btn-success';
        $button_add->style='height: 26px; position: relative; top: -2px; padding-top: 2px;';
        $button_add->onclick='uicomponent_select_adder_add(\''.$text->id.'\',\''.$select->id.'\')';
        $button_add->type='button';
        
        $button_del=clone $button_add;
        $button_del->class='btn btn-danger';
        $button_del->text='Eliminar seleccionados';
        $button_del->style='height: 26px; position: relative; top: -2px; padding-top: 2px; margin-top: 6px;';
        $button_del->onclick='uicomponent_select_adder_del(\''.$select->id.'\')';


        $this->_buffer[]=$text->draw();
        $this->_buffer[]=$button_add->draw();
        $this->_buffer[]='<br>';
        $this->_buffer[]=$select->draw();
        $this->_buffer[]=$button_del->draw();
        
        
        $this->_buffer[]='<script>';
        if(!self::$_common_js_written) {
            $this->_buffer[]='
            function uicomponent_select_adder_add(source,dest) {
                source=$("#"+source);
                if(source.val()=="") return;
                var html="<option value=\'"+source.val()+"\'>"+source.val()+"</option>";
                $("#"+dest).append(html);
                source.val("").focus();
            }
            
            function uicomponent_select_adder_del(dest) {
                dest=$("#"+dest);
                dest.find("option:selected").each(function (k,o) {
                    $(o).remove();
                });
            }
            ';
            self::$_common_js_written=true;
        }
        
        $this->_buffer[]='
        $(document).ready(function() {
            $("#'.$this->getCustomProp('form').'").on("submit",function(e) {
                $("#'.$select->id.' option").each(function(k,o) {
                    $(o).prop("selected",true);
                });
            });
        });
        </script>';
        
        return parent::draw();
    }
    
    
}