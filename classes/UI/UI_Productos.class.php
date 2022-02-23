<?php
class UI_Productos {
    private $_M;
    private $producto;
    private $component_tree=array();
    private $component_tree_info;
    private $internal_id;
    
    static function get_component_tree($id_producto,&$more_info,$db) {
        $_M['producto']=AutoModel::getInstance('productos','productos',$db);
        $_M['grupo']=AutoModel::getInstance('formularios','grupo',$db);
        $_M['fila']=AutoModel::getInstance('formularios','fila',$db);
        $_M['componente']=AutoModel::getInstance('formularios','componente',$db);
        $_M['configuracion']=AutoModel::getInstance('formularios','configuracion',$db);
        $producto=$_M['producto']->getById($_GET['id']);
        if($producto===false) throw new Exception('El producto "'.$id_producto.'" no existe');

        $grupos=$_M['grupo']->getByAndCond(array('id_producto'=>$id_producto));
        $more_info['total_groups']=count($grupos);
        foreach($grupos as $grupo) {
            $parsed_grupo=array();
            $filas=$_M['fila']->getByAndCond(array('id_grupo'=>$grupo->id_grupo));
            $more_info['total_rows']+=count($filas);
            unset($grupo_ptr);
            $grupo_ptr=array();
            $grupo_ptr['max_in_row']=0;
            $more_info['grupos'][$grupo->nombre]=&$grupo_ptr;
            foreach($filas as $fila) {
                unset($fila_ptr);
                $fila_ptr=array();
                $grupo_ptr['rows'][]=&$fila_ptr;
                $parsed_fila=array();
                $componentes=$_M['componente']->getByAndCond(array('id_fila'=>$fila->id_fila));
                foreach($componentes as $componente) {
                    $fila_ptr['num_componentes']++;
                    $more_info['total_components']++;
                    $parsed_componente=array(
                        'componente_class'=>$componente->class,
                        'nombre_campo'=>$componente->nombre,
                        'agrupable'=>$componente->agrupable,
                        'config'=>array(),
                    );
                    foreach($_M['configuracion']->getByAndCond(array('id_componente'=>$componente->id_componente)) as $configuracion) {
                        $parsed_componente['config'][$configuracion->nombre]=$configuracion->valor;
                    }
                    $parsed_fila[]=$parsed_componente;
                }
                if($fila_ptr['num_componentes']>$grupo_ptr['max_in_row']) {
                    $grupo_ptr['max_in_row']=$fila_ptr['num_componentes'];
                }
                $parsed_grupo[]=$parsed_fila;
            }
            $component_tree[$grupo->nombre]=$parsed_grupo;
        }
        return $component_tree;
    }
    
    function __construct($id_producto,$db) {
        if(!is_a($db,'DB_Interface')) throw new Exception(__METHOD__.' - $db debe ser una instance de DB_Interface');
        $this->component_tree=self::get_component_tree($id_producto,$more_info,$db);
        $this->component_tree_info=$more_info;
        $this->internal_id='UIP'.uniqid();
    }
    
    private function draw_panel($panel_title,$panel_content) {
        if(is_array($panel_content)) $panel_content=implode("\r\n",$panel_content);
        $panel_uid='P'.uniqid();
        $html=array();
        $html[]='<div class="panel-group" id="[[%id%]]_accordion">';
        $html[]='<div class="panel panel-default">';
        $html[]='<div class="panel-heading">';
        $html[]='<h4 class="panel-title">';
        $html[]='<a data-toggle="collapse" data-parent="#[[%id%]]_accordion" href="#collapse'.$panel_uid.'">';
        $html[]=$panel_title;
        $html[]='</a>';
        $html[]='</h4>';
        $html[]='</div>';
        $html[]='</div>';
        
        $html[]='<div id="collapse'.$panel_uid.'" class="panel-collapse collapse in">';
        $html[]='<div class="panel-body" style="background-color: #F3F3F3;">';
        $html[]=$panel_content;
        $html[]='</div>';
        $html[]='</div>';
        $html[]='</div>';        
        return implode("\r\n",$html);
    }
    
    static function get_field_name($grupo_name,$field_tag) {
        $ret=str_replace(' ','_',preg_replace('#[^a-z0-9_]#','',strtolower($grupo_name.'_'.$field_tag)));
        while(strpos($ret,'__')!==false) $ret=str_replace('__','_');
        return $ret;
        
    }
    
    function draw() {
        $html=array();
        $group_num=0;
        foreach($this->component_tree as $grupo=>$filas) {
            $group_num++;
            $group_info_ptr=&$this->component_tree_info['grupos'][$grupo];
            $panel_html=array();
            $panel_html[]='<table border="0" class="'.get_class($this).'_group_table">';
            foreach($filas as $nfila=>$componentes) {
                $odd=($nfila%2==0);
                $panel_html[]='<tr class="'.get_class($this).'_row '.($odd?get_class($this).'_odd':get_class($this).'_even').'">';
                $ncomponente=0;
                reset($componentes);
                for($componente=current($componentes);current($componentes);$componente=next($componentes)) {
                    $ncomponente++;
                    $cinstance=new $componente['componente_class'];
                    $cinstance->name=self::get_field_name($grupo,$componente['nombre_campo']);
                    $colspan=null;
                    if(!next($componentes)) {
                        if($group_info_ptr['max_in_row']!=$ncomponente) {
                            $colspan=1+((($group_info_ptr['max_in_row']-$ncomponente)))*2;
                        }
                    }else{
                        prev($componentes);
                    }
                    
                    foreach($componente['config'] as $cfgk=>$cfgv) {
                        //echo $cfgk.'='.$cfgv.'<br>';
                        $cinstance->$cfgk=$cfgv;
                    }
                    
                    if(!$cinstance->overrideTag()) {
                        $panel_html[]='<td class="'.get_class($this).'_field_title">';
                        $panel_html[]=$componente['nombre_campo'];
                        $panel_html[]='</td>';

                        $panel_html[]='<td class="'.get_class($this).'_field"'.(!is_null($colspan)?' colspan="'.$colspan.'"':'').'>';
                        $panel_html[]=$cinstance->draw();
                        $panel_html[]='</td>';
                    }else{
                        $panel_html[]='<td class="'.get_class($this).'_field"'.(!is_null($colspan)?' colspan="'.($colspan+1).'"':'').'>';
                        $panel_html[]=$cinstance->draw();
                        $panel_html[]='</td>';
                    }
                }
                $panel_html[]='</tr>';
            }
            $panel_html[]='</table>';
            
            $html[]=$this->draw_panel($grupo,$panel_html);
            
            unset($group_info_ptr);
        }
        foreach($html as &$h) {
            $h=str_replace('[[%id%]]',$this->internal_id,$h);
            unset($h);
        }
        
        return implode('',$html);
        return '
        <div class="panel-group" id="accordion">
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">
          Collapsible Group Item #1
        </a>
      </h4>
    </div>
    <div id="collapseOne" class="panel-collapse collapse in">
      <div class="panel-body">
        Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven\'t heard of them accusamus labore sustainable VHS.
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">
          Collapsible Group Item #2
        </a>
      </h4>
    </div>
    <div id="collapseTwo" class="panel-collapse collapse">
      <div class="panel-body">
        Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven\'t heard of them accusamus labore sustainable VHS.
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree">
          Collapsible Group Item #3
        </a>
      </h4>
    </div>
    <div id="collapseThree" class="panel-collapse collapse">
      <div class="panel-body">
        Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably haven\'t heard of them accusamus labore sustainable VHS.
      </div>
    </div>
  </div>
</div>';
    }
}