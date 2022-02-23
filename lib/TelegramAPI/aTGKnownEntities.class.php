<?php
abstract class aTGKnownEntities {
    protected static $known_tokens;
    protected static $known_chats;
    protected static $known_users;
    
    private static function &getTarget($type) {
        switch($type) {
            case 'tokens': return TGKnownEntities::$known_tokens; break;
            case 'chats': return TGKnownEntities::$known_chats; break;
            case 'users': return TGKnownEntities::$known_users; break;
        }
    }
    
    public static function get($type,$key) {
        $target=&TGKnownEntities::getTarget($type);
        if(!array_key_exists($key,$target)) return false;
        return $target[$key];
    }
    
    public static function getId($type,$id) {
        $target=&TGKnownEntities::getTarget($type);
        foreach($target as $k=>$v) {
            if($v==$id) return $k;
        }
    }
    
    public static function getKnownTokens() {
        return TGKnownEntities::$known_tokens;
    }
}
