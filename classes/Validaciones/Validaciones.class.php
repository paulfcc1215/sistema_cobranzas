<?php
class Validaciones { 
    private static $instance;
    private static $_CACHE;
    private $_db;
    private $_modelo;
    protected function __construct() {
        $this->_db = Db::getInstance();
        $this->_modelo = AutoModel::getInstance('validaciones','validaciones',$this->_db);
    }
    
    private function &getFnRef($id_or_name) {
        if(preg_match('#^\d+$#',$id_or_name)) {
            $aux=$this->_modelo->getById($id_or_name);
            if(!$aux)
                throw new Exception(__METHOD_.' - Validación con id "'.$id_or_name.'" no existe en la base de datos');
            $identificador=$aux->identificador;
        }else{
            $identificador=$id_or_name;
        }
        
        if(!array_key_exists($identificador,Validaciones::$_CACHE)) {
            $val_rec=$this->_modelo->getByAndCond(array('identificador'=>$identificador));
            if(empty($val_rec)) throw new Exception(__METHOD_.' - Validación "'.$identificador.'" no existe en la base de datos');
            $element=array(
                'id_validacion'=>-1,
                'params'=>array(),
                'identificador'=>$val_rec[0]->identificador,
                'descripcion'=>$val_rec[0]->descripcion,
                'fn'=>''
                
            );
            $params=array();
            if(!is_null($val_rec[0]->params) && trim($val_rec[0]->params)!='') {
                $params=explode(',',$val_rec[0]->params);
            }
            $element['params']=$params;
            $src='
            return function (&$info,$value';
            if(!empty($params)) {
                $src.=',$'.implode(',$',$params);
            }
            $src.=') {
                try {   
                    '."\r\n".$val_rec[0]->source_code."\r\n".'
                    return true;
                } catch (Exception $e) {
                    $info=$e->getMessage();
                    return false;
                }
             
            };
            ';
            $element['fn']=eval($src);
            $element['id_validacion']=$val_rec[0]->id_validacion;
            Validaciones::$_CACHE[$identificador]=$element;
        }
        return Validaciones::$_CACHE[$identificador];
    }
    
    public function getValidationRow($id_or_name) {
        if(preg_match('#^\d+$#',$id_or_name)) {
            $aux=$this->_modelo->getById($id_or_name);
            if(!$aux)
                throw new Exception(__METHOD_.' - Validación con id "'.$id_or_name.'" no existe en la base de datos');
            return $aux;
        }else{
            $aux=$this->_modelo->getByAndCond(array('identificador'=>$id_or_name));
            if(!$aux)
                throw new Exception(__METHOD_.' - Validación con id "'.$id_or_name.'" no existe en la base de datos');
            return $aux[0];
        }
        
    }
    
    public function v($id_or_name,&$info,$value) {
        $arguments=func_get_args();
        $num_args=count($arguments)-3;
        array_shift($arguments);
        array_shift($arguments);
        $params=array(
            &$info,
            array_shift($arguments)
        );
        while(!empty($arguments)) {
            $params[]=array_shift($arguments);
        }
        $target=&$this->getFnRef($id_or_name);
        if($num_args!=count($target['params']))
            throw new Exception(
                __METHOD__.' - la validación "'.$target['identificador'].'" recibe '.(count($target['params']))
                .' argumento'.((count($target['params'])==0 || count($target['params'])>1)?'s':'')
                .' ('.implode(',',$target['params']).'). '
                .'Usted está enviando '.$num_args.' argumento'
                .(($num_args==0 || $num_args>1)?'s':'').'.'
            );
        $ret=call_user_func_array($target['fn'],$params);
        return $ret;
    }
    
    public static function getInstance() {
        if(!is_null(Validaciones::$instance)) return Validaciones::$instance;
        Validaciones::$instance=new Validaciones();
        return Validaciones::$instance;
    }
    
    
    public function getList() {
        $list=$this->_modelo->getAll();
        $ret=array();
        foreach($list as $l) {
            $ll=array(
                'id_validacion'=>$l->id_validacion,
                'identificador'=>$l->identificador,
                'descripcion'=>$l->descripcion,
                'nombre_humano'=>$l->nombre_humano,
                'num_params'=>count(explode(',',$l->params)),
                'params'=>array()
            );
            if(!is_null($l->params) && trim($l->params)!='') {
                $ll['params']=explode(',',$l->params);
            }
            $ret[]=$ll;
        }
        return $ret;
    }
}