<?php
class UIComponents_BSToggle extends UIComponents_Abstract {
    private static $commons_drawn=false;
    public function __construct() {
        parent::__construct();
        $this->_custom_properties=array('text_on','text_off');
        $this->_common_name='Checkbox tipo toggle bootstrap';
        $this->_common_description='Checkbox que permite encendido o apagado, estilizado con bootstrap';
        $this->_component_type='Básico';
    }
    
    public function draw() {
        if(!self::$commons_drawn) {
            $this->_buffer[]='<style>';
            $this->_buffer[]='
                .'.get_class($this).'_switch input { 
                    display:none;
                }
                .'.get_class($this).'_switch {
                    display:inline-block;
                    width:50px;
                    height:20px;
                    margin:8px;
                    transform:translateY(20%);
                    position:relative;
                }

                .'.get_class($this).'_inner_text {
                    margin-left: 5px;
                }
                
                .'.get_class($this).'_slider {
                    position:absolute;
                    top:0;
                    bottom:0;
                    left:0;
                    right:0;
                    border-radius:30px;
                    box-shadow:0 0 0 2px #777, 0 0 4px #777;
                    cursor:pointer;
                    border:4px solid transparent;
                    overflow:hidden;
                     transition:.4s;
                }
                .'.get_class($this).'_slider:before {
                    position:absolute;
                    content:"";
                    width:100%;
                    height:100%;
                    background:#777;
                    border-radius:30px;
                    transform:translateX(-30px);
                    transition:.4s;
                }

                input:checked + .'.get_class($this).'_slider:before {
                    transform:translateX(30px);
                    background:limeGreen;
                }
                input:checked + .'.get_class($this).'_slider {
                    box-shadow:0 0 0 2px limeGreen,0 0 2px limeGreen;
                }
                .'.get_class($this).'_inner_text {
                    position: relative;
                    top: -9px;
                    left: -8px;
                    text-shadow: 1px 1px #ccc;
                }
                
                
            ';
            $this->_buffer[]='</style>';
            $this->_buffer[]='<script>';
            if($this->getCustomProp('text_on')!='' && $this->getCustomProp('text_off')!='') {
                $this->_buffer[]='
                $(document).ready(function() {
                    $(".'.get_class($this).'_switch input").on("change",function(e) {
                        var component_id=($(e.target).prop("id")).split("_")[0];
                        var label=$("#"+component_id+"_text");
                        if($(e.target).prop("checked")) {
                            label.html($(e.target).data("text_on"));
                        }else{
                            label.html($(e.target).data("text_off"));
                        }
                    });
                });
                ';
            }
            $this->_buffer[]='</script>';
        }
        $this->_buffer[]='<div>';
        $this->_buffer[]='<label class="'.get_class($this).'_switch">';
        $html='<input type="checkbox"'.$this->genTagAttributes().' id=\'[[%id%]]_input\' data-text_on="'.$this->getCustomProp('text_on').'" data-text_off="'.$this->getCustomProp('text_off').'">';
        $this->_buffer[]=$html;
        $this->_buffer[]='<span class="'.get_class($this).'_slider"></span>';
        $this->_buffer[]='</label>';
        $this->_buffer[]='<span class="'.get_class($this).'_inner_text" id="[[%id%]]_text">';
        if(array_key_exists('checked',$this->_properties)) {
            if($this->getCustomProp('text_on')!='') {
                $this->_buffer[]=$this->getCustomProp('text_on');
            }
        }else{
            if($this->getCustomProp('text_off')!='') {
                $this->_buffer[]=$this->getCustomProp('text_off');
            }
        }
        $this->_buffer[]='</span>';
        $this->_buffer[]='</div>';
        return parent::draw();
    }
}