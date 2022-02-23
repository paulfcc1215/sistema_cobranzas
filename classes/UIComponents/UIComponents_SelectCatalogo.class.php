<?php
class UIComponents_SelectCatalogo extends UIComponents_Select {
    function __construct() {
        parent::__construct();
        $this->_custom_properties=array('id_catalogo','tag','selected','agregar_opcion_vacia','version');
        $this->_common_name='Menu desplegable - Catálogo';
        $this->_common_description='Menu desplegable que permite seleccionar una opcion';
        $this->_component_type='Básico';
    }
    
    public function draw() {
        $db=Db::getInstance();
        $id_catalogo=$this->getCustomProp('id_catalogo');
        $tag=$this->getCustomProp('tag');
        if(empty($id_catalogo))
            throw new Exception(__METHOD__.' - No se definió el id_catalogo');
        if(is_null($this->_custom_properties_values['version']) || $this->_custom_properties_values['version']=='*last*') {
            $catalogo=new Catalogo($id_catalogo,$db);
        }else{
            $catalogo=new Catalogo($id_catalogo,$db,$this->_custom_properties_values['version']);
        }

        $pk=$catalogo->getPk();
        
        if(empty($tag)) {
            $this->setCustomProp('tag',$catalogo->getTag());
            $tag=$this->getCustomProp('tag');
            if(empty($tag))
                throw new Exception(__METHOD__.' - No se definió el tag');
        }
        $options=array();
        if(empty($this->getCustomProp('agregar_opcion_vacia')) || is_null($this->getCustomProp('agregar_opcion_vacia'))) {
            $options['']='Seleccione...';
        }
        foreach($catalogo->getAll() as $k=>$v) {
            $options[$v[$pk]]=$v[$tag];
        }
        
        
        
        $this->_buffer[]='<select'.$this->genTagAttributes().'>';
        foreach($options as $k=>$v) {
            $selected=in_array($k,$this->getCustomProp('selected'));
            $this->_buffer[]='<option value="'.$k.'"'.($selected?' selected="true"':'').'>'.$v.'</option>';
        }
        $this->_buffer[]='</select>';
        $this->doBufferReplacements();
        return implode("\r\n",$this->_buffer);
    }
    
    function configGUI($step,$state) {
        $db=Db::getInstance();
        $catalogos=Catalogo::getList($db);
        
        switch($step) {
            case '3':
                try {
                    $catalogo=new Catalogo($state['id_catalogo'],$db);
                    $versiones=Catalogo::getVersionList($state['id_catalogo'],$db);
                    if($state['version']=='*last*') {
                        $version='[Última] (Actualmente '.$versiones[array_keys($versiones)[0]]['version'].')';
                        $val_version='*last*';
                    }else{
                        $fecha=(!is_null($versiones[$state['version']]['fecha_modificacion']))?$versiones[$state['version']]['fecha_modificacion']:$versiones[$state['version']]['fecha_creacion'];
                        $fecha=date('d/m/Y',strtotime($fecha));
                        $version='Versión '.$versiones[$state['version']]['version'].' ('.$fecha.')';
                        $val_version=$versiones[$state['version']]['version'];
                    }
                    $html='
                    <u><b>Paso 3</b></u>
                    <br>
                    <input type="hidden" name="finished" value="1">
                    <input type="hidden" name="id_catalogo" value="'.$state['id_catalogo'].'">
                    <input type="hidden" name="nombre_catalogo" value="'.($catalogo->nombre).'">
                    <input type="hidden" name="version" value="'.($val_version).'">
                    <b>Id Catálogo: </b>'.$state['id_catalogo'].'<br>
                    <b>Nombre Catálogo: </b>'.$catalogo->nombre.'<br>
                    <b>Version:</b> '.$version.'
                    
                    ';
                    return $html;
                }catch(Exception $e) {
                    $html='<b style="color:red;">'.$e->getMessage().'</b>';
                    goto lbl_default;
                    
                }
            break;
            
            case '2':
                try {
                    $catalogo=new Catalogo($state['id_catalogo'],$db);
                    $versiones=Catalogo::getVersionList($state['id_catalogo'],$db);
                    $html='
                    <u><b>Paso 2/3</b></u>
                    <br>
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="id_catalogo" value="'.$state['id_catalogo'].'">
                    <input type="hidden" name="nombre_catalogo" value="'.($catalogo->nombre).'">
                    <b>Id Catálogo: </b>'.$state['id_catalogo'].'<br>
                    <b>Nombre Catálogo: </b>'.$catalogo->nombre.'<br>
                    <b>Version:</b>
                    <select name="version">
                    <option value="*last*">Última Versión (Actualmente Versión '.$versiones[array_keys($versiones)[0]]['version'].')</option>
                    ';
                    foreach($versiones as $v) {
                        $fecha=(!is_null($v['fecha_modificacion']))?$v['fecha_modificacion']:$v['fecha_creacion'];
                        $fecha=date('d/m/Y',strtotime($fecha));
                        $html.='<option value="'.$v['id_catalogo_version'].'">Versión '.$v['version'].' - '.$fecha.'</option>';
                    }
                    $html.='
                    </select>
                    <button type="button" onclick="'.$state['callback'].'">Finalizar</button>
                    ';
                    return $html;
                }catch(Exception $e) {
                    $html='<b style="color:red;">'.$e->getMessage().'</b>';
                    goto lbl_default;
                    
                }
            break;
            
            default:
                lbl_default:
                $html.='
                <u><b>Paso 1/3</b></u>
                <br>
                <input type="hidden" name="step" value="2">
                <table>
                <tr><th style="text-align: right;">Catálogo:</th><td style="padding-left: 10px;"><select name="id_catalogo">';
                foreach($catalogos as $grupo=>$cc) {
                    $html.='<optgroup label="'.$grupo.'">';
                    foreach($cc as $c) {
                        if($c['status']!='1') continue;
                        $html.='<option value="'.$c['id_catalogo'].'">'.$c['nombre'].'</option>';
                    }
                    $html.='</optgroup>';
                }
                $html.='</select></td></tr>
                </table>
                <button type="button" onclick="'.$state['callback'].'">Siguiente</button>
                ';
                return $html;
            break;
        }
    }
    
    public function __set($k,$v) {
        if($k=='selected') {
            if(!is_array($v)) $v=array($v);
        }
        parent::__set($k,$v);
    }
    
}