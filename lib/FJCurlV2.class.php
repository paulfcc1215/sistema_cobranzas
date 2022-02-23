<?php
date_default_timezone_set('America/Guayaquil');
class FJCurlV2 {
    private $curl;
    private $headers;
    private $debug_type;
    private $tcp_debug_socket;
    private $file_debug_handle;
    private $last_response;
    private $last_response_headers;    

    function __construct($cookies) {
        $this->debug_type=null;
        $this->curl=curl_init();
        curl_setopt_array($this->curl,array(
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_HEADERFUNCTION=>array($this,'_headerHandler')
        ));
        if(!is_null($cookies) && $cookies!==false) {
            curl_setopt_array($this->curl,array(
                CURLOPT_COOKIEJAR=>$cookies,
                CURLOPT_COOKIEFILE=>$cookies,
            ));
        }
        $this->headers=array();
    }
    
    function _headerHandler($curl, $headerLine) {
        $aux=trim($headerLine);
        if($aux!='')
            $this->last_response_headers[]=$aux;
        return strlen($headerLine);
    }
    
    function __destruct() {
        switch($this->debug_type) {
            case 'file': fclose($this->file_debug_handle); break;
            case 'tcp': socket_close($this->tcp_debug_socket); break;
        }
    }
    
    protected function genHeaders() {
        $aux=array();
        foreach($this->headers as $h=>$v) {
            if(!is_array($v)) {
                $aux[]=$h.': '.$v;
            }else{
                foreach($v as $vv) {
                    $aux[]=$h.': '.$vv;
                }
            }
        }
        return $aux;
    }

    function setHeader($head,$value) {
        if(!is_array($value)) {
            $this->headers[$head]=$value;
        }else{
            foreach($value as $v) {
                $this->headers[$head][]=$v;
            }
        }
    }
    
    function unsetHeader($head) {
        unset($this->headers[$head]);
    }
    
    function get_last_response() {
        return $this->last_response;
    }
    
    function post($url,$post_data,$apply_url_encode=true,$multipart=false) {
        $this->last_response_headers=array();
        $data=$post_data;
        if(!$multipart) {
            curl_setopt_array($this->curl,array(
                CURLOPT_POST=>true
            ));
            $data=array();
            if(is_array($post_data)) {
                foreach($post_data as $k=>$v) {
                    if($apply_url_encode) {
                        $data[]=urlencode($k).'='.urlencode($v);
                    }else{
                        $data[]=($k).'='.($v);
                    }
                }
                $data=implode('&',$data);
            }else{
                $data=$post_data;
            }
        }
        
        curl_setopt_array($this->curl,array(
            CURLOPT_HTTPHEADER=>$this->genHeaders(),
            CURLOPT_URL=>$url,
            CURLOPT_POSTFIELDS=>$data,
        ));

        $this->doLog($data);
        $this->last_response=curl_exec($this->curl);
        return $this->last_response;
        
    }
    
    protected function get($url) {
        $this->last_response_headers=array();
        curl_setopt_array($this->curl,array(
            CURLOPT_HTTPHEADER=>$this->genHeaders(),
            CURLOPT_HTTPGET=>true,
            CURLOPT_URL=>$url,
        ));
        $this->last_response=curl_exec($this->curl);
        return $this->last_response;
    }
    
    function setReferer($referer) {
        curl_setopt_array($this->curl,array(
            CURLOPT_REFERER=>$referer
        ));
    }
    
    function setBrowser($browser) {
        curl_setopt_array($this->curl,array(
            CURLOPT_USERAGENT=>$browser
        ));
    }
    
    protected function setopt_array($opt_array) {
        curl_setopt_array($this->curl,$opt_array);
    }
    
    function _debugTCP($host, $port) {
        $this->debug_type='tcp';
        $this->tcp_debug_socket=fsockopen($host,$port,$errno,$errstr,10);
        curl_setopt_array($this->curl,array(
            CURLOPT_VERBOSE=>true,
            CURLOPT_STDERR=>$this->tcp_debug_socket
        ));
    }
    
    function _debugFILE($file_path) {
        $this->debug_type='file';
        $this->file_debug_handle=fopen($file_path,'w+b');
        curl_setopt_array($this->curl,array(
            CURLOPT_VERBOSE=>true,
            CURLOPT_STDERR=>$this->file_debug_handle
        ));
    }
    
    protected function doLog($str) {
        if(is_array($str)) {
            $str=print_r($str,true);
        }
        $str='['.date('Y-m-d H:i:s').'] - '.$str."\r\n";
        if($this->debug_type=='tcp') fwrite($this->tcp_debug_socket,$str,strlen($str));
        if($this->debug_type=='file') fwrite($this->file_debug_handle,$str,strlen($str));
    }
    
    static function parseHiddens($data) {
        $data=str_replace("\r",'',str_replace("\n",' ',$data));
        $data=str_replace('<',"\n".'<',$data);
        $data=str_replace('>','>'."\n",$data);
        preg_match_all('#<.*?type=.hidden..*?>#i',$data,$matches);
        foreach($matches[0] as $m) {
            preg_match('#name=(\'|")(?P<name>.*?)(\'|")#i',$m,$matches2);
            $name=$matches2['name'];
            preg_match('#value=(\'|")(?P<value>.*?)(\'|")#i',$m,$matches2);
            $value=$matches2['value'];
            $hiddens[]=array(
                'name'=>$name,
                'value'=>$value,
            );
        }
        return $hiddens;
    }
    
    function getCurl() {
        return $this->curl;
    }
    
    function getCurlInfo() {
        return curl_getinfo($this->curl);
    }
    
    function getResponseHeader() {
        return $this->last_response_headers;
    }
    
    
    
    
    
    
   
    
    
    
}