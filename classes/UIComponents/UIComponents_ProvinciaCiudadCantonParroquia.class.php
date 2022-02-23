<?php
class UIComponents_ProvinciaCiudadCantonParroquia extends UIComponents_Abstract {
    private static $commons_drawed=false;
    private $exclude=array('UIComponents_Abstract');
    private $usable_components=array(
    );
    private $db;
    
    public function __construct() {
        parent::__construct();
        $this->db=Db::getInstance();
        $this->_custom_properties=array('name','value');
        $this->_component_type='Especializado - Pichincha';
        $this->_common_name='Desplegables Provincia/Ciudad/Canton/Parroquia';
        $this->_common_description='Componente que contiene desplegables Provincia/Ciudad/Canton/Parroquia. NOTA: Utiliza el catálogo de Pichincha.';
    }
    
    public function overrideTag() {
        return true;
    }
    
    private function getProvincias() {
        $q0=$this->db->query('SELECT * FROM catalogos_otros.bp_provincias WHERE "status"=\'1\' ORDER BY provincia ASC');
        return $q0;
    }
    
    private function getCiudades($provincia) {
        if(!preg_match('#^\d+$#',$provincia)) {
            $q0=$this->db->query('SELECT * FROM catalogos_otros.bp_provincias WHERE "status"=\'1\' AND provincia=\''.$this->db->escape($provincia).'\'');
            if($q0->numRows()==0) throw new Exception(__METHOD__.' - provincia inválida');
            $id_provincia=$q0->current()['id_provincia'];
        }else{
            $id_provincia=$provincia;
        }
        $q0=$this->db->query('SELECT * FROM catalogos_otros.bp_ciudades WHERE id_provincia=\''.$id_provincia.'\' AND "status"=\'1\' ORDER BY ciudad ASC');
        return $q0;
    }
    
    private function getCantones($provincia) {
        if(!preg_match('#^\d+$#',$provincia)) {
            $q0=$this->db->query('SELECT * FROM catalogos_otros.bp_provincias WHERE "status"=\'1\' AND provincia=\''.$this->db->escape($provincia).'\'');
            if($q0->numRows()==0) throw new Exception(__METHOD__.' - provincia inválida');
            $id_provincia=$q0->current()['id_provincia'];
        }else{
            $id_provincia=$provincia;
        }
        $q0=$this->db->query('SELECT * FROM catalogos_otros.bp_cantones WHERE id_provincia=\''.$id_provincia.'\' AND "status"=\'1\' ORDER BY canton ASC');
        return $q0;
    }
    
    private function getParroquias($canton) {
        if(!preg_match('#^\d+$#',$canton)) {
            $q0=$this->db->query('SELECT * FROM catalogos_otros.bp_cantones WHERE "status"=\'1\' AND canton=\''.$this->db->escape($canton).'\'');
            if($q0->numRows()==0) throw new Exception(__METHOD__.' - cantón inválido');
            $id_canton=$q0->current()['id_canton'];
        }else{
            $id_canton=$canton;
        }
        $q0=$this->db->query('SELECT * FROM catalogos_otros.bp_parroquias WHERE id_canton=\''.$this->db->escape($id_canton).'\' AND "status"=\'1\' ORDER BY parroquia ASC');
        return $q0;
    }
    
    public function setValue($provincia,$ciudad,$canton,$parroquia) {
        $vals=array(
            'provincias'=>array(
                'item'=>'provincia',
                'table'=>'catalogos_otros.bp_provincias',
                'column'=>'id_provincia',
                'ncolumn'=>'provincia',
                'value'=>$provincia,
            ),
            'ciudades'=>array(
                'item'=>'ciudad',
                'table'=>'catalogos_otros.bp_ciudades',
                'column'=>'id_ciudad',
                'ncolumn'=>'ciudad',
                'value'=>$ciudad,
            ),
            'cantones'=>array(
                'item'=>'canton',
                'table'=>'catalogos_otros.bp_cantones',
                'column'=>'id_canton',
                'ncolumn'=>'canton',
                'value'=>$canton,
            ),
            'parroquias'=>array(
                'item'=>'parroquia',
                'table'=>'catalogos_otros.bp_parroquias',
                'column'=>'id_parroquia',
                'ncolumn'=>'parroquia',
                'value'=>$parroquia,
            ),
        );
        foreach($vals as $k=>$v) {
            if(!preg_match('#^\d+$#',$v['value'])) {
                $q0=$this->db->query('SELECT '.$v['column'].' FROM '.$v['table'].' WHERE '.$v['ncolumn'].'=\''.$this->db->escape($v['value']).'\' AND "status"=\'1\'');
                $v['value']=$q0->current()[$v['column']];
            }
            $this->_custom_properties_values['value'][$v['item']]=$v['value'];
        }
    }
    
    public function draw() {
        //$this->setValue(23,57,210,1160);
        $elements=array('Provincia'=>'provincia','Ciudad'=>'ciudad','Cantón'=>'canton','Parroquia'=>'parroquia');
        if(!self::$commons_drawed) {
            $this->_buffer[]='<style>';
            $this->_buffer[]='.'.get_class($this).'_tag {
                font-weight: bold;
                padding-right: 10px;
            }
            .'.get_class($this).'_tbl td:not(:nth-child(1)) {
                padding-left: 10px;
            
            }
            ';
            $this->_buffer[]='</style>';
            $this->_buffer[]='<script>';
            $this->_buffer[]='
            function '.get_class($this).'_ajax_get(values,callback) {
                $.ajax({
                    "url":"ajax/UIComponents_ProvinciaCiudadCantonParroquia_ajax.php",
                    "data":values,
                    "success":function(d) {
                        try {
                            d=$.parseJSON(d);
                            if(!d.success) throw d.error;
                            if(callback!=null && typeof(callback)=="function") {
                                callback.apply(d.data);
                            }
                        }catch(err){
                            alert(err);
                        }
                    }
                });
            }
            function '.get_class($this).'_mkhtml(data) {
                var html=new Array("<option value=\'\'>Seleccione...</option>");
                for(var i in data) {
                    var ptr=data[i];
                    html.push("<option value=\'"+i+"\'>"+ptr+"</option>");
                }
                return html.join("\n");
            }
            
            function '.get_class($this).'_onchange(what,component_id) {
                console.log("Changed "+what+" on component id "+component_id);
                var provincia=$("#"+component_id+"_provincia");
                var ciudad=$("#"+component_id+"_ciudad");
                var canton=$("#"+component_id+"_canton");
                var parroquia=$("#"+component_id+"_parroquia");
                if(what=="provincia") {
                    ciudad.html("<option value=\'\'>Seleccione Provincia</option>");
                    canton.html("<option value=\'\'>Seleccione Provincia</option>");
                    parroquia.html("<option value=\'\'>Seleccione Cantón</option>");
                    '.get_class($this).'_ajax_get({"what":"ciudades","provincia":provincia.val()},function() {
                        ciudad.html('.get_class($this).'_mkhtml(this));
                    });
                    '.get_class($this).'_ajax_get({"what":"cantones","provincia":provincia.val()},function() {
                        canton.html('.get_class($this).'_mkhtml(this));
                    });
                }else if(what=="canton"){
                    '.get_class($this).'_ajax_get({"what":"parroquias","canton":canton.val()},function() {
                        parroquia.html('.get_class($this).'_mkhtml(this));
                    });
                }
            }
            ';
            $this->_buffer[]='</script>';
            self::$commons_drawed=true;
        }
        
        $this->_buffer[]='<table class="'.get_class($this).'_tbl">';
        $this->_buffer[]='<tr>';
        foreach($elements as $k=>$v) {
            $this->_buffer[]='<td class="'.get_class($this).'_tag">'.$k.'</td>';
        }
        $this->_buffer[]='</tr>';

        $this->_buffer[]='<tr>';
        foreach($elements as $k=>$v) {
            $this->_buffer[]='<td class="'.get_class($this).'_field">';
            $this->_buffer[]='<select '
            .'name="'.$this->_custom_properties_values['name'].'_pccp['.$v.']" '
            .'onchange="'.get_class($this).'_onchange(\''.$v.'\',\'[[%id%]]\')"'
            .'id="[[%id%]]_'.$v.'"'
            .'>';
            switch($v) {
                case 'provincia':
                    $this->_buffer[]='<option value="">Seleccione Provincia...</option>';
                    foreach($this->getProvincias() as $p) {
                        $selected=$this->_custom_properties_values['value']['provincia']==$p['id_provincia'];
                        $this->_buffer[]='<option value="'.$p['id_provincia'].'"'.($selected?' selected="selected"':'').'>'.$p['provincia'].'</option>';
                    }
                break;
                case 'ciudad':
                    if(!is_null($this->_custom_properties_values['value']['provincia'])) {
                        $this->_buffer[]='<option value="">Seleccione...</option>';
                        foreach($this->getCiudades($this->_custom_properties_values['value']['provincia']) as $c) {
                            $selected=$this->_custom_properties_values['value']['ciudad']==$c['id_ciudad'];
                            $this->_buffer[]='<option value="'.$c['id_ciudad'].'"'.($selected?' selected="selected"':'').'>'.$c['ciudad'].'</option>';
                        }
                    }else{
                        $this->_buffer[]='<option value="">Seleccione Provincia</option>';
                    }
                break;
                case 'canton':
                    if(!is_null($this->_custom_properties_values['value']['provincia'])) {
                        $this->_buffer[]='<option value="">Seleccione...</option>';
                        foreach($this->getCantones($this->_custom_properties_values['value']['provincia']) as $c) {
                            $selected=$this->_custom_properties_values['value']['canton']==$c['id_canton'];
                            $this->_buffer[]='<option value="'.$c['id_canton'].'"'.($selected?' selected="selected"':'').'>'.$c['canton'].'</option>';
                        }
                    }else{
                        $this->_buffer[]='<option value="">Seleccione Provincia</option>';
                    }
                break;
                case 'parroquia':
                    if(!is_null($this->_custom_properties_values['value']['canton'])) {
                        $this->_buffer[]='<option value="">Seleccione...</option>';
                        foreach($this->getParroquias($this->_custom_properties_values['value']['canton']) as $p) {
                            $selected=$this->_custom_properties_values['value']['canton']==$p['id_canton'];
                            $this->_buffer[]='<option value="'.$p['id_parroquia'].'"'.($selected?' selected="selected"':'').'>'.$p['parroquia'].'</option>';
                        }
                    }else{
                        $this->_buffer[]='<option value="">Seleccione Cantón</option>';
                    }
                break;
            }
            
            $this->_buffer[]='</select>';
            
        }
        $this->_buffer[]='</tr>';
        $this->_buffer[]='</table>';
        
        return parent::draw();
    }
    
    
}