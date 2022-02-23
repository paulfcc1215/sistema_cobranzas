<?php
class UIComponents_Fecha extends UIComponents_Abstract {
    private static $commons_drawed=false;
    private $_js_config;
    private $_dias=array('domingo','lunes','martes','miercoles','jueves','viernes','sabado');
    
    private $_formatos=array(
        'día mes año',
        'año mes día',
        'mes año día',
        'mes día año',
    );
    private $_separadores=array(
        '/'=>'/ (slash)',
        '-'=>'- (guion)',
        '.'=>'. (punto)',
    );

    public function __construct() {
        parent::__construct();
        $this->_custom_properties=array('formato','separador','js_config','dias');
        $this->_common_name='Fecha Simple';
        $this->_common_description='Caja donde se puede ingresar una fecha';
        $this->_component_type='Básico';
    }
    
    function setJsConfig($config) {
        if(!is_array($config))
            throw new Exception(__METHOD__.' - $config debe ser un array');
        $this->setCustomProp('js_config',$config);
    }
    
    private function genJsConfig() {
        $separador=$this->getCustomProp('separador');
        $defaults=array(
            'todayHighlight'=>true,
            'format'=>"dd/mm/yyyy",
            'autoclose'=>true,
            'language'=>'es',
        );
        
        $config=$this->getCustomProp('js_config');
        if(!is_null($config)) {
            foreach($config as $k=>$v) {
                if($k=='format')
                    throw new Exception(__METHOD__.' - format debe ser configurado mediate el custom property "formato"');
                $defaults[$k]=$v;
            }
        }
        $formato=$this->getCustomProp('formato');

        foreach(array(
            'día'=>'dd',
            'año'=>'yyyy',
            'mes'=>'mm',
        ) as $k=>$v) {
            $formato=str_replace($k,$v,$formato);
        }
        $formato=str_replace(' ',$separador,$formato);
        $defaults['format']=$formato;
        
        
        if(!is_null($this->getCustomProp('dias'))) {
            $dias_validos=explode(',',$this->getCustomProp('dias'));
            $dias_invalidos=array();
            foreach($this->_dias as $k=>$v) {
                if(!in_array($v,$dias_validos)) {
                    $dias_invalidos[]=$k;
                }
            }
            $defaults['daysOfWeekDisabled']=$dias_invalidos;
        }
        
        return json_encode($defaults);
    }
    
    /*
    function dias_validos($dias) {
        if(!is_array($dias))
            throw new Exception(__METHOD__.' - $dias debe ser un array con los días habilitados');
        foreach($dias as &$d) {
            $d=strtolower($d);
            $d=strtr($d,array(
                'á'=>'a',
                'é'=>'e'
            ));
            if(!in_array($d,$this->_dias))
                throw new Exception(__METHOD__.' - El día '.$d.' no es válido');
            unset($d);
        }
        $this->setCustomProp('dias_validos',$dias);
        
    }
    */
    
    public function draw() {
        if(!in_array($this->getCustomProp('formato'),$this->_formatos))
            throw new Exception (__METHOD__.' - Formato inválido o no definido');
        if(!array_key_exists($this->getCustomProp('separador'),$this->_separadores))
            throw new Exception (__METHOD__.' - Separador inválido o no definido');
        $this->autocomplete='off';
        $this->readonly='true';

        if(!self::$commons_drawed) {
            $this->_buffer[]='<script>
            $(document).ready(function() {
                var defaults='.$this->genJsConfig().';
                $(".'.get_class($this).'").each(function(k,o) {
                    var cid=$(o).data("idcomponent");
                    if(typeof '.get_class($this).'_config[cid] == "undefined") {
                    
                    }else{
                        $(o).datepicker(
                            '.get_class($this).'_config[cid]
                        );
                    }
                    
                });
            });
            ';
            $this->_buffer[]=get_class($this).'_config={};';
            $this->_buffer[]='</script>';
            self::$commons_drawed=true;
        }
        $this->_buffer[]='<script>';
        $this->_buffer[]=get_class($this).'_config.[[%id%]]='.$this->genJsConfig().';';
        $this->_buffer[]='</script>';
        
        
        $aux=array();
        if(!array_key_exists('class',$this->_properties)) $this->_properties['class']=get_class($this);
        foreach($this->_properties as $k=>$v) {
            if($k=='_component_type') continue;
            if($k=='class' && strpos($v,get_class($this))===false) $v.=' '.get_class($this);
            $aux[]=$k.'="'.$v.'"';
        }
        
        $html='<input type="text" data-idcomponent="[[%id%]]"';
        if(!empty($aux)) $html.=' '.implode(' ',$aux);
        $html.='>';
        $this->_buffer[]=$html;
        return parent::draw();
    }
    
    public function configGUI($step,$state) {
        switch($step) {
            case '2':
                try {
                    if(empty($state['dias'])) throw new Exception('Debe seleccionar al menos un día');
                    $html='
                    <u><b>Paso 2</b></u>
                    <br>
                    <input type="hidden" name="finished" value="1">
                    <input type="hidden" name="formato" value="'.$state['formato'].'">
                    <input type="hidden" name="separador" value="'.($state['separador']).'">
                    <input type="hidden" name="dias" value="'.implode(',',$state['dias']).'">
                    <b>Formato: </b>'.$state['formato'].'<br>
                    <b>Separador: </b>'.$state['separador'].'<br>
                    <b>Días: </b>'.implode(', ',$state['dias']).'
                    ';
                    return $html;
                }catch(Exception $e) {
                    $html='<b style="color:red;">'.$e->getMessage().'</b><br>';
                    goto lbl_default;
                    
                }
            break;

            default:
               lbl_default:
                $html.='
                <u><b>Paso 1</b></u>
                <br>
                <input type="hidden" name="step" value="2">
                <table>
                <tr><th style="text-align: right;">Formato:</th><td style="padding-left: 10px;"><select name="formato">';
                foreach($this->_formatos as $f) {
                    $html.='<option value="'.$f.'">'.$f.'</option>';
                }
                $html.='</select></td></tr>
                <tr><th style="text-align: right;">Separador:</th>
                <td style="padding-left: 10px;">
                <select name="separador">
                ';
                foreach($this->_separadores as $k=>$s) {
                    $html.='<option value="'.$k.'">'.$s.'</option>';
                }
                $html.='
                </select>
                </td></tr>
                <tr>
                    <th style="text-align: right;">Días:</th>
                    <td style="padding-left: 10px;">
                    <input type="checkbox" name="dias[]" value="lunes" checked="1"> Lunes
                    <input type="checkbox" name="dias[]" value="martes" checked="1"> Martes
                    <input type="checkbox" name="dias[]" value="miercoles" checked="1"> Miércoles
                    <input type="checkbox" name="dias[]" value="jueves" checked="1"> Jueves
                    <input type="checkbox" name="dias[]" value="viernes" checked="1"> Viernes
                    <input type="checkbox" name="dias[]" value="sabado" checked="1"> Sábado
                    <input type="checkbox" name="dias[]" value="domingo" checked="1"> Domingo
                    </td>
                </tr>
                </table>
                <br>
                <button type="button" onclick="'.$state['callback'].'">Siguiente</button>
                ';
                return $html;
            break;
        }
    }
}