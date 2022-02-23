<?php
class Log {
    static function hdba($action,$archivo,$data) {
        $db=DB::getInstance();
        $SM=SessionManager::getInstance(_SESSION_NAME);
        $user=$SM->user;
        $query='INSERT INTO "'._DB_SCHEMA_LOG_TABLES.'".log_hdba (action,date,usr_logname,ip,data,filename)
        VALUES
        (
        ';
        $val=array(
            '\''.$db->escape($action).'\'',
            'NOW()',
            '\''.$db->escape($user['usr_logname']).'\'',
            '\''.$_SERVER['REMOTE_ADDR'].'\'',
            '\''.$db->escape(serialize($data)).'\'',
            '\''.$db->escape($archivo).'\''
        );
        $query.=implode(',',$val);
        $query.=')';
        $db->query($query);
    }
    
    static function addLog($action,$archivo,$data) {
        $db=DB::getInstance();
        $SM=SessionManager::getInstance(_SESSION_NAME);
        $user=$SM->user;
        $archivo=basename($archivo);
        
        if(is_array($data)) {
            $data=print_r($data,true);
            if(in_array('ajax_refresh',$data)) return;
        }
        $data=utf8_encode($data);
        
        $query='INSERT INTO "'._DB_SCHEMA_LOG_TABLES.'".log_actions (action,date,usr_logname,ip,data,filename)
        VALUES
        (
        ';
        $val=array(
            '\''.$db->escape($action).'\'',
            'NOW()',
            '\''.$db->escape($user['usr_logname']).'\'',
            '\''.$_SERVER['REMOTE_ADDR'].'\'',
            '\''.$db->escape($data).'\'',
            '\''.$db->escape($archivo).'\''
        );
        $query.=implode(',',$val);
        $query.=')';
        $db->query($query);
    }
    
    
    
}