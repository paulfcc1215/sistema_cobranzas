<?php
class UIComponents_FormularioProductos extends UIComponents_Abstract {
    private static $commons_drawed=false;
    private $exclude=array('UIComponents_Abstract');
    private $usable_components=array(
    );
    public function __construct() {
        parent::__construct();
        $this->_custom_properties=array('form','name','value','id_producto','preview_url','capture_ctrl_z');
        $this->_common_name='Creador de Formularios Productos';
        $this->_common_description='Componente exclusivo para la creacion de formularios para cada producto - Pichinicha Comercial V2';
        $this->_component_type='Especializado - Comercial V2';
        $db=Db::getInstance();
        $q0=$db->query('SELECT "class" from formularios.componentes_utilizables');
        foreach($q0 as $c) {
            $this->usable_components[]=$c['class'];
        }
    }
    
    private function parseValue($value) {
        $html=array();
        foreach($value as $nombre_grupo=>$grupo) {
            $html[]='<div '
            .'class="UIComponents_FormularioProductos_droppable_element grupo dropped"  '
            .'data-tipo="grupo"> '
            .'<span ondblclick="'.get_class($this).'_enableTextEdit(this,\'[[%id%]]\')">'
            .$nombre_grupo
            .'</span>'
            .'<div align="right" style="float: right; font-weight: normal;">'
            .'<span onclick="'.get_class($this).'_del_droppable(\'grupo\',this.parentElement.parentElement,\'[[%id%]]\')" style="color: blue; cursor: pointer;">Eliminar</span>'
            .' | '
            .'<span onclick="'.get_class($this).'_move_droppable(\'grupo\',this.parentElement.parentElement,\'up\',\'[[%id%]]\')" style="color: blue; cursor: pointer;">Subir</span>'
            .' | '
            .'<span onclick="'.get_class($this).'_move_droppable(\'grupo\',this.parentElement.parentElement,\'down\',\'[[%id%]]\')" style="color: blue; cursor: pointer;">Bajar</span>'
            .'</div>';
            
            $num_fila=0;
            foreach($grupo as $fila) {
                $num_fila++;
                $html[]='<div '
                .'class="UIComponents_FormularioProductos_droppable_element fila dropped"'
                .'data-tipo="fila">'
                .'<div align="right" style="float: right; font-weight: normal;">'
                .'<span onclick="'.get_class($this).'_del_droppable(\'fila\',this.parentElement.parentElement,\'[[%id%]]\')" style="color: blue; cursor: pointer;">Eliminar</span>'
                .' | '
                .'<span onclick="'.get_class($this).'_move_droppable(\'fila\',this.parentElement.parentElement,\'up\',\'[[%id%]]\')" style="color: blue; cursor: pointer;">Subir</span>'
                .' | '
                .'<span onclick="'.get_class($this).'_move_droppable(\'fila\',this.parentElement.parentElement,\'down\',\'[[%id%]]\')" style="color: blue; cursor: pointer;">Bajar</span>'
                .'</div>'
                
                .'<span data-tipo="fila_titulo">'
                .'Fila '.$num_fila
                .'</span>';
                foreach($fila as $componente) {
                    $cI=new $componente['componente_class']();
                    $html[]='<div '.
                    'class="'.get_class($this).'_droppable_element componente dropped" '.
                    'data-tipo="componente"'.
                    'data-componente="'.$componente['componente_class'].'"'.
                    '>'.
                    '<div align="right" style="float: right;">'.
                    '<span onclick="'.get_class($this).'_del_droppable(\'componente\',this.parentElement.parentElement,\'[[%id%]]\')" style="color: blue; cursor: pointer;">Eliminar</span>'.
                    ' | '.
                    '<span onclick="'.get_class($this).'_move_droppable(\'componente\',this.parentElement.parentElement,\'up\',\'[[%id%]]\')" style="color: blue; cursor: pointer;">Subir</span>'.
                    ' | '.
                    '<span onclick="'.get_class($this).'_move_droppable(\'componente\',this.parentElement.parentElement,\'down\',\'[[%id%]]\')" style="color: blue; cursor: pointer;">Bajar</span>'.
                    '</div>'.
                    '<b>Campo: </b>'.
                    '<nombrecampo ondblclick="'.get_class($this).'_enableTextEdit(this,\'[[%id%]]\')">'.$componente['nombre_campo'].'</nombrecampo> '.
                    '| <b>Agrupable: </b><agrupable style="text-decoration: underline; color: blue; cursor: pointer;" onclick="'.get_class($this).'_toggle_agrupable(this,\'[[%id%]]\')">'.($componente['agrupable']=='1'?'SI':'NO').'</agrupable>'.
                    '<br> '.
                    '<b>Tipo: </b>'.$cI->getCommonName().
                    '<br> ';
                    if(!empty($componente['config'])) {
                        $html[]=
                        '<b>Configuracion:</b> '.
                        '<br> '.
                        '<div style="margin-left: 30px;"> ';
                        foreach($componente['config'] as $k=>$v) {
                            $html[]='<config name="'.$k.'" value="'.$v.'"><b>'.$k.'</b>: '.$v.'</config><br>';
                        }
                        $html[]='</div>';
                    }
                    $html[]='</div>';
                }
                $html[]='</div>';
            }
            
            
            $html[]='</div>';
        }
        
        return implode("\r\n",$html);
    }
    
    public function draw() {
        if($this->getCustomProp('id_producto')=='') throw new Exception(__METHOD__.' - id_producto no fue definido');
        if(empty($this->getCustomProp('capture_ctrl_z')) && $this->getCustomProp('capture_ctrl_z')!==false) {
            $this->setCustomProp('capture_ctrl_z',true);
        }
        $fhdl=opendir(dirname(__FILE__));
        
        while($ptr=readdir($fhdl)) {
            if(substr($ptr,-10)!='.class.php') continue;
            require_once(dirname(__FILE__).'/'.$ptr);
        }
        
        $components=array();
        foreach(get_declared_classes() as $clazz) {
            if(substr($clazz,0,12)!='UIComponents') continue;
            if($clazz==__CLASS__) continue;
            if(in_array($clazz,$this->exclude)) continue;
            $components[$clazz]=new $clazz();
        }

        if(!self::$commons_drawed) {
            // CSS STYLE
            $this->_buffer[]='
                <style>
                    .'.get_class($this).'_table {
                    width: 100%;
                }

                .'.get_class($this).'_table_drop_area_col {
                    width: 75%;
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
                    padding: 10px 10px 10px 10px;
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

                .'.get_class($this).'_droppable_element.fila {
                    background-color: #FFD47F;
                }

                .'.get_class($this).'_droppable_element.componente {
                    background-color: #D4FFD4;
                    font-weight: normal !important;
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
                .'.get_class($this).'_droppable_element.fila.dropped {
                    text-align: left;
                    font-weight: bold;
                }

                .'.get_class($this).'_droppable_element.componente.dropped {
                    text-align: left;
                    font-weight: bold;
                }
                </style>
            ';
            
            // JAVASCRIPT
            $this->_buffer[]='<script>';
            $this->_buffer[]='
            var last_selected_component=null;
            
            function '.get_class($this).'_toggle_agrupable(el,component_id) {
                el=$(el);
                if(el.html()=="SI") {
                    el.html("NO");
                }else{
                    el.html("SI");
                }
                '.get_class($this).'_save_formulario(component_id);
            }
            
            function '.get_class($this).'_del_droppable(tipo,element,component_id) {
                try {
                    var el=$(element);
                    if(confirm("Está seguro que desea eliminar el elemento seleccionado y todos sus subelementos?")) {
                        el.remove();
                        '.get_class($this).'_save_formulario(component_id);
                    }
                }catch(err) {
                    alert(err);
                    return false;
                }
            }
            
            function '.get_class($this).'_recalcular_num_filas(component_id) {
                var container=$("#"+component_id+"_drop_area");
                container.find("div[data-tipo=\'grupo\']").each(function(k,o) {
                    var num_fila=0;
                    $(o).find("div[data-tipo=\'fila\']").each(function(kk,oo) {
                        num_fila++;
                        $(oo).find("span[data-tipo=\'fila_titulo\']").html("Fila "+num_fila);
                    });
                });
            
            }
            
            function '.get_class($this).'_move_droppable(tipo,element,direction,component_id) {
                try {
                    var el=$(element);
                    if(tipo=="grupo") {
                        if(direction=="up") {
                            var drop_target=el.prev("div[data-tipo=\'grupo\']");
                            if(drop_target.length==0) throw "Este elemento ya se encuentra al principio de su contenedor";
                            drop_target.before(el.get(0));
                        }else if(direction=="down"){
                            var drop_target=el.next("div[data-tipo=\'grupo\']");
                            if(drop_target.length==0) throw "Este elemento ya se encuentra al final de su contenedor";
                            drop_target.after(el.get(0));
                        }
                    }else if(tipo=="fila") {
                        if(direction=="up") {
                            var drop_target=el.prev("div[data-tipo=\'fila\']");
                            if(drop_target.length==0) throw "Este elemento ya se encuentra al principio de su contenedor";
                            drop_target.before(el.get(0));
                        }else if(direction=="down"){
                            var drop_target=el.next("div[data-tipo=\'fila\']");
                            if(drop_target.length==0) throw "Este elemento ya se encuentra al final de su contenedor";
                            drop_target.after(el.get(0));
                        }
                    }else if(tipo=="componente") {
                        if(direction=="up") {
                            var drop_target=el.prev("div[data-tipo=\'componente\']");
                            if(drop_target.length==0) throw "Este elemento ya se encuentra al principio de su contenedor";
                            drop_target.before(el.get(0));
                        }else if(direction=="down"){
                            var drop_target=el.next("div[data-tipo=\'componente\']");
                            if(drop_target.length==0) throw "Este elemento ya se encuentra al final de su contenedor";
                            drop_target.after(el.get(0));
                        }
                    }
                    console.log("GUARDANDO!!!!!");
                    '.get_class($this).'_recalcular_num_filas(component_id);
                    '.get_class($this).'_save_formulario(component_id);
                    
                }catch(err) {
                    alert(err);
                    return;
                }
            }
            
            function '.get_class($this).'_catch_drop(event,me,producto_id,component_id) {
                try {
                    var dropped_el_tipo=event.dataTransfer.getData("tipo");
                    var dropped_el=$("#"+component_id+"_droppable_element_"+dropped_el_tipo);
                    var dropping_target=$(event.target);
                    var template={
                        "grupo":"<div "
                            +"class=\'"
                            +"'.get_class($this).'_droppable_element "
                            +"grupo dropped"
                            +"\' "
                            +"data-tipo=\'grupo\'"
                            +">"
                            +"<span ondblclick=\''.get_class($this).'_enableTextEdit(this,\'"+component_id+"\')\'>%nombre%</span>"
                            
                            +"<div align=\"right\" style=\"float: right; font-weight: normal;\">"
                            +"<span onclick=\"'.get_class($this).'_del_droppable(\'grupo\',this.parentElement.parentElement,\'[[%id%]]\')\" style=\"color: blue; cursor: pointer;\">Eliminar</span>"
                            +" | "
                            +"<span onclick=\"'.get_class($this).'_move_droppable(\'grupo\',this.parentElement.parentElement,\'up\',\'[[%id%]]\')\" style=\"color: blue; cursor: pointer;\">Subir</span>"
                            +" | "
                            +"<span onclick=\"'.get_class($this).'_move_droppable(\'grupo\',this.parentElement.parentElement,\'down\',\'[[%id%]]\')\" style=\"color: blue; cursor: pointer;\">Bajar</span>"
                            +"</div>"
                            
                            
                            +"</div>",
                        "fila":"<div "
                            +"class=\'"
                            +"'.get_class($this).'_droppable_element "
                            +"fila dropped"
                            +"\' "
                            +"data-tipo=\'fila\'"
                            +">"
                            +"<div align=\"right\" style=\"float: right; font-weight: normal;\">"
                            +"<span onclick=\"'.get_class($this).'_del_droppable(\'fila\',this.parentElement.parentElement,\'"+component_id+"\')\" style=\"color: blue; cursor: pointer;\">Eliminar</span>"
                            +" | "
                            +"<span onclick=\"'.get_class($this).'_move_droppable(\'fila\',this.parentElement.parentElement,\'up\',\'"+component_id+"\')\" style=\"color: blue; cursor: pointer;\">Subir</span>"
                            +" | "
                            +"<span onclick=\"'.get_class($this).'_move_droppable(\'fila\',this.parentElement.parentElement,\'down\',\'"+component_id+"\')\" style=\"color: blue; cursor: pointer;\">Bajar</span>"
                            +"</div>"
                            +"<span data-tipo=\'fila_titulo\'>Fila %num_fila%</span>"
                            +"</div>",
                        "componente":"<div "
                            +"class=\'"
                            +"'.get_class($this).'_droppable_element "
                            +"componente dropped"
                            +"\' "
                            +"data-tipo=\'componente\'"
                            +"data-componente=\'%componente_class%\'"
                            +">"

                            +"<div align=\'right\' style=\'float: right;\'>"
                            +"<span onclick=\"'.get_class($this).'_del_droppable(\'componente\',this.parentElement.parentElement,\'"+component_id+"\')\" style=\"color: blue; cursor: pointer;\">Eliminar</span>"
                            +" | "
                            +"<span onclick=\"'.get_class($this).'_move_droppable(\'componente\',this.parentElement.parentElement,\'up\',\'"+component_id+"\')\" style=\"color: blue; cursor: pointer;\">Subir</span>"
                            +" | "
                            +"<span onclick=\"'.get_class($this).'_move_droppable(\'componente\',this.parentElement.parentElement,\'down\',\'"+component_id+"\')\" style=\"color: blue; cursor: pointer;\">Bajar</span>"
                            +"</div>"


                            +"<b>Campo: </b><nombrecampo ondblclick=\"'.get_class($this).'_enableTextEdit(this,\'"+component_id+"\')\">%nombre_campo%</nombrecampo>"
                            +" | <b>Agrupable: </b><agrupable style=\"text-decoration: underline; color: blue; cursor: pointer;\" onclick=\"'.get_class($this).'_toggle_agrupable(this,\'"+component_id+"\')\">'.($componente['agrupable']=='1'?'SI':'NO').'</agrupable>"
                            +"<br>"
                            +"<b>Tipo: </b>%tipo_campo%<br>"
                            +"%configuracion%"
                            +"</div>"
                    };

                    var html=new Array();
                    var html_template="";
                    if(dropped_el_tipo=="grupo") {
                        if(dropping_target.prop("id")!=component_id+"_drop_area")
                            throw "Los grupos solo pueden ser creados en el area gris";
                        var nombre=dropped_el.find("input").val();
                        
                        dropping_target.find("div[data-tipo=\'grupo\']").each(function(k,o) {
                            var prev_nombre=$(o).find("span").html();
                            if(prev_nombre==nombre) throw "Ya existe un grupo con el nombre \'"+nombre+"\'";
                        });
                        
                        html_template=template.grupo;
                        html_template=html_template.replace(/%nombre%/g,nombre);
                        html.push(html_template);
                    }else if(dropped_el_tipo=="fila") {
                        if(!dropping_target.hasClass("grupo"))
                            throw "Las filas solo pueden ser agregadas dentro de los grupos";
                        var num_fila=-1;
                        html_template=template.fila;
                        num_fila=dropping_target.find("div[data-tipo=\'fila\']").length+1;
                        html_template=html_template.replace(/%num_fila%/g,num_fila);
                        html.push(html_template);
                    }else if(dropped_el_tipo=="componente") {
                        var selected_component=dropped_el.find("#[[%id%]]_componente_combo");
                        var selected_component_tag=selected_component.find("option:selected").html();
                        if(!dropping_target.hasClass("fila"))
                            throw "Las filas solo pueden ser agregadas dentro de las filas";
                        if(selected_component.val()=="") throw "Debe seleccionar un componente";
                        var config='.get_class($this).'_get_inputs(\'[[%id%]]_config_component\');
                        if(typeof config.finished == "undefined" || config.finished!=\'1\')
                            throw "Debe configurar el componente";
                        var nombre_componente=$("#[[%id%]]_componente_nombre").val();
                        if(nombre_componente=="")
                            throw "Debe indicar un nombre para el campo";
                        
                        // buscar si ya existe el nombre del campo
                        
                        html_template=template.componente;
                        
                        var html_config=new Array(
                            "<b>Configuracion:</b><br>",
                            "<div style=\'margin-left: 30px;\'>"
                        );
                        for(var i in config) {
                            if(i=="finished") continue;
                            html_config.push("<config name=\'"+i+"\' value=\'"+config[i]+"\'><b>"+i+"</b>: "+config[i]+"</config><br>");
                        }
                        html_config.push("</div>");
                        
                        html_template=html_template.replace(/%componente_class%/g,selected_component.val());
                        var config_size=0;
                        for(var i in config) {
                            if(i=="finished") continue;
                            config_size++;
                        }
                        if(config_size > 0) {
                            html_template=html_template.replace(/%configuracion%/g,html_config.join(""));
                        }else{
                            html_template=html_template.replace(/%configuracion%/g,"");
                        }
                        html_template=html_template.replace(/%nombre_campo%/g,nombre_componente);
                        html_template=html_template.replace(/%tipo_campo%/g,selected_component_tag);
                        
                        html.push(html_template);
                        
                    }else{
                        throw "Unknown droppable";
                    }
                    $(html.join("")).each(function(k,o) {
                        dropping_target.append(o);
                    });
                    
                    //'.get_class($this).'_parse_formulario("[[%id%]]");
                    '.get_class($this).'_save_formulario(component_id);
                }catch(err) {
                    alert(err);
                    return;
                }
                
                
            }
            
            ajax_save_busy=false;
            function '.get_class($this).'_save_formulario(component_id) {
                if(ajax_save_busy) return;
                ajax_save_busy=true;
                $.ajax({
                    "url":"ajax/admin_formulario_save.php",
                    "method":"POST",
                    "data": {
                        "id_producto":'.get_class($this).'_config[component_id].id_producto,
                        "content":JSON.stringify('.get_class($this).'_parse_formulario(component_id))
                    },
                    "success":function(d) {
                        try {
                            ajax_save_busy=false;
                            if(d.substr(0,1)!="1") throw d.substr(1);
                            if('.get_class($this).'_config[component_id].has_preview_url) {
                                $("#"+component_id+"_preview_iframe").get(0).src=$("#"+component_id+"_preview_iframe").get(0).src;
                                
                            }
                            console.log("Guardado OK");
                        }catch(err){
                            alert(d);
                        }
                    }
                });
            }
            
            function '.get_class($this).'_parse_formulario(component_id) {
                var container=$("#"+component_id+"_drop_area");
                var parsed={};
                var parsed_fila=new Array();
                var parsed_grupo=new Array();
                container.find("div[data-tipo=\'grupo\']").each(function(k,o) {
                    var grupo=$(o);
                    var grupo_nombre=grupo.find("span:first").html();
                    parsed_grupo=new Array();
                    grupo.find("div[data-tipo=\'fila\']").each(function(kk,oo) {
                        var fila=$(oo);
                        parsed_fila=new Array();
                        fila.find("div[data-tipo=\'componente\']").each(function(kkk,ooo) {
                            var componente=$(ooo);
                            var parsed_component={
                                "componente_class":componente.data("componente"),
                                "nombre_campo":componente.find("nombrecampo").html(),
                                "agrupable":componente.find("agrupable").html(),
                                "config":{}
                            };
                            componente.find("config").each(function(kkkk,oooo) {
                                parsed_component.config[$(oooo).attr("name")]=$(oooo).attr("value");
                            });
                            parsed_fila.push(parsed_component);
                        });
                        parsed_grupo.push(parsed_fila);
                    });
                    parsed[grupo_nombre]=parsed_grupo;
                });
                return parsed;
            }
            
            function '.get_class($this).'_enableTextEdit(el,component_id) {
                el=$(el);
                el.html("<input type=\'text\' value=\'"+el.html()+"\' style=\'width: 200px;\'>");
                var input=el.find("input");
                input.focus();
                input.on("blur",function(e) {
                    el.html(input.val());
                    '.get_class($this).'_save_formulario(component_id);
                });
                
            }
            
            function '.get_class($this).'_get_inputs(id_container) {
                var container=$("#"+id_container);
                if(container.length==0) return;
                var state={};
                container.find("select").add(container.find("input")).each(function(k,o) {
                    if(o.type=="checkbox" && !$(o).prop("checked")) return true;
                    
                    if(typeof(state[o.name])=="undefined") {
                        state[o.name]=o.value;
                    }else{
                        if(typeof(state[o.name])=="string") {
                            var aux=state[o.name];
                            state[o.name]=new Array(state[o.name]);
                        }
                        state[o.name].push(o.value);
                    }
                });
                console.log(state);
                return state;
                
                
            }
            
            function '.get_class($this).'_config_component(component_id) {
                var el=$("#[[%id%]]_componente_combo");
                var container=$("#"+component_id+"_config_component");
                if(el.val()=="") {
                    container.html("");
                    return;
                }
                if(last_selected_component==null || last_selected_component!=el.val()) {
                    container.html("");
                    last_selected_component=el.val();
                }
                var state={
                    "component":el.val(),
                    "callback":"'.get_class($this).'_config_component(\'"+component_id+"\')"
                };
                var aux='.get_class($this).'_get_inputs(component_id+"_config_component");
                for(var i in aux) {
                    state[i]=aux[i];
                }
                container.html("");
                $.ajax({
                    "url":"ajax/component_config.php",
                    "method":"POST",
                    "data": state,
                    "success":function(d) {
                        container.html(d);
                    }
                });
            }
            
            var '.get_class($this).'_config={
            };
            ';
            
            if($this->getCustomProp('capture_ctrl_z')) {
                $this->_buffer[]='
                    $(document).keydown(function(e){
                      if( e.which === 89 && e.ctrlKey ){
                         $.ajax({
                             "url":"ajax/admin_formulario_undo.php?id='.$_GET['id'].'&a=r",
                             "success":function(d) {
                                 window.location=window.location;
                             }
                         });
                      }
                      else if( e.which === 90 && e.ctrlKey ){
                         $.ajax({
                             "url":"ajax/admin_formulario_undo.php?id='.$_GET['id'].'&a=u",
                             "success":function(d) {
                                 window.location=window.location;
                             }
                         });
                      }          
                    });                
                ';
            }
            $this->_buffer[]='</script>';
            self::$commons_drawed=true;
        }
        // FIN DE DIBUJADO COMMONS
        
        $this->_buffer[]='<script>';
        $aux=get_class($this).'_config[\''.$this->getInternalId().'\']={
        ';
        if(!is_null($this->getCustomProp('preview_url'))) {
            $aux.='"has_preview_url":true';
        }else{
            $aux.='"has_preview_url":true';
        }
        $aux.='
        ,
        "id_producto":"'.$this->getCustomProp('id_producto').'"
        }';
        $this->_buffer[]=$aux;
        $this->_buffer[]='</script>';
        // hidden que contendra la estructura parseada
        $this->_buffer[]='<input type="hidden" name="'.$this->getCustomProp('name').'" id="[[%id%]]_hidden">';
        // tabla principal
        $this->_buffer[]='<table id="[[%id%]]_table" class="'.get_class($this).'_table" border="0">';
        $this->_buffer[]='<tr>';
        $this->_buffer[]='<td class="'.get_class($this).'_table_drop_area_col" valign="top">';
        // div que recibira los drops
        $this->_buffer[]='<div '
            .'id="[[%id%]]_drop_area" '
            .'ondragover="event.preventDefault();" '
            .'ondrop="'.get_class($this).'_catch_drop(event,this,\''.$this->getCustomProp('id_producto').'\',\'[[%id%]]\');" '
            .'class="'.get_class($this).'_drop_area" '
            .'ondragover="event.preventDefault();" '
            .'ondrop="catchDrop(event)"'
            .'>';
        
        if(!is_null($this->getCustomProp('value')) && is_array($this->getCustomProp('value'))) {
            $this->_buffer[]=$this->parseValue($this->getCustomProp('value'));
        }
            
        $this->_buffer[]='</div>';
        $this->_buffer[]='</td>';
        // columna donde están los droppables
        $this->_buffer[]='<td class="'.get_class($this).'_table_droppables" valign="top">';
        // droppable - grupo
        $this->_buffer[]='<div '
        .'draggable="true" '
        .'ondragstart="event.dataTransfer.setData(\'tipo\',\'grupo\');" '
        .'class="'.get_class($this).'_droppable_element grupo" '
        .'id="[[%id%]]_droppable_element_grupo">'
        .'<div class="title">Grupo</div>'
        .'<input type="text" placeholder="Nombre del grupo..." value="fer">'
        .'</div>';
        $this->_buffer[]='<br>';
        // droppable - fila
        $this->_buffer[]='<div draggable="true" ondragstart="event.dataTransfer.setData(\'tipo\',\'fila\');" class="'.get_class($this).'_droppable_element fila" id="[[%id%]]_droppable_element_fila"><div class="title">Fila</div></div>';
        $this->_buffer[]='<br>';
        // droppable - componente
        $this->_buffer[]='<div draggable="true" ondragstart="event.dataTransfer.setData(\'tipo\',\'componente\');" class="'.get_class($this).'_droppable_element componente" id="[[%id%]]_droppable_element_componente"><div class="title">Componente</div>
        ';
        $this->_buffer[]='<select id="[[%id%]]_componente_combo" onchange="'.get_class($this).'_config_component(\'[[%id%]]\')">';
        $this->_buffer[]='<option value="">Seleccione...</option>';
        
        $aux=array();
        foreach($components as $c) {
            $clazz=get_class($c);
            //if(!in_array($c->getComponentType(),array('Básico','Complejo','Avanzado'))) continue;
            if(!in_array($clazz,$this->usable_components)) continue;
            $aux[$c->getComponentType()][$clazz]=$c->getCommonName();
        }
        foreach($aux as &$a) {
            asort($a);
            unset($a);
        }
        foreach($aux as $k=>$cc) {
            $this->_buffer[]='<optgroup label="'.$k.'">';
            foreach($cc as $kk=>$c) {
                $this->_buffer[]='<option value="'.$kk.'">'.$c.'</option>';
            }
            $this->_buffer[]='</optgroup>';
        }
        
        
        $this->_buffer[]='</select>';
        $this->_buffer[]='<div><b>Nombre</b><br><input type="text" id="[[%id%]]_componente_nombre" placeholder="Ingrese nombre de campo..." style="width: 300px;"></div>';
        $this->_buffer[]='<div id="[[%id%]]_config_component" style="text-align: left; margin-top: 10px;"></div>';
        $this->_buffer[]='</td>';
        $this->_buffer[]='</tr>';
        $this->_buffer[]='</table>';
        if(!is_null($this->getCustomProp('preview_url'))) {
            $this->_buffer[]='
            <br>
            <b>Preview</b>
            <br>
            <iframe id="[[%id%]]_preview_iframe" src="'.$this->getCustomProp('preview_url').'" style="width: 100%; height: 300px;"></iframe>
            <br><br>
            ';
        }
        
        //$this->_buffer[]='<button type="button" onclick="'.get_class($this).'_parse_validaciones(\'[[%id%]]\')">parse</button>';
        return parent::draw();
        
    }
    
}