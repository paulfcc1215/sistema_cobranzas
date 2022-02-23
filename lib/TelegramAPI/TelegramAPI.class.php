<?php
require 'aTGKnownEntities.class.php';
//require 'TGKnownEntities.class.php';
require 'iKeyboard.class.php';
require 'TGInlineKeyboardMarkup.class.php';
require 'TGReplyKeyboardMarkup.class.php';
require 'TGReplyKeyboardRemove.class.php';

class TelegramAPI {
    private $curl;
    private $token;
    private $known_entities;
    private $last_response;
    private $url;
    
    function __construct($bot_token,$known_entities=null) {
        $this->known_entities=null;
        if(!is_null($known_entities)) {
            if(!is_a($known_entities,'aTGKnownEntities')) throw new Exception('$known_entities must extend TGKnownEntities class');
            $this->known_entities=$known_entities;
            $bot_token=$this->_findKnownEntity('tokens',$bot_token);
        }

        $this->token=$bot_token;
        $this->url='https://api.telegram.org/bot'.$bot_token.'/';
        
        $this->curl=curl_init();
        curl_setopt_array($this->curl,array(
            CURLOPT_URL=>$this->url,
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,
            CURLOPT_SSL_VERIFYPEER=>false,
        ));
    }
    
    private function _sendPost($endpoint,$params=array(),$files=array()) {
        $url=$this->url.$endpoint;
        curl_setopt($this->curl,CURLOPT_URL,$url);
        if(!empty($files)) {
            foreach($files as $fk=>$f) {
                if(strpos($fk,'#!#')!==false) {
                    $aux=explode('#!#',$fk);
                    $params[$aux[0]]=curl_file_create($f,'application/octect-stream',$aux[1]);
                }else{
                    $params[$fk]=curl_file_create($f);
                }
            }
        }
        curl_setopt($this->curl,CURLOPT_POSTFIELDS,$params);
        $this->last_response=curl_exec($this->curl);
        $ret=json_decode($this->last_response,true);
        return $ret;
    }

    private function _sendGet($endpoint,$params=array()) {
        $url=$this->url.$endpoint;
        if(!empty($params)) {
            $aux=array();
            foreach($params as $k=>$v) {
                $aux[]=urlencode($k).'='.urlencode($v);
            }
            $url.='?'.implode('&',$aux);
        }
        curl_setopt($this->curl,CURLOPT_HTTPGET,true);
        curl_setopt($this->curl,CURLOPT_URL,$url);
        
        $this->last_response=curl_exec($this->curl);
        $ret=json_decode($this->last_response,true);
        return $ret;
    }
    
    public function getLastResponse() {
        return $this->last_response;
    }
    
    private function _findKnownEntity($type,$entity) {
        if(is_null($this->known_entities)) return $entity;
        $ret=$this->known_entities->get($type,$entity);
        if($ret===false) return $entity;
        return $ret;
    }
    
    function getMe() {
        return ($this->_sendGet('getMe'));
    }
    
    function sendDocument(
        $chat_id,
        $document_path,
        $override_uploaded_fname=null,
        $thumb_path=null,
        $caption=null,
        $parse_mode=null,
        $disable_notification=null,
        $reply_to_message_id=null,
        $reply_markup=null
    ) {
        $chat_id=$this->_findKnownEntity('chats',$chat_id);
        
        $params=array(
            'chat_id'=>$chat_id
        );
        if(!is_null($override_uploaded_fname)) {
            $files=array(
                'document#!#'.$override_uploaded_fname => $document_path
            );
        }else{
            $files=array(
                'document'=>$document_path
            );
        }
        
        if(!is_null($thumb_path)) {
            $files['thumb']=$thumb_path;
        }
        
        if(!is_null($caption)) {
            $params['caption']=$caption;
        }
        if(!is_null($parse_mode)) {
            $params['parse_mode']=$parse_mode;
        }else{
            $params['parse_mode']='Markdown';
        }
        
        return $this->_sendPost('sendDocument',$params,$files);
    }
    
    function sendMessage(
        $chat_id,
        $text,
        $parse_mode=null,
        $disable_web_page_preview=null,
        $disable_notification=null,
        $reply_to_message_id=null,
        $reply_markup=null
    ){
        $chat_id=$this->_findKnownEntity('chats',$chat_id);
        $params=array(
            'chat_id'=>$chat_id,
            'text'=>$text,
        );
        
        if(!is_null($parse_mode)) {
            if($parse_mode!='') {
                if(!in_array($parse_mode,array('Markdown','HTML'))) throw new Exception ('$parse_mode must be Markdown or HTML');
                $params['parse_mode']=$parse_mode;
            }
        }else{
            $params['parse_mode']='Markdown';
        }
        
        if(!is_null($disable_web_page_preview)) {
            if(!is_bool($disable_web_page_preview)) throw new Exception('disable_web_page_preview must be boolean');
            $params['disable_web_page_preview']=$disable_web_page_preview;
        }
        if(!is_null($disable_notification)) {
            if(!is_bool($disable_notification)) throw new Exception('disable_notification must be boolean');
            $params['disable_notification']=$disable_notification;
        }
        if(!is_null($reply_to_message_id)) {
            if(!is_integer($reply_to_message_id)) throw new Exception('reply_to_message_id must be integer');
            $params['reply_to_message_id']=$reply_to_message_id;
        }
        if(!is_null($reply_markup)) {
            if(!is_object($reply_markup)) throw new Exception('reply_markup must be a class');
            if(!is_a($reply_markup,'iKeyboard')) throw new Exception('reply_markup must implement interface iKeyboard');
            $params['reply_markup']=($reply_markup->get());
        }
        
        return $this->_sendPost('sendMessage',$params);
    }
    
    
    function sendChatAction($chat_id,$action) {
        $params=array(
            'chat_id'=>$chat_id,
            'action'=>$action
        );
        $valid_actions=array(
            'typing',
            'upload_photo',
            'record_video',
            'record_audio',
            'upload_document',
            'find_location',
            'record_video_note',
            'upload_video_note'
        );
        if(!in_array($action,$valid_actions)) throw new Exception('$action must be one of '.implode(',',$valid_actions));
        $this->_sendPost('sendChatAction',$params);
        
    }
    
    function keyboardRemove($chat_id,$text=null,$reply_to_message_id=null,$selective=false) {        
        $kb=new TGReplyKeyboardRemove($selective);
        return  $this->sendMessage($chat_id,$text,'',null,null,$reply_to_message_id,$kb);
    }
    

    function forwardMessage() {
        throw new Exception('Not implemented');
    }

    function sendPhoto() {
        throw new Exception('Not implemented');
    }

    function sendVoice(
        $chat_id,
        $voice,
        $override_uploaded_fname=null,
        $caption=null,
        $parse_mode=null,
        $duration=null,
        $disable_notification=null,
        $reply_to_message_id=null,
        $reply_markup=null    
    ) {
        $chat_id=$this->_findKnownEntity('chats',$chat_id);
        $params=array(
            'chat_id'=>$chat_id
        );
        if(!is_null($override_uploaded_fname)) {
            $files=array(
                'voice#!#'.$override_uploaded_fname => $voice
            );
        }else{
            $files=array(
                'voice'=>$voice
            );
        }
        
        if(!is_null($caption)) {
            $params['caption']=$caption;
        }
        if(!is_null($parse_mode)) {
            $params['parse_mode']=$caption;
        }else{
            $params['parse_mode']='Markdown';
        }
        
        if(!is_null($duration)) {
            $params['duration']=$duration;
        }
        if(!is_null($disable_notification)) {
            $params['disable_notification']=$disable_notification;
        }
        if(!is_null($reply_to_message_id)) {
            $params['reply_to_message_id']=$reply_to_message_id;
        }
        if(!is_null($reply_markup)) {
            $params['reply_markup']=$reply_markup->get();
        }
        
        return $this->_sendPost('sendVoice',$params,$files);        
        
    }

    function sendVideo() {
        throw new Exception('Not implemented');
    }

    function sendAnimation() {
        throw new Exception('Not implemented');
    }

    function sendAudio(
        $chat_id,
        $audio,
        $override_uploaded_fname=null,
        $caption=null,
        $parse_mode=null,
        $duration=null,
        $performer=null,
        $title=null,
        $thumb=null,
        $disable_notification=null,
        $reply_to_message_id=null,
        $reply_markup=null    
    ) {
        $chat_id=$this->_findKnownEntity('chats',$chat_id);
        $params=array(
            'chat_id'=>$chat_id
        );
        if(!is_null($override_uploaded_fname)) {
            $files=array(
                'audio#!#'.$override_uploaded_fname => $audio
            );
        }else{
            $files=array(
                'audio'=>$audio
            );
        }
        if(!is_null($thumb)) {
            $files['thumb']=$thumb;
        }
        
        if(!is_null($caption)) {
            $params['caption']=$caption;
        }
        if(!is_null($parse_mode)) {
            $params['parse_mode']=$caption;
        }else{
            $params['parse_mode']='Markdown';
        }
        
        if(!is_null($duration)) {
            $params['duration']=$duration;
        }

        if(!is_null($performer)) {
            $params['performer']=$performer;
        }
        
        if(!is_null($title)) {
            $params['title']=$title;
        }
        
        if(!is_null($disable_notification)) {
            $params['disable_notification']=$disable_notification;
        }
        if(!is_null($reply_to_message_id)) {
            $params['reply_to_message_id']=$reply_to_message_id;
        }
        if(!is_null($reply_markup)) {
            $params['reply_markup']=$reply_markup->get();
        }
        
        return $this->_sendPost('sendAudio',$params,$files);        
        
    }

    function sendVideoNote() {
        throw new Exception('Not implemented');
    }

    function sendMediaGroup() {
        throw new Exception('Not implemented');
    }

    function sendLocation() {
        throw new Exception('Not implemented');
    }

    function editMessageLiveLocation() {
        throw new Exception('Not implemented');
    }

    function stopMessageLiveLocation() {
        throw new Exception('Not implemented');
    }

    function sendVenue() {
        throw new Exception('Not implemented');
    }

    function sendContact() {
        throw new Exception('Not implemented');
    }

    function getUserProfilePhotos() {
        throw new Exception('Not implemented');
    }

    function getFile() {
        throw new Exception('Not implemented');
    }

    function kickChatMember() {
        throw new Exception('Not implemented');
    }

    function unbanChatMember() {
        throw new Exception('Not implemented');
    }

    function restrictChatMember() {
        throw new Exception('Not implemented');
    }

    function promoteChatMember() {
        throw new Exception('Not implemented');
    }

    function exportChatInviteLink() {
        throw new Exception('Not implemented');
    }

    function setChatPhoto() {
        throw new Exception('Not implemented');
    }

    function deleteChatPhoto() {
        throw new Exception('Not implemented');
    }

    function setChatTitle() {
        throw new Exception('Not implemented');
    }

    function setChatDescription() {
        throw new Exception('Not implemented');
    }

    function pinChatMessage() {
        throw new Exception('Not implemented');
    }

    function unpinChatMessage() {
        throw new Exception('Not implemented');
    }

    function leaveChat() {
        throw new Exception('Not implemented');
    }

    function getChat() {
        throw new Exception('Not implemented');
    }

    function getChatAdministrators() {
        throw new Exception('Not implemented');
    }

    function getChatMembersCount() {
        throw new Exception('Not implemented');
    }

    function getChatMember() {
        throw new Exception('Not implemented');
    }

    function setChatStickerSet() {
        throw new Exception('Not implemented');
    }

    function deleteChatStickerSet() {
        throw new Exception('Not implemented');
    }

    function answerCallbackQuery(
        $callback_query_id,
        $text=null,
        $show_alert=null,
        $url=null,
        $cache_time=null
    ) {
        $params=array(
            'callback_query_id'=>$callback_query_id
        );
        if(!is_null($text)) {
            $params['text']=$text;
        }
        if(!is_null($show_alert)) {
            $params['show_alert']=$show_alert;
        }
        if(!is_null($url)) {
            $params['url']=$url;
        }
        if(!is_null($cache_time)) {
            $params['cache_time']=$cache_time;
        }
        
        return $this->_sendPost('answerCallbackQuery',$params);
    }
    
    
}