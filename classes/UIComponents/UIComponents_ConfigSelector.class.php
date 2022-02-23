<?php
class UIComponents_ConfigSelector extends UIComponents_Abstract {
    private static $commons_drawed=false;
    public function __construct() {
        parent::__construct();
        $this->_common_name='Seleccionador de Configuraciones';
        $this->_custom_properties=array('source_data','value','form_id','hidden_target');
        $this->_common_description='Componente para la seleccion de un item y agregarlo en otro combo';
        $this->_component_type='Generico';
    }
    
    public function draw() {
        if($this->form_id=='')
            throw new Exception('Debe indicar el id de formulario en el custom property "form_id"');
        if($this->hidden_target=='')
            throw new Exception('Debe indicar el hidden_target de formulario en el custom property "hidden_target"');
        if($this->value!='') {
            $this->value=base64_decode($this->value);
            if($this->value===false)
                throw new Exception('Contenido invalido para "value"');
            $this->value=json_decode($this->value,true);
            if($this->value===false)
                throw new Exception('Contenido invalido para "value"');
        }else{
            $this->value=array();
        }
        
        $aux=array();
        foreach($this->source_data as $sd) {
            unset($sd['validacion']);
            $aux[]=$sd;
        }
        if(!self::$commons_drawed) {
            $this->_buffer[]='
            <style>
                .'.get_class($this).'_maintable {
                    border: solid 1px #ccc;
                }
                .'.get_class($this).'_maintable td {
                    padding: 10px;
                }
                
                .'.get_class($this).'_select {
                    min-width: 200px;
                    min-height: 300px;
                }
                .'.get_class($this).'_select option {
                    padding: 5px
                }
            </style>
            <script>
            var _'.get_class($this).'_params={};
            
            function '.get_class($this).'_onsrcchange(id_component) {
                var src=$("#"+id_component+"_src");
                var dst=$("#"+id_component+"_dst");
                var cfg=$("#"+id_component+"_cfg");
                var desc=$("#"+id_component+"_descripcion");
                for(var i in _'.get_class($this).'_params[id_component]) {
                    var ptr=_'.get_class($this).'_params[id_component][i];
                    if(ptr.key==src.val()) {
                        desc.html("<br>"+ptr.descripcion);
                        var html=new Array();
                        html.push("<div align=\'center\'>");
                        html.push("<b>"+ptr.key+"</b><br>");
                        html.push(ptr.config_gui);
                        html.push("<br><br>");
                        html.push("<button type=\'button\' onclick=\''.get_class($this).'_add("+\'"\'+id_component+\'"\'+")\'>Agregar</button>");
                        html.push("</div>");
                        cfg.html(html.join(""));
                    }
                }
            }
            
            function '.get_class($this).'_parsecfgform(id_component) {
                var cfg=$("#"+id_component+"_cfg");
                var aux={};
                cfg.find("input").add(cfg.find("select")).each(function(k,o) {
                    var name=o.name.replace("[]","");
                    if(o.name.includes("[]")) {
                        if(typeof(aux[name])=="undefined") aux[name]=new Array();
                        if(o.type=="checkbox")  {
                            if(o.checked) {
                                aux[name].push(o.value);
                            }
                        }else{
                            aux[name].push(o.value);
                        }
                    }else{
                        aux[name]=(o.value);
                    }
                });
                return aux["valor"];
                // return aux2;
                // return aux;
                
            }
                
            function '.get_class($this).'_add(id_component) {
                var src=$("#"+id_component+"_src");
                var dst=$("#"+id_component+"_dst");
                var cfg=$("#"+id_component+"_cfg");
                var desc=$("#"+id_component+"_descripcion");
                
                var cfg_form='.get_class($this).'_parsecfgform(id_component);
                
                var src_sel=src.find("option:selected");
                
                src_sel.data("params",btoa(JSON.stringify(cfg_form)));
                src_sel.data("name",src.val());
                dst.append(src_sel);
                
                cfg.html("");
                
                
            }
            
            function '.get_class($this).'_deletedst(id_component) {
                var src=$("#"+id_component+"_src");
                var dst=$("#"+id_component+"_dst");
                var cfg=$("#"+id_component+"_cfg");
                var desc=$("#"+id_component+"_descripcion");
                var cfg_form='.get_class($this).'_parsecfgform(id_component);
                
                var dst_sel=dst.find("option:selected");
                dst_sel.data("params","");
                dst_sel.data("name","");
                src.append(dst_sel);
                
            }

            function _'.get_class($this).'_build_cfg(id_component) {
                var dst=$("#"+id_component+"_dst");
                var parsed={};
                dst.find("option").each(function(k,o) {
                    o=$(o);
                    parsed[o.data("name")]=$.parseJSON(atob(o.data("params")));
                });
                return parsed;

            }
            </script>
            ';
            self::$commons_drawed=true;
        }
        
        $this->_buffer[]='
        <table class="'.get_class($this).'_maintable">
        <tr>
        <td colspan="3">
        <b>Descripción:</b>
        <span id="'.$this->getInternalId().'_descripcion"></span>
        </td>
        </tr>

        <tr>
        <td valign="top">
            <select class="'.get_class($this).'_select" id="'.$this->getInternalId().'_src" size="10" onchange="'.get_class($this).'_onsrcchange(\''.$this->getInternalId().'\')">
        ';
        foreach($this->source_data as $sd) {
            if(array_key_exists($sd['key'],$this->value)) continue;
            $this->_buffer[]='<option value="'.$sd['key'].'">'.$sd['nombre_humano'].'</option>';
        }
        
        $this->_buffer[]='
            </select>
        </td>
        <td>
        <span id="'.$this->getInternalId().'_cfg"></span>
        </td>
        <td valign="top">
        <select class="'.get_class($this).'_select" id="'.$this->getInternalId().'_dst" size="10" ondblclick="'.get_class($this).'_deletedst(\''.$this->getInternalId().'\')">
        ';
        foreach($this->value as $k=>$v) {
            foreach($this->source_data as $sd) {
                if($sd['key']==$k) break;
            }
            $this->_buffer[]='<option value="'.$k.'" data-name="'.$k.'" data-params="'.base64_encode(json_encode($v)).'">'.$sd['nombre_humano'].'</option>';
        }
        $this->_buffer[]='
        </select>
        <br>
        <span style="color: #9c9c9c; font-size: 9px; font-weight: bold;">* Doble click para eliminar una configuración</span>
        </td>
        </tr>
        </table>
        
        <script>
        _'.get_class($this).'_params.'.$this->getInternalId().'='.json_encode($aux).';
        
        $("#'.$this->form_id.'").on("submit",function() {
			try {
				var parsed=_'.get_class($this).'_build_cfg("'.$this->getInternalId().'");
				$("#'.$this->hidden_target.'").val(btoa(JSON.stringify(parsed)));
				return true;
			}catch(err) {
				alert("Ocurrio un error con el componente config: "+err);
			}
			
        });
        </script>
        ';
        
        
        return parent::draw();
        
    }
    
}