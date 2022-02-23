<?php
class UIComponents_ValidacionesSimples extends UIComponents_Abstract {
    private static $commons_drawed=false;
    public function __construct() {
        parent::__construct();
        $this->_common_name='Validaciones Simples';
        $this->_custom_properties=array('validaciones','max_grupos','value','form','name');
        $this->setCustomProp('max_grupos',1);
        $this->_common_name='Creador Validaciones Simples';
        $this->_common_description='Componente para la creacion de validaciones simples - Comercial V2';
        $this->_component_type='Especializado - Comercial V2';
    }
    
    public function setMaxGrupos($cant_grupos) {
        $this->setCustomProp('max_grupos',$cant_grupos);
    }
    
    public function draw() {
        $validaciones_no_source=array();
        foreach($this->getCustomProp('validaciones') as $v) {
            unset($v['source_code']);
            $validaciones_no_source[$v['id_validacion']]=$v;
        }
        if(!self::$commons_drawed) {
            $this->_buffer[]='
            <style>
                .'.get_class($this).'_table {
                    width: 100%;
                }

                .'.get_class($this).'_table_drop_area_col {
                    width: 60%;
                }

                .'.get_class($this).'_table_droppables {
                    padding-left: 20px;
                }
                
                .'.get_class($this).'_drop_area {
                    overflow-y: scroll;
                    background-color: #cccccc;
                    height: 300px;
                    width: 100%;
                    border-radius: 5px;
                    padding: 10px;
                }
                
                .'.get_class($this).'_droppable_element {
                    border: solid 1px #ccc;
                    padding: 10px 10px 10px 10px;
                    border-radius: 5px;
                    text-align: center;
                }
                .'.get_class($this).'_droppable_element .title {
                    font-weight: bold;
                }
                
                .'.get_class($this).'_droppable_element.grupo {
                    background-color: #ABBAFF;
                }

                .'.get_class($this).'_droppable_element.disyuncion {
                    background-color: #D4AAFF;
                }

                .'.get_class($this).'_droppable_element.validacion {
                    background-color: #D4FFD4;
                }
                
                .'.get_class($this).'_droppable_element.dropped {
                    margin-top: 12px;
                }
                
                .'.get_class($this).'_del_button {
                    border: solid 1px #ccc;
                    font-weight: normal;
                    font-size: 10px;
                    width: 20px;
                    padding: 0px;
                    margin: 0px;
                    color: red;
                }
                .'.get_class($this).'_droppable_element.grupo.dropped {
                    text-align: left;
                    font-weight: bold;
                }
                .'.get_class($this).'_droppable_element.disyuncion.dropped {
                    text-align: left;
                    font-weight: bold;
                }

                .'.get_class($this).'_droppable_element.validacion.dropped {
                    text-align: left;
                    font-weight: bold;
                }
                .'.get_class($this).'_droppable_element.validacion.dropped .variables {
                    font-size: 10px;
                }
                
                .'.get_class($this).'_droppable_element.validacion.dropped .variables .var_name {
                    color: red;
                }

                .'.get_class($this).'_droppable_element.validacion.dropped .variables .var_value {
                    color: blue;
                }
            </style>
            <script>
                var __'.get_class($this).'_validaciones='.json_encode($validaciones_no_source).';
                var __'.get_class($this).'_config={};
                
                function '.get_class($this).'_catch_drop(e,drop_target,id_component) {
                    try {
                        var drop_target=$(e.target);
                        var tipo=e.dataTransfer.getData("tipo");
                        var templates={
                            "grupo":"<div class=\''.get_class($this).'_droppable_element grupo dropped\' "
                                    +"data-tipo=\'grupo\'"
                                    +"data-nombre=\'%nombre_grupo%\'"
                                    +">"
                                    +"<button type=\'button\' class=\''.get_class($this).'_del_button\' onclick=\''.get_class($this).'_del(this)\'>[X]</button>"
                                    +" Agrupación (%nombre_grupo%)"
                                    +"</div>",
                            "disyuncion":"<div class=\''.get_class($this).'_droppable_element disyuncion dropped\' "
                                    +"data-tipo=\'disyuncion\'"
                                    +">"
                                    +"<button type=\'button\' class=\''.get_class($this).'_del_button\' onclick=\''.get_class($this).'_del(this)\'>[X]</button>"
                                    +" (Grupo Disyuntivo)"
                                    +"</div>",
                            "validacion":"<div class=\''.get_class($this).'_droppable_element validacion dropped\' "
                                    +"data-tipo=\'validacion\'"
                                    +"data-id_validacion=\'%id_validacion%\'"
                                    +"%data%"
                                    +">"
                                    +"<button type=\'button\' class=\''.get_class($this).'_del_button\' onclick=\''.get_class($this).'_del(this)\'>[X]</button>"
                                    +" %nombre_validacion%"
                                    +" <div class=\'variables\'>%variables%</div>"
                                    +"</div>"
                        };
                        if(tipo=="grupo") {
                            if(drop_target.prop("id")!=id_component+"_drop_area" /* && drop_target.data("tipo")!="grupo" */) throw "No puede crear ahí un grupo";
                            
                            var droppable=$("#"+id_component+"_droppable_element_grupo");
                            var nombre=droppable.find("input").val();
                            var el=templates.grupo;
                            
                            if(nombre=="") throw "Debe indicar un nombre para el grupo";
                            
                            el=el.replace(/%nombre_grupo%/g,nombre);
                            el=$(el).get(0);
                            drop_target.append(el);
                        }else if(tipo=="disyuncion"){
                            if(drop_target.data("tipo")!="grupo") throw "No puede crear ahí un grupo disyuntivo";
                            var droppable=$("#"+id_component+"_droppable_element_disyuncion");
                            var nombre=droppable.find("input").val();
                            var el=templates.disyuncion;
                            
                            el=$(el).get(0);
                            drop_target.append(el);
                        }else if(tipo=="validacion"){
                            var droppable=$("#"+id_component+"_droppable_element_validacion");
                            var el=templates.validacion;
                            if(drop_target.data("tipo")=="validacion") drop_target=drop_target.parent();
                            if(drop_target.data("tipo")!="disyuncion") throw "No puede crear ahí una validacion";
                            var config=new Array();
                            var config_html=new Array();
                            var droppable=$("#"+id_component+"_droppable_element_validacion");
                            var i=0;
                            droppable.find("input").each(function(k,o) {
                                var cfg={
                                    "var_name": $(o).data("var"),
                                    "var_value": $(o).val()
                                };
                                if(cfg.var_value==null || cfg.var_value=="")
                                    throw "Debe llenar la información de configuración de la validación\n("+cfg.var_name+" está vacío)";
                                config.push(cfg);
                                
                                config_html.push(\'data-param_\'+i+\'_name="\'+cfg.var_name+\'" data-param_\'+i+\'_value="\'+cfg.var_value+\'"\');
                                i++;
                            });
                            var html_data=new Array();
                            var html_human_data=new Array();
                            for(var i in config) {
                                var cfg=config[i];
                                html_data.push("data-param_"+i+"_name=\'"+cfg.var_name+"\'");
                                html_data.push("data-param_"+i+"_value=\'"+cfg.var_value+"\'");
                                html_human_data.push("<span class=\'var_name\'>"+cfg.var_name+"</span> = <span class=\'var_value\'>"+cfg.var_value+"</span>");
                            }
                            el=el.replace(/%nombre_validacion%/g,droppable.find("option:selected").html());
                            el=el.replace(/%id_validacion%/g,droppable.find("select").val());
                            el=el.replace(/%variables%/g,"{"+html_human_data.join(" ")+"}");
                            el=el.replace(/%data%/g,config_html.join(" "));
                            el=$(el).get(0);
                            drop_target.append(el);
                            
                            
                        }else{
                            throw "Unknown droppable!"
                        }
                    }catch(err){
                        alert(err);
                        return false;
                    }
                }

                function '.get_class($this).'_del(btn) {
                    if(!confirm("Está seguro que desea eliminar este elemento y todos sus subelementos?")) return;
                    $(btn).parent().remove();
                }
                
                function '.get_class($this).'_gen_config_val(val_el,component_id) {
                    try {
                        var config_span=$("#"+component_id+"_config_validacion");
                        config_span.html("Generando configuracion...");
                        if(val_el.value=="") return;
                        var target_val=null;
                        for(var i in __'.get_class($this).'_validaciones) {
                            var ptr=__'.get_class($this).'_validaciones[i];
                            if(ptr.id_validacion==val_el.value) {
                                target_val=ptr;
                                break;
                            }
                        }
                        if(target_val==null) throw ("Invalid validation!");
                        if(target_val.params!=null) {
                            var splitted_params=target_val.params.split(",");
                            var html=new Array("<hr><table>");
                            for(var p in splitted_params) {
                                var param=splitted_params[p];
                                html.push("<tr>");
                                html.push("<td style=\'font-weight: bold; padding-right: 5px;\'>"+param+": </td>");
                                html.push("<td>");
                                html.push("<input type=\'text\' data-var=\'"+param+"\'>");
                                html.push("</td>");
                                html.push("</tr>");
                            }
                            html.push("</table>");
                            config_span.html(html.join(""));
                        }else{
                            config_span.html("No requiere configuracion");
                        }
                    }catch(err){
                        alert(err);
                    }
                    
                }
                
                function '.get_class($this).'_parse_validaciones(component_id) {
                    var main=$("#"+component_id+"_drop_area");
                    var parsed={};
                    main.find("div[data-tipo=\'grupo\']").each(function(k,o) {
                        var grupo=$(o);
                        parsed[grupo.data("nombre")]=new Array();
                        grupo.find("div[data-tipo=\'disyuncion\']").each(function (kk,oo) {
                            var disyuncion=$(oo);
                            var grupo_disyuncion=new Array();
                            disyuncion.find("div[data-tipo=\'validacion\']").each(function(kkk,ooo) {
                                ooo=$(ooo);
                                var v={
                                    "id_validacion":ooo.data("id_validacion"),
                                    "config":{}
                                };
                                var data=ooo.data();
                                var repeat=false;
                                var i=0;
                                while(typeof data["param_"+i+"_name"] != "undefined") {
                                    v.config[data["param_"+i+"_name"]]=data["param_"+i+"_value"];
                                    i++;
                                }
                                grupo_disyuncion.push(v);
                            });
                            parsed[grupo.data("nombre")].push(grupo_disyuncion);
                        });
                    });
                    console.log(parsed);
                    gen=parsed;
                    return parsed;
                }
                
            </script>
            ';
            self::$commons_drawed=true;
        }
        
        $this->_buffer[]='
        <script>
        __'.get_class($this).'_config.[[%id%]]={
            "maxGrupos":'.$this->getCustomProp('max_grupos').'
        };
        
        $(document).ready(function() {
            $("#'.$this->getCustomProp('form').'").on("submit",function() {
                $("#[[%id%]]_hidden").val(JSON.stringify('.get_class($this).'_parse_validaciones("[[%id%]]")));
            });
        });
        </script>
        ';
        
        $this->_buffer[]='<input type="hidden" name="'.$this->getCustomProp('name').'" id="[[%id%]]_hidden">';
        $this->_buffer[]='<table id="[[%id%]]" class="'.get_class($this).'_table" border="0">';
        $this->_buffer[]='<tr>';
        $this->_buffer[]='<td class="'.get_class($this).'_table_drop_area_col">';
        $this->_buffer[]='<div id="[[%id%]]_drop_area" ondragover="event.preventDefault();" ondrop="'.get_class($this).'_catch_drop(event,this,\'[[%id%]]\');" class="'.get_class($this).'_drop_area" ondragover="event.preventDefault();" ondrop="catchDrop(event)">';
        foreach($this->getCustomProp('value') as $nombre_grupo=>$disyunciones) {
            $this->_buffer[]='<div '
            .'class="'.get_class($this).'_droppable_element grupo dropped"'
            .'data-tipo="grupo" '
            .'data-nombre="'.$nombre_grupo.'">'
            .'<button type="button" class="'.get_class($this).'_del_button" '
            .'onclick="'.get_class($this).'_del(this)">[X]</button>'
            .'Agrupación ('.$nombre_grupo.')';
            foreach($disyunciones as $validaciones) {
                $this->_buffer[]='<div '
                .'class="'.get_class($this).'_droppable_element disyuncion dropped"  '
                .'data-tipo="disyuncion"'
                .'>'
                .'<button type="button" '
                .'class="'.get_class($this).'_del_button" '
                .'onclick="'.get_class($this).'_del(this)"'
                .'>'
                .'[X]'
                .'</button>'
                .'(Grupo Disyuntivo)';
                foreach($validaciones as $validacion) {
                    $config=array();
                    $i=0;
                    $data=array();
                    foreach($validacion['config'] as $k=>$v) {
                        $config[]='<span class="var_name">'.$k.'</span> = <span class="var_value">'.$v.'</span>';
                        $data[]='data-param_'.$i.'_name="'.$k.'" data-param_'.$i.'_value="'.$v.'"';
                        $i++;
                    }
                    
                    $this->_buffer[]='<div '
                    .'class="'.get_class($this).'_droppable_element validacion dropped" '
                    .'data-tipo="validacion" '
                    .'data-id_validacion="'.$validacion['id_validacion'].'" '
                    .implode(' ',$data)
                    .'>'
                    .'<button '
                    .'type="button" '
                    .'class="'.get_class($this).'_del_button" '
                    .'onclick="'.get_class($this).'_del(this)"'
                    .'>[X]'
                    .'</button>'
                    .$validaciones_no_source[$validacion['id_validacion']]['nombre_humano']
                    .'<div class="variables">{'
                    .implode(' ',$config)
                    .'}</div>'
                    .'</div>';
                }
                $this->_buffer[]='</div>';
            }
            $this->_buffer[]='</div>';
        }
        $this->_buffer[]='</div>';
        $this->_buffer[]='</td>';
        $this->_buffer[]='<td class="'.get_class($this).'_table_droppables" valign="top">';
        $this->_buffer[]='<div draggable="true" ondragstart="event.dataTransfer.setData(\'tipo\',\'grupo\');" class="'.get_class($this).'_droppable_element grupo" id="[[%id%]]_droppable_element_grupo"><div class="title">Grupo</div><input type="text" placeholder="Nombre del grupo..." value="General"></div>';
        $this->_buffer[]='<br>';
        $this->_buffer[]='<div draggable="true" ondragstart="event.dataTransfer.setData(\'tipo\',\'disyuncion\');" class="'.get_class($this).'_droppable_element disyuncion" id="[[%id%]]_droppable_element_disyuncion"><div class="title">Disyunción (O)</div></div>';
        $this->_buffer[]='<br>';
        $this->_buffer[]='<div draggable="true" ondragstart="event.dataTransfer.setData(\'tipo\',\'validacion\');" class="'.get_class($this).'_droppable_element validacion" id="[[%id%]]_droppable_element_validacion"><div class="title">Validación (Y)</div>';
        $this->_buffer[]='<select id="[[%id%]]_validacion_combo" onchange="'.get_class($this).'_gen_config_val(this,\'[[%id%]]\')">';
        $this->_buffer[]='<option value="">Seleccione...</option>';
        $vs=$this->getCustomProp('validaciones');
        usort($vs,function($a,$b) {
            $x=array($a['nombre_humano'],$b['nombre_humano']);
            sort($x);
            if($x[0]==$a['nombre_humano']) return 0;
            return 1;
            
        });
        foreach($vs as $v) {
            $this->_buffer[]='<option value="'.$v['id_validacion'].'">'.$v['nombre_humano'].'</option>';
        }
        $this->_buffer[]='</select>';
        $this->_buffer[]='<div id="[[%id%]]_config_validacion"></div></div>';
        $this->_buffer[]='</td>';
        $this->_buffer[]='</tr>';
        $this->_buffer[]='</table>';
        //$this->_buffer[]='<button type="button" onclick="'.get_class($this).'_parse_validaciones(\'[[%id%]]\')">parse</button>';
        return parent::draw();
        
    }
    
}