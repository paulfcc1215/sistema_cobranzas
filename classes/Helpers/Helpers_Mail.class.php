<?php
class Helpers_Mail {

    

    private $mail;
    private $username;
    private $password;

    public function __construct() {

        require _BASE_SYS_PATH.'/lib/PHPMailer/class.smtp.php';
        require _BASE_SYS_PATH.'/lib/PHPMailer/class.phpmailer.php';

        $SMTPDebug = 0;
        $Host = 'smtp.gmail.com';
        $SMTPAuth = true;
        $this->username = 'informacion@recapt.com.ec';
        $this->password = 'Info2022..';
        // $this->username = 'diego.gaybor@c-3contactcenter.com';
        // $this->password = 'Satan0201552817';
        // $this->username = 'referidoscreditospichincha@c-3contactcenter.com';
        // $password = 'C-3credipich2017.';
        $SMTPSecure = 'tls';
        $Port = 587;

        $this->mail = new PHPMailer(false);
        $this->mail->SMTPDebug = $SMTPDebug;
        $this->mail->isSMTP();                                  // Set mailer to use SMTP
        $this->mail->Host = $Host;                              // Specify main and backup SMTP servers
        $this->mail->SMTPAuth = $SMTPAuth;                      // Enable SMTP authentication
        $this->mail->Username = $this->username;                // SMTP username
        $this->mail->Password = $this->password;                // SMTP password
        $this->mail->SMTPSecure = $SMTPSecure;                  // Enable TLS encryption, `ssl` also accepted
        $this->mail->Port = $Port;                              // TCP port to connect to

    } 

    public function get_username(){
        return $this->username;
    }
    public function get_password(){
        return $this->password;
    }
    public function set_username($_username){
        $this->username=$_username;
    }
    public function set_password($_password){
        $this->password=$_password;
    }

    public function add_attachment($fpath){
        $this->mail->addAttachment($fpath);
    }
    
    public function sendMail($to,$subject,$content,$from=null,$notes=null) {
        
        if(!is_null($from)) {
            $from_text=$from;
        }else{
            $from_text='Sistema Automatico de Gestion Telefonica';
        }
        $this->mail->setFrom($this->username, $from_text);
        
        if(!is_array($to)) {
            $to = explode(',',$to);
        }
        foreach($to as $t) {
            $this->mail->addAddress($t, $t);     // Add a recipient
        }
        



        //$mail->addReplyTo('info@example.com', 'Information');
        //$mail->addCC('cc@example.com');
        //$mail->addBCC('bcc@example.com');

        //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
        //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name


        $this->mail->isHTML(true);                                  // Set email format to HTML

        $this->mail->Subject = $subject;
        $this->mail->Body    = $content;
        $this->mail->AltBody = $content;

        
        if(!$this->mail->send()) {
            echo 'Message could not be sent.';
            echo 'Mailer Error: ' . $this->mail->ErrorInfo;
            return false;
        }
         
        return true;
        
        
    }
    
    
}