<?php
class DB_PgSQL implements DB_Interface {
    private $conn;
    private $inTransaction;
    private $relations_manager;
    private $conn_string_hash;
    private $conn_framework_name;
    private $_CACHE;
    
    function __construct($framework_conn_name,$host,$user,$pass,$dbname,$port=5432) {
        $conn_string='host='.$host.' user='.$user.' password='.$pass.' dbname='.$dbname.' port='.$port;
        $this->conn = pg_connect($conn_string);
        if(!$this->conn) throw new Exception('Error al conectar a la base de datos');
        $this->inTransaction=false;
        $this->relations_manager=new DB_Relations($this);
        $this->conn_string_hash=md5($conn_string);
        $this->conn_framework_name=$framework_conn_name;
        $this->_CACHE=array();
    }
    
    public function getConnName() {
        return $this->conn_framework_name;
    }
    
    public function getConnStringHash() {
        return $this->conn_string_hash;
    }
    
    public function relations() {
        return $this->relations_manager;
    }
	
	public function getNotice() {
		return pg_last_error($this->conn);
	}
    
    function query($query,$throw_exception=true) {
        $dbt=debug_backtrace();
        $dbg_file=$dbt[0]['file'];
        $dbg_line=$dbt[0]['line'];
        
        $query='/*'."\r\n\r\n".'File is: '.$dbg_file."\r\n".'Line is: '.$dbg_line."\r\n\r\n".'*/'."\r\n".$query;
        $q0=pg_query($this->conn,$query);
        if(!$q0) {
            if($throw_exception) throw new Exception('Error al ejecutar query "'.$query.'" | '.pg_last_error($this->conn));
            echo 'Error al ejecutar query "'.$query.'" | '.pg_last_error($this->conn);
            /*
            $backtrace=debug_backtrace();
            print_arr($backtrace);
            */
            echo '<pre>';
            debug_print_backtrace();
            die();
        }
        return new DB_PGResultSet($this,$q0,$query);
    }
    
    function affectedRows($q0) {
        return pg_affected_rows($q0);
    }
    
    function get_connection() {
        return $this->conn;
    }
    
    function escape($string) {
        if(is_array($string) || is_object($string)) {
            throw new Exception(__METHOD__.'(?) - No es string! (es object o array)');
        }
        return pg_escape_string($this->conn,$string);
    }
    
    function escape_bytea($data) {
        return pg_escape_bytea($this->conn,$data);
    }
    
    function unescape_bytea($data) {
        return pg_unescape_bytea($data);
    }
    
    function numRows($resource) {
        if(is_a($resource,'DB_iResultSet')) {
            $resource=$resource->getResource();
        }
        return pg_num_rows($resource);
    }
    
    function fetchOne($resource,$freeResult=false) {
        if(is_a($resource,'DB_iResultSet')) {
            $resource=$resource->getResource();
        }
        $ret=pg_fetch_assoc($resource);
        if($freeResult) {
            pg_free_result($resource);
        }
        return $ret;
        
    }
    function fetchOneRow($resource,$freeResult=false) {
        if(is_a($resource,'DB_iResultSet')) {
            $resource=$resource->getResource();
        }
        $ret=pg_fetch_row($resource);
        if($freeResult) {
            pg_free_result($resource);
        }
        return $ret;
    }
    
    function fetchAll($in_resource,$freeResult=true) {
		
        if(is_a($in_resource,'DB_iResultSet')) {
            $resource=$in_resource->getResource();
        }else{
			$resource=&$in_resource;
		}
        $ret=array();
        if(pg_num_rows($resource)>0) {
            while($qa0=pg_fetch_assoc($resource)) {
                $ret[]=$qa0;
            }
        }
        if($freeResult) pg_free_result($resource);
        return $ret;
        
    }
    
    function free($resource) {
        if(is_a($resource,'DB_iResultSet')) {
            $resource=$resource->getResource();
        }
        pg_free_result($resource);
    }
    
    public function startTransaction($throw_exception=false) {
        if(!$this->inTransaction) {
            $this->query('BEGIN',$throw_exception);
            $this->inTransaction=true;
        }
    }
    public function commit($throw_exception=false) {
        if($this->inTransaction) {
            $this->query('COMMIT',$throw_exception);
            $this->inTransaction=false;
        }
    }
    public function rollback($throw_exception=false) {
        if($this->inTransaction) {
            $this->query('ROLLBACK',$throw_exception);
            $this->inTransaction=false;
        }
    }
    
    public function isInTransaction() {
        return $this->inTransaction;
    }
    
    public function getColumns($schema_name,$table_name,$show_system_columns=false,$show_dropped_columns=false) {
        $pks=$this->getPrimaryKeys($schema_name,$table_name);
        
        $query='
            SELECT
                nsp.nspname AS schema,
                cls.relname AS table,
                att.attnum AS colindex,
                att.attname AS column,
                format_type(att.atttypid,null) AS type,
                att.attlen AS length,
                (NOT att.attnotnull) AS nullable,
                pg_catalog.pg_get_expr(def.adbin, def.adrelid) AS default
                --def.adsrc AS default
            FROM
                pg_attribute att
            JOIN pg_class cls ON (cls.oid = att.attrelid)
            JOIN pg_namespace nsp ON (nsp.oid = cls.relnamespace)
            LEFT JOIN pg_attrdef def ON (def.adrelid=cls.oid AND def.adnum=att.attnum)
            WHERE
            nsp.nspname=\''.$this->escape($schema_name).'\'
            AND cls.relname=\''.$this->escape($table_name).'\'
        ';
        if(!$show_system_columns) $query.="\r\n".'AND att.attnum >=0';
        if(!$show_dropped_columns) $query.="\r\n".'AND NOT att.attisdropped';
        $ret=array();
        foreach($this->query($query) as $k=>$v) {
            switch($v['nullable']) {
                case 't': $v['nullable']=true; break;
                case 'f': $v['nullable']=false; break;
                default: throw new Exception('Unexpected nullable value!'); break;
            }
            if(in_array($v['column'],$pks)) {
                $v['is_pk']=true;
            }else{
                $v['is_pk']=false;
            }
            $ret[]=$v;
        }
        return $ret;
    }

    public function getNonNullableColumns($schema_name,$table_name) {
        $query='SELECT * FROM information_schema.columns WHERE table_schema=\''.$this->escape($schema_name).'\' AND table_name=\''.$table_name.'\' AND is_nullable=\'NO\'';
        $q0=$this->query($query);
        return $this->fetchAll($q0);
    }
    
    public function prepare($stmt_name,$query) {
        $ret=pg_prepare($this->conn,$stmt_name,$query);
        if(!$ret) {
			echo 'Error while preparing "'.$query.'"<hr>"';
			echo '<pre>';
			debug_print_backtrace();
			echo '</pre>';
            throw new Exception(__METHOD__.' - Error al preparar statement "'.$query.'". '.pg_last_error($this->conn));
		}
        return $ret;
    }
    
    public function execute($stmt_name,$params) {
        $q0=pg_execute($this->conn,$stmt_name,$params);
        if(!$q0) {
            throw new Exception(__METHOD__.' - Error al ejecutar statement "'.$stmt_name.'" con parametros ('.implode(',',$params).'). '.pg_last_error($this->conn));
        }
        return new DB_PGResultSet($this,$q0);
    }
    
    public function getTables($schema) {
        $query='
            SELECT
                ns.nspname AS SCHEMA,
                cls.relname AS TABLE
            FROM
                pg_class cls
            JOIN pg_namespace ns ON (cls.relnamespace = ns.oid)
            WHERE
            cls.relkind=\'r\'
            AND ns.nspname=\''.$this->escape($schema).'\'
        ';
        
        $ret=array();
        foreach($this->query($query) as $k=>$v) {
            $ret[]=$v['table'];
        }
        return $ret;
    }
    
    public function getPrimaryKeys($schema,$table) {
        $query='
            SELECT
                ns.nspname AS schema,
                cls.relname AS table,
                constr.conname AS constraint_name,
                attr.attname AS column
            FROM
                pg_constraint constr
            JOIN pg_namespace ns ON (ns.oid = constr.connamespace)
            JOIN pg_class cls ON (cls.oid = constr.conrelid)
            JOIN unnest(constr.conkey) AS pks ON (TRUE)
            JOIN pg_attribute attr ON (
                attr.attrelid = cls.oid
                AND attr.attnum = pks
            )
            WHERE
                constr.contype = \'p\'
            AND ns.nspname = \''.$schema.'\'
            AND cls.relname = \''.$table.'\'
        ';
       $q0=$this->query($query);
       $ret=array();
       while($qa0=$this->fetchOne($q0)) {
           $ret[]=$qa0['column'];
       }
       return $ret;
    }
	
	public function insert($schema,$table,$data,$raw_data=array(),$db=null,$returning=null) {
		if(is_null($db)) {
			$db=DB::getInstance();
		}
		foreach($data as &$d) {
			if(is_null($d) || strtolower($d)=='null') {
				$d='NULL';
			}else if(strtolower($d)=='now()'){
				$d='NOW()';
			}else{
				$d='\''.$db->escape($d).'\'';
			}
			unset($d);
		}
		
		foreach($raw_data as $k=>$v) $data[$k]=$v;
		
		$query='INSERT INTO "'.$schema.'"."'.$table.'" ('.implode(',',array_keys($data)).') VALUES ('.implode(',',$data).')';
		if(!is_null($returning)) {
			$query.=' RETURNING '.$returning;
		}
		return $db->query($query);
	}
    
	public function &getEnumValues($enum_name) {
		if(!array_key_exists('enum_'.$enum_name,$this->_CACHE) || empty($this->_CACHE['enum_'.$enum_name])) {
			$db=DB::getInstance();
			$query='SELECT
					*
				FROM
					pg_enum
				WHERE
					enumtypid = (
						SELECT
							oid
						FROM
							pg_type
						WHERE
							typname = \''.$this->escape($enum_name).'\'
					);
			';
			foreach($this->query($query) as $t) {
				$this->_CACHE['enum_'.$enum_name][]=$t['enumlabel'];
			}
			
		}
		return $this->_CACHE['enum_'.$enum_name];
	}    

}

