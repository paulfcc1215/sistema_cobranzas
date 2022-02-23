<?php
class UI_Helper {
    private function __construct() {
        // no instanciable
    }
    
    static function array_to_options($array,$selected=null,$op_seleccione=true) {
        $ret=array();
        if($op_seleccione)
            $ret[]='<option value="">Seleccione...</option>';
        foreach($array as $k=>$v) {
            $html='';
            if(is_array($v)) {
                $html.='<optgroup label="'.$k.'">';
                foreach($v as $kk=>$vv) {
                    $html.='<option value="'.$kk.'"';
                    if(is_array($selected)) {
                        if(in_array($kk,$selected)) {
                            $html.=' selected="1"';
                        }
                    }else{
                        if($selected==$kk) $html.=' selected="1"';
                    }
                    $html.='>'.$vv.'</option>';
                }
                $html.='</optgroup>';
            }else{
                $html.='<option value="'.$k.'"';
                if(is_array($selected)) {
                    if(in_array($k,$selected)) {
                        $html.=' selected="1"';
                    }
                }else{
                    if($selected==$k) $html.=' selected="1"';
                }
                $html.='>'.$v.'</option>';
            }
            
            $ret[]=$html;
        }
        return implode("\r\n",$ret);
    }    
    
    static function array_to_hidden($array,$debug=false,$exclude=array()) {
        $html=array();
        foreach($array as $k=>$v) {
            if(in_array($k,$exclude)) continue;
            if(is_array($v)) {
                foreach($v as $kk=>$vv) {
                    if($debug) {
                        $html[]='<input type="text" name="'.$k.'[]" value="'.$k.'[]=\''.$vv.'\'">';
                    }else{
                        $html[]='<input type="hidden" name="'.$k.'[]" value="'.$vv.'">';
                    }
                }
            }else{
                if($debug) {
                    $html[]='<input type="text" name="'.$k.'" value="'.$k.'=\''.$v.'\'">';
                }else{
                    $html[]='<input type="hidden" name="'.$k.'" value="'.$v.'">';
                }
            }
        }
        return implode("\r\n",$html);
    }
    
    static function putFechaAutoLoader() {
        return '
            <script>
            $(document).ready(function() {
                $(".fecha").datepicker({
                    \'todayHighlight\': true,
                    \'format\': "dd/mm/yyyy",
                    \'autoclose\': true,
                    \'language\': \'es\'
                    
                });
                
            });
            </script>
        ';
        
    }
    
    
    static function putTipificacionesHTML(
        $tipificaciones,
        $id_span_tipificacion,
        $tipificacion_seleccionada,
        $id_span_sub_tipificacion,
        $sub_tipificacion_seleccionada
    ) {

        if(!is_object($tipificaciones))
            throw new Exception(__METHOD__.' - la variable tipificaciones debe ser un objeto del tipo Tipificaciones');
        if(get_class($tipificaciones)!='Tipificaciones')
            throw new Exception(__METHOD__.' - la variable tipificaciones debe ser un objeto del tipo Tipificaciones');
        
        // traemos todas las tipificaciones en un array
        $aux=$tipificaciones->getAll();

        // ordenamos por peso las tipificaciones
        $aux2=array();
        foreach($aux as $k=>$a) {
            $aux2[]=array(
                $k,$a['peso']
            );
        }
        usort($aux2,function($a,$b) {
            if($a[1]>$b[1]) return -1;
            if($a[1]<$b[1]) return 1;
            return 0;
            
        });
        $tipif=array();
        foreach($aux2 as $v) {
            $tipif[$v[0]]=$aux[$v[0]];
        }
        
        // ordenamos sub tipificaciones por peso
        foreach($tipif as &$t) {
            uasort($t['_st'],function($a,$b) {
                if($a['peso']>$b['peso']) return -1;
                if($a['peso']<$b['peso']) return 1;
                return 0;
            });
        }
        unset($t);
                
        $html='<script>';
        
        $html.='
        // funcion event handler de tipificacion
        function __auto_tipif_eh(id_tipif) {
            try {
                var html="<select name=\'id_sub_tipificacion\'><option value=\'\'>Seleccione tipificacion...</option>";
                var st_span=document.getElementById("'.$id_span_sub_tipificacion.'");
                if(id_tipif==null) {
                    st_span.innerHTML=html+"</select>";
                    return;
                }

                var tipif=__auto_tipif[id_tipif];
                if(typeof st_span!="object" || st_span==null)
                    throw "No se logro conseguir el span con id \''.$id_span_sub_tipificacion.'\'";
                
                for(var i in tipif._st) {
                    var ptr=tipif._st[i];
                    var desc="";
                    if(ptr.tag!=null) desc=ptr.tag+" - ";
                    desc=desc+ptr.descripcion;
                    if(ptr.id_sub_tipificacion==\''.$sub_tipificacion_seleccionada.'\') {
                        html=html+"<option selected=\'selected\' value=\'"+ptr.id_sub_tipificacion+"\'>"+desc+"</option>";
                    }else{
                        html=html+"<option value=\'"+ptr.id_sub_tipificacion+"\'>"+desc+"</option>";
                    }
                    
                }
                html=html+"</select>"
                console.log(html);
                
                st_span.innerHTML=html;
            }catch(err) {
                alert("AUTO SUB TIPIFICACION - ERROR: "+err);
            }
        }
        
        try {
            // tipificaciones en json
            var __auto_tipif='.json_encode($tipif).';
            
            // spans donde se va a escribir los combos
            var __auto_tipif_span_tipif=document.getElementById("'.$id_span_tipificacion.'");
            if(typeof __auto_tipif_span_tipif!="object" || __auto_tipif_span_tipif==null)
                throw "No se logro conseguir el span con id \''.$id_span_tipificacion.'\'";
            
            var __auto_tipif_span_sub_tipif=document.getElementById("'.$id_span_sub_tipificacion.'");
            if(typeof __auto_tipif_span_sub_tipif!="object" || __auto_tipif_span_sub_tipif==null)
                throw "No se logro conseguir el span con id \''.$id_span_sub_tipificacion.'\'";
            
            // escribimos el combo
            var __auto_tipif_combo_html="<select name=\'id_tipificacion\' onchange=\'__auto_tipif_eh(this.value);\'><option value=\'\'>Seleccione...</option>";
            for(var __auto_i in __auto_tipif) {
                var __auto_ptr=__auto_tipif[__auto_i];
                if(__auto_ptr.id_tipificacion==\''.$tipificacion_seleccionada.'\') {
                    __auto_tipif_combo_html=__auto_tipif_combo_html+"<option selected=\'selected\' value=\'"+__auto_ptr.id_tipificacion+"\'>"+__auto_ptr.tag+" - "+__auto_ptr.descripcion+"</option>";
                }else{
                    __auto_tipif_combo_html=__auto_tipif_combo_html+"<option value=\'"+__auto_ptr.id_tipificacion+"\'>"+__auto_ptr.tag+" - "+__auto_ptr.descripcion+"</option>";
                }
            
            }
            __auto_tipif_combo_html=__auto_tipif_combo_html+"</select>";
            __auto_tipif_span_tipif.innerHTML=__auto_tipif_combo_html;
            
        }catch(err){
            alert("AUTO TIPIFICACION - ERROR: "+err);
        }
        
        __auto_tipif_eh('.($tipificacion_seleccionada==''?'null':'\''.$tipificacion_seleccionada.'\'').');
        
        
        
        ';
        
        
        
        $html.='</script>';
        return $html;
        
        
        
    }
    
    
}