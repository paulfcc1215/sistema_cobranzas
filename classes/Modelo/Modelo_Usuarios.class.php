<?php
class Modelo_Usuarios extends Modelo {

	function __construct($id) {
		parent::__construct('auth','auth_usuarios','id_usuario',array());
        $query='SELECT * FROM "auth"."auth_usuarios" WHERE id_usuario=\''.$this->db->escape($id).'\'';
        $q0=$this->db->query($query);
        if($this->db->numRows($q0)==0) throw new Exception(__METHOD__.'::__construct - el id '.$id.' no existe');
        $qa0=$this->db->fetchOne($q0,true);
        foreach($qa0 as $k=>$v) {
            $this->_data[$k]=$v;
        }
    }

    static function getAll($include_disabled=false,$order_by='id_usuario DESC') {
        $db=DB::getInstance();
        $query='SELECT id_usuario FROM "auth"."auth_usuarios"';
        if(!$include_disabled) $query.=' WHERE "status"=\'1\'';
        $query.='ORDER BY '.$order_by;
        $q0=$db->query($query);
        if($db->numRows($q0)==0) return array();
        $ret=array();
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_usuario']]=new Modelo_Usuarios($qa0['id_usuario']);
        }
        return $ret;
    }

    static function getByUser($user,$include_disabled=false){
    	$db=DB::getInstance();
    	
    	$query='SELECT * FROM "auth"."auth_usuarios" WHERE "usr_logname"=\''.$db->escape($user).'\'';
    	if(!$include_disabled) $query.=' AND "status"=\'1\'';
    	$q0=$db->query($query);
        if($db->numRows($q0)==0) return array();
        $ret=array();
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_usuario']]=new Modelo_Usuarios($qa0['id_usuario']);
        }
        return $ret;
    }

    static function getByIdentificacion($identificacion,$include_disabled=false){
        $db=DB::getInstance();
        
        $query='SELECT id_usuario FROM "auth"."auth_usuarios" WHERE "identificacion"=\''.$db->escape($identificacion).'\'';
        if(!$include_disabled) $query.=' AND "status"=\'1\'';
        $q0=$db->query($query);
        if($db->numRows($q0)==0) return array();
        $ret=array();
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_usuario']]=new Modelo_Usuarios($qa0['id_usuario']);
        }
        return $ret;
    }

    static function getUsuariosPorGrupo($id_grupo){
        
        $db=DB::getInstance();
        $ret=array();

        $query='SELECT id_usuario FROM "auth"."auth_grupos_usuarios" WHERE id_grupo='.$db->escape($id_grupo);
        $q0=$db->query($query);
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_usuario']]= new Modelo_Usuarios($qa0['id_usuario']);
        }
        return $ret;
    }

    
    static function create($identificacion,$nombre_completo,$usuario,$pass,$force_pw_change,$status,$array_grupos_usuario) {
        $db=DB::getInstance();
        $db->startTransaction();
        
        $query='INSERT INTO "auth"."auth_usuarios" (
            identificacion,
            nombre_completo,
            usr_logname,
            pass,
            habilitado,
            fecha_agregado,
            status,
            force_pw_change
        )VALUES(
            \''.$db->escape($identificacion).'\',
            \''.$db->escape($nombre_completo).'\',
            \''.$db->escape($usuario).'\',
            \''.$db->escape(password_hash($pass,PASSWORD_DEFAULT)).'\',
            \'1\',
            CURRENT_TIMESTAMP,
            \''.$status.'\',
            \''.$force_pw_change.'\'
        )RETURNING id_usuario';

        $q0=$db->query($query);
        $qa0=$db->fetchOne($q0);
        if(is_null($qa0)){
            $db->rollback();
            return array();
        }
        $query='INSERT INTO "auth"."auth_grupos_usuarios" (usr_logname,id_grupo) VALUES ($1,$2)';
        $pre=$db->prepare("sentencia",$query);
        if(!$pre){
            $db->rollback();
            return array();
        }
        foreach ($array_grupos_usuario as $value) {
            $q0=$db->execute("sentencia",array($usuario,$value));
            if(!$q0){
                $db->rollback();
                return array();
            }
            //$db->free($q0);
        }
        $db->commit();
        return new Modelo_Usuarios($qa0['id_usuario']);
    }
    
    function __set($k,$v) {
        if(preg_match('#^\$2y\$10#',$v)){
            return $v;
        }
        if($k=='pass') {
            $v=password_hash($v,PASSWORD_DEFAULT);
        }
        parent::__set($k,$v);
    }
    

}