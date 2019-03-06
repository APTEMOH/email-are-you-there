<?php
#PHP class for checking the existence of email
#Author: pligin
#Email: i@psweb.ru
#Site: psweb.ru

class CheckMail{
    var $timeout = 20;
    var $domain_rules = array ("aol.com", "bigfoot.com", "brain.net.pk", "breathemail.net",
                "compuserve.com", "dialnet.co.uk", "glocksoft.com", "home.com",
                "msn.com", "rocketmail.com", "uu.net", "yahoo.com", "yahoo.de");
    private $host = '';
    private $ismx = NULL;
    private $mxhosts = array();
    private $email = '';
    private $port = '25';
    private $localhost = '';
    private $from = '';
    private $result = false;
    private $connection = '';
    var $debug = array();
    private $isdebug = true;//true - запись отладочной информации, false - отключение записи
    private $errno = '';
    private $error = '';
    function __construct($email) {
        $this->email = $email;
        $this->host = substr(strstr($email,'@'),1);
        $this->ismx = getmxrr($this->host, $this->mxhosts[0], $this->mxhosts[1]);
        $this->_mxhosts();
        $this->localhost = 'psweb.ru';
        $this->from = 'i@' . $this->localhost;
    }
    public function _is_valid_email ($email = ""){
        return preg_match('/^[.\w-]+@([\w-]+\.)+[a-zA-Z]{2,6}$/', $email);
    }
    function _check_domain_rules ($domain = ""){
        return in_array(strtolower($domain), $this->domain_rules); 
    }
    private function _validateEmail(){
        if(!$this->_is_valid_email($this->email)){
            return false;
        }
        if ($this->_check_domain_rules($this->host)){
            return false;
        }
        return true;
    }
    private function _mxhosts(){
        if($this->ismx == true) {
            array_multisort($this->mxhosts[1], $this->mxhosts[0]);
        }else{
            $this->mxhosts[0][0] = $this->host;
            $this->mxhosts[1][1] = 10;
        }
        $this->_debug(['mxhosts' => $this->mxhosts]);
    }
    private function _connection($id){
        $this->_debug($id . " " . $this->mxhosts[0][$id]);
        $this->connection = fsockopen($this->mxhosts[0][$id],$this->port, $this->errno, $this->error, $this->timeout);
        $data = fgets($this->connection,1024);
        $this->_debug([$this->errno => $this->error]);
        $this->_debug($data);
        return substr($data,0,1);
    }
    private function _helo(){
        $content = "HELO $this->localhost\r\n";
        fputs($this->connection,$content); // 250
        $data = fgets ($this->connection,1024);
        $this->_debug($content);
        $this->_debug($data);
        return substr($data,0,1);
    }
    private function _mailFrom(){
        $content = "MAIL FROM:<$this->from>\r\n";
        fputs($this->connection,$content);
        $data = fgets($this->connection,1024);
        $this->_debug($content);
        $this->_debug($data);
        return substr ($data,0,1);
    }
    private function _rcptTo(){
        $content = "RCPT TO:<$this->email>\r\n";
        fputs($this->connection,$content);
        $data = fgets($this->connection,1024);
        $this->_debug($content);
        $this->_debug($data);
        return substr($data,0,1);
    }
    private function _data(){
        $content = "data\r\n";
        fputs($this->connection,$content);
        $data = fgets($this->connection,1024);
        $this->_debug($content);
        $this->_debug($data);
        return substr ($data,0,1);
    }
    private function _quit(){
        $content = "QUIT\r\n";
        fputs($this->connection,$content);
        $data = fclose($this->connection);
        $this->_debug($content);
        $this->_debug($data);
    }
    private function _debug($data){
        if($this->isdebug){
            $this->debug[] = $data;
        }
    }
    public function execute (){
        if(!$this->_validateEmail()){
            return false;
        }
        $id = 0;
        while (!$this->result && $id < count ($this->mxhosts[0])){
            if(!function_exists('fsockopen')){
                $this->_debug(['fsockopen' => 'FALSE']);
                break;
            }
            $connect = $this->_connection($id);
            if($connect != 2){
                $this->_debug(['connect' => $connect]);
                return false;
            }
            if(!$this->connection){
                $this->_debug($this->connection);
                return false;
            }
            if($this->_helo() != '2'){
                return false;
            }
            if($this->_mailFrom() != '2'){
                return false;
            }
            if($this->_rcptTo() != '2'){
                return false;
            }
            if($this->_data() == '3'){
                $this->result = true;
            }
            $this->_quit();
            if($this->result === true){
                return true;
            }
            $id++;
        } //while
        return false;
    }
}