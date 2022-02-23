<?php
abstract class UIComponents_Abstract {
    protected $_buffer;
    protected $_properties;
    protected $_internal_id;
    protected $_custom_properties;
    protected $_custom_properties_values;
    protected $_custom_js;
    protected $_common_js;
    protected $_common_name;
    protected $_common_description;
    protected $_images_sample;
    
    private $_abstract_construct_called=false;
    public function __construct() {
        $this->_properties=array();
        $this->_internal_id='C'.uniqid();
        $this->_custom_properties=array();
        $this->_custom_properties_values=array();
        $this->_custom_js=array();
        $this->_common_js=array();
        $this->_abstract_construct_called=true;
        $this->_images_sample=array();
        
    }
    
    public function configGUI($step,$state) {
        return '
        <input type="hidden" name="finished" value="1">
        <span style="color: red;">Este componente no es configurable</span>
        ';
    }
    
    public function getImagesSample() {
        if(empty($this->_images_sample)) {
            $path=dirname(__FILE__).'/images/'.get_class($this);
            $dhdl=opendir($path);
            $ret=array();
            while($ptr=readdir($dhdl)) {
                if(!in_array(strtolower(substr($ptr,-3)),array('jpg','png'))) continue;
                $ret[]=file_get_contents($path.'/'.$ptr);
            }
            return $ret;
            
        }else{
            return $this->_images_sample;
        }
        
    }
    
    public function getInternalId() {
        return $this->_internal_id;
    }
    
    public function setCustomProp($k,$v) {
        if(!in_array($k,$this->_custom_properties))
            throw new Exception($k.' no está definido como propiedad custom en array _custom_properties');
        $this->_custom_properties_values[$k]=$v;
    }

    public function getCustomProp($k) {
        if(!in_array($k,$this->_custom_properties))
            throw new Exception($k.' no está definido como propiedad custom en array _custom_properties');
        return $this->_custom_properties_values[$k];
    }
    
    protected function doBufferReplacements() {
        foreach($this->_buffer as &$b) {
            $b=str_replace('[[%id%]]',$this->_internal_id,$b);
            unset($b);
        }
    }
    
    public function draw() {
        if(!$this->_abstract_construct_called) {
            $debug=debug_backtrace();
            throw new Exception('No se llamo correctamente al constructor de la clase abstracta - '.get_class($debug[0]['object']));
        }
        $this->doBufferReplacements();
        return implode("\r\n",$this->_buffer);
    }
    
    public function preHtml($html) {
        $aux=$this->buffer;
        $this->_buffer=array();
        if(!is_array($html)) $html=array($html);
        foreach($html as $h) {
            $this->_buffer[]=$h;
        }
        foreach($aux as $a) {
            $this->_buffer[]=$a;
        }
        
    }

    public function postHtml($html) {
        if(!is_array($html)) $html=array($html);
        foreach($html as $h) {
            $this->_buffer[]=$h;
        }
    }
    
    protected function genTagAttributes() {
        $aux=array();
        foreach($this->_properties as $k=>$v) {
            if($k=='_component_type') continue;
            $aux[]=$k.'="'.$v.'"';
        }
        if(!empty($aux)) {
            return ' '.implode(' ',$aux);
        }else{
            return '';
        }
    }
    
    public function getCommonDescription() {
        if(is_null($this->_common_description))
            throw new Exception('La descripción no fue definida por el componente');
        return $this->_common_description;
    }
    
    public function getCommonName() {
        if(is_null($this->_common_name))
            throw new Exception('El nombre comun no esta definido');
        return $this->_common_name;
    }
    
    public function getComponentType() {
        if(is_null($this->_component_type)) {
            return 'Standard';
        }
        return $this->_component_type;
    }

    
    public function __set($k,$v) {
        if(in_array($k,$this->_custom_properties)) {
            $this->_custom_properties_values[$k]=$v;
        }else{
            $this->_properties[$k]=$v;
        }
    }
    
    public function __get($k) {
        if(in_array($k,$this->_custom_properties)) {
            return $this->_custom_properties_values[$k];
        }else{
            return $this->_properties[$k];
        }
    }
    
    public function __clone() {
        do {
            $gen='C'.uniqid();
        }while($gen==$this->_internal_id);
        $this->_internal_id=$gen;
    }
    
    public function overrideTag() {
        return false;
    }
    
    
}